<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        return view('admin.purchase-orders.index');
    }

    public function datatable()
    {
        $query = PurchaseOrder::with('supplier');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('po_number', fn($po) => '<strong>' . e($po->po_number) . '</strong>')
            ->addColumn('supplier_name', fn($po) => e($po->supplier?->name ?? '-'))
            ->addColumn('date_formatted', fn($po) => $po->created_at->format('d/m/Y'))
            ->addColumn('status_badge', function ($po) {
                $badges = [
                    'draft' => '<span class="badge badge-secondary">Draft</span>',
                    'sent' => '<span class="badge badge-info">Terkirim</span>',
                    'partial' => '<span class="badge badge-warning">Sebagian</span>',
                    'received' => '<span class="badge badge-success">Diterima</span>',
                    'cancelled' => '<span class="badge badge-danger">Dibatalkan</span>',
                ];
                return $badges[$po->status] ?? '<span class="badge badge-secondary">' . e($po->status) . '</span>';
            })
            ->addColumn('total_fmt', fn($po) => 'Rp ' . number_format($po->total, 0, ',', '.'))
            ->addColumn('actions', function ($po) {
                $show = '<a href="' . route('admin.purchase-orders.show', $po) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-eye"></i></a>';
                return $show;
            })
            ->rawColumns(['po_number', 'date_formatted', 'status_badge', 'total_fmt', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::with('variants')->orderBy('name')->get();

        return view('admin.purchase-orders.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        foreach ($data['items'] as $index => $item) {
            if (!empty($item['variant_id'])) {
                $variant = \App\Models\Variant::find($item['variant_id']);
                if (!$variant || (int) $variant->product_id !== (int) $item['product_id']) {
                    throw ValidationException::withMessages([
                        'items' => 'Variant pada baris ' . ($index + 1) . ' tidak sesuai dengan produk.',
                    ]);
                }
            }
        }

        $po = DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'po_number' => 'PO-' . strtoupper(uniqid()),
                'supplier_id' => $data['supplier_id'],
                'user_id' => auth()->id(),
                'status' => 'draft',
                'total' => 0,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $qty = (int) $item['quantity'];
                $price = (float) $item['price'];
                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'] ?: null,
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $qty * $price,
                ]);
            }

            $po->update(['total' => (int) $po->items()->sum('subtotal')]);

            return $po;
        });

        return redirect()->route('admin.purchase-orders.show', $po)->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('supplier', 'items.product', 'items.variant', 'user');
        return view('admin.purchase-orders.show', compact('purchaseOrder'));
    }


    /**
     * Halaman cetak PO (tanpa layout admin, siap printer).
     */
    public function print(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('supplier', 'items.product', 'items.variant', 'user');

        return view('admin.purchase-orders.print', compact('purchaseOrder'));
    }

    /**
     * Ekspor item PO ke CSV.
     */
    public function export(PurchaseOrder $purchaseOrder): StreamedResponse
    {
        $purchaseOrder->load('supplier', 'items.product', 'items.variant');

        $fileName = 'PO-' . preg_replace('/[^A-Za-z0-9._-]/', '_', $purchaseOrder->po_number) . '.csv';

        return response()->streamDownload(function () use ($purchaseOrder) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM: Excel membaca UTF-8

            fputcsv($out, ['No. PO', $purchaseOrder->po_number]);
            fputcsv($out, ['Tanggal', $purchaseOrder->created_at->format('d/m/Y H:i')]);
            fputcsv($out, ['Supplier', $purchaseOrder->supplier?->name ?? '-']);
            fputcsv($out, ['Status', ucfirst($purchaseOrder->status)]);
            fputcsv($out, ['Catatan', (string) ($purchaseOrder->notes ?? '')]);
            fputcsv($out, []);
            fputcsv($out, ['No', 'SKU', 'Produk', 'Varian', 'Jumlah', 'Harga', 'Subtotal']);

            foreach ($purchaseOrder->items as $index => $item) {
                fputcsv($out, [
                    $index + 1,
                    $item->variant?->sku ?? $item->product?->sku ?? '',
                    $item->product?->name ?? '',
                    $item->variant?->name ?? '',
                    $item->quantity,
                    $item->price,
                    $item->subtotal,
                ]);
            }

            fputcsv($out, ['', '', '', '', '', 'Total', $purchaseOrder->total]);
            fclose($out);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function send(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->update(['status' => 'sent']);
        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('success', 'PO berhasil dikirim ke supplier.');
    }

    public function cancel(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->update(['status' => 'cancelled']);
        return redirect()->route('admin.purchase-orders.show', $purchaseOrder)->with('success', 'PO berhasil dibatalkan.');
    }
}
