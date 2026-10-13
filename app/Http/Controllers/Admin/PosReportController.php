<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Models\PosSaleItem;
use App\Services\PosSaleService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Laporan penjualan POS (kasir): ringkasan, filter, detail, ekspor.
 *
 * Sumber angka: tabel `pos_sales` + `pos_sale_items`.
 * Stok penjualan mengikuti konsep utama (StockLogService) sehingga laporan
 * stok di menu Stok → Log Stok ikut mencatat penjualan POS ini.
 */
class PosReportController extends Controller
{
    public function __construct(private PosSaleService $pos)
    {
    }

    public function index(Request $request): View
    {
        $filters = $this->filters($request);

        $summary = $this->baseQuery($filters)
            ->selectRaw('COUNT(*) as transactions, COALESCE(SUM(total),0) as revenue, COALESCE(SUM(discount),0) as discount')
            ->first();

        $itemsSold = $this->baseQuery($filters)
            ->join('pos_sale_items', 'pos_sale_items.pos_sale_id', '=', 'pos_sales.id')
            ->sum('pos_sale_items.quantity');

        $sales = $this->baseQuery($filters)
            ->with('user')
            ->orderByDesc('sold_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $topProducts = $this->baseQuery($filters)
            ->join('pos_sale_items', 'pos_sale_items.pos_sale_id', '=', 'pos_sales.id')
            ->selectRaw('
                pos_sale_items.sku,
                pos_sale_items.product_name,
                pos_sale_items.variant_name,
                SUM(pos_sale_items.quantity) as qty,
                SUM(pos_sale_items.subtotal) as revenue
            ')
            ->groupBy('pos_sale_items.sku', 'pos_sale_items.product_name', 'pos_sale_items.variant_name')
            ->orderByDesc('qty')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        return view('admin.pos.sales.index', compact(
            'sales', 'summary', 'itemsSold', 'topProducts', 'filters'
        ));
    }

    public function show(PosSale $posSale): View
    {
        $posSale->load(['user', 'items']);

        return view('admin.pos.sales.show', ['sale' => $posSale]);
    }

    /**
     * Batalkan transaksi: stok jual + buku gudang dikembalikan, mutasi ditutup.
     */
    public function cancel(Request $request, PosSale $posSale)
    {
        $data = $request->validate([
            'reason' => 'nullable|string|max:200',
        ]);

        if ($posSale->isCancelled()) {
            return back()->with('error', 'Transaksi sudah dibatalkan sebelumnya.');
        }

        $this->pos->cancel($posSale, $data['reason'] ?? null, $request->user()->id);

        return redirect()
            ->route('admin.pos.sales.show', $posSale)
            ->with('success', 'Transaksi ' . $posSale->invoice . ' dibatalkan dan stok dikembalikan.');
    }

    /**
     * Ekspor CSV sesuai filter yang aktif.
     */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request);
        $sales = $this->baseQuery($filters)->with('user')->orderByDesc('sold_at')->get();

        $range = $filters['label'];
        $fileName = 'laporan-penjualan-pos-' . preg_replace('/[^A-Za-z0-9._-]/', '_', $range) . '.csv';

        return response()->streamDownload(function () use ($sales) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel membaca UTF-8

            fputcsv($out, ['Invoice', 'Tanggal', 'Kasir', 'Pelanggan', 'Item', 'Subtotal', 'Diskon', 'Total', 'Bayar', 'Kembali', 'Pembayaran', 'Status', 'Catatan']);

            foreach ($sales as $sale) {
                fputcsv($out, [
                    $sale->invoice,
                    $sale->sold_at?->format('d/m/Y H:i'),
                    $sale->user?->name ?? '-',
                    $sale->customer_name ?? '-',
                    $sale->items()->sum('quantity'),
                    $sale->subtotal,
                    $sale->discount,
                    $sale->total,
                    $sale->paid_amount,
                    $sale->change_amount,
                    PosSale::paymentMethodLabel($sale->payment_method),
                    ucfirst($sale->status),
                    (string) ($sale->notes ?? ''),
                ]);
            }

            fputcsv($out, []);
            fputcsv($out, [
                'TOTAL', '', '', '',
                $sales->sum(fn ($sale) => $sale->items()->sum('quantity')),
                $sales->sum('subtotal'),
                $sales->sum('discount'),
                $sales->sum('total'),
            ]);

            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Baca filter dari query string (rentang cepat + tanggal manual + metode + status).
     *
     * @return array{from: Carbon, to: Carbon, label: string, payment_method: ?string, status: ?string, q: ?string}
     */
    private function filters(Request $request): array
    {
        $range = (string) $request->query('range', '');
        $from = $request->query('date_from');
        $to = $request->query('date_to');

        [$start, $end, $label] = match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay(), 'hari-ini'],
            'yesterday' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay(), 'kemarin'],
            '7days' => [now()->subDays(6)->startOfDay(), now()->endOfDay(), '7-hari'],
            '30days' => [now()->subDays(29)->startOfDay(), now()->endOfDay(), '30-hari'],
            'month' => [now()->startOfMonth(), now()->endOfDay(), 'bulan-ini'],
            default => $this->customRange($from, $to),
        };

        return [
            'from' => $start,
            'to' => $end,
            'label' => $label,
            'payment_method' => $request->query('payment_method') ?: null,
            'status' => $request->query('status') ?: null,
            'q' => trim((string) $request->query('q', '')) ?: null,
        ];
    }

    /** @return array{0: Carbon, 1: Carbon, 2: string} */
    private function customRange(mixed $from, mixed $to): array
    {
        $start = $from
            ? Carbon::parse($from)->startOfDay()
            : now()->subDays(29)->startOfDay();

        $end = $to
            ? Carbon::parse($to)->endOfDay()
            : now()->endOfDay();

        return [$start, $end, $from || $to ? 'custom' : '30-hari'];
    }

    private function baseQuery(array $filters)
    {
        return PosSale::query()
            ->whereBetween('sold_at', [$filters['from'], $filters['to']])
            ->when($filters['payment_method'], fn ($query, $method) => $query->where('payment_method', $method))
            ->when($filters['status'], fn ($query, $status) => $query->where('status', $status))
            ->when($filters['q'], function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('invoice', 'like', '%' . $search . '%')
                        ->orWhere('customer_name', 'like', '%' . $search . '%');
                });
            });
    }
}
