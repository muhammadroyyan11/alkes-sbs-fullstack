<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class OrderController extends Controller
{
    /**
     * Filter ringkasan yang didukung di halaman daftar pesanan.
     */
    protected const FILTERS = ['need_process', 'overdue', 'unpaid', 'today'];

    public function index(Request $request)
    {
        // Pastikan daftar tidak menampilkan pesanan yang sudah lewat batas bayar 30 menit.
        \App\Services\OrderExpiry::run();

        $filter = $this->filterFrom($request);

        // Ringkasan: apa yang harus dikerjakan hari ini + pelanggaran SLA 1 hari.
        $summary = [
            'need_process' => Order::active()->where('payment_status', 'paid')->count(),
            'overdue' => Order::active()->where('created_at', '<', now()->subHour(Order::SLA_HOURS))->count(),
            'unpaid' => Order::active()->where('payment_status', '!=', 'paid')->count(),
            'today' => Order::where('created_at', '>=', now()->startOfDay())->count(),
            'queue_total' => Order::active()->count(),
        ];

        // Antrean diproses: yang paling lama menunggu diurutkan paling atas.
        $queue = Order::with('user')
            ->active()
            ->orderBy('created_at')
            ->limit(20)
            ->get();

        return view('admin.orders.index', compact('summary', 'queue', 'filter'));
    }

    public function datatable(Request $request)
    {
        $query = Order::with('user');
        $this->applyFilter($query, $this->filterFrom($request));

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('order_number', fn (Order $o) => '<strong>' . e($o->order_number) . '</strong>')
            ->addColumn('customer', fn (Order $o) => e($o->user?->name ?? '-'))
            ->addColumn('date_formatted', fn (Order $o) => $o->created_at->format('d/m/Y H:i'))
            ->addColumn('total_formatted', fn (Order $o) => 'Rp ' . number_format($o->total, 0, ',', '.'))
            ->addColumn('payment_badge', fn (Order $o) => '<span class="badge ' . $o->paymentBadgeClass() . '">' . $o->paymentStatusLabel() . '</span>')
            ->addColumn('status_badge', fn (Order $o) => '<span class="badge ' . $o->statusBadgeClass() . '">' . $o->statusLabel() . '</span>')
            ->addColumn('age', function (Order $o) {
                $overdue = $o->isOverdue();

                return '<span class="badge ' . ($overdue ? 'badge-danger' : 'badge-secondary') . '">'
                    . e($o->ageLabel()) . '</span>'
                    . '<div style="font-size:.72rem;font-weight:600;margin-top:3px;color:' . ($overdue ? '#dc2626' : '#16a34a') . ';">'
                    . e($o->slaLabel()) . '</div>';
            })
            ->addColumn('actions', function (Order $o) {
                return '<a href="' . route('admin.orders.show', $o) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-eye"></i></a>';
            })
            ->rawColumns(['order_number', 'payment_badge', 'status_badge', 'age', 'actions'])
            ->make(true);
    }

    /**
     * Ambil & validasi parameter filter dari query string.
     */
    protected function filterFrom(Request $request): ?string
    {
        $filter = $request->query('filter');

        return in_array($filter, self::FILTERS, true) ? $filter : null;
    }

    /**
     * Batasi query pesanan sesuai filter ringkasan.
     */
    protected function applyFilter($query, ?string $filter): void
    {
        match ($filter) {
            'need_process' => $query->active()->where('payment_status', 'paid'),
            'overdue' => $query->active()->where('created_at', '<', now()->subHour(Order::SLA_HOURS)),
            'unpaid' => $query->active()->where('payment_status', '!=', 'paid'),
            'today' => $query->where('created_at', '>=', now()->startOfDay()),
            default => null,
        };
    }

    public function show(Order $order)
    {
        $order->load('user', 'items', 'shipment', 'address', 'stockLogs');

        $availableActions = $this->availableActions($order);

        return view('admin.orders.show', compact('order', 'availableActions'));
    }

    /**
     * Konfirmasi pembayaran manual (untuk kasus webhook belum masuk / transfer manual).
     */
    public function markPaid(Request $request, Order $order)
    {
        if ($order->payment_status === 'paid') {
            return back()->with('success', 'Pesanan sudah lunas.');
        }

        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages([
                'status' => 'Pesanan sudah dibatalkan, tidak bisa ditandai lunas.',
            ]);
        }

        $order->update([
            'payment_status' => 'paid',
            'paid_at' => now(),
            'payment_reference' => 'manual-' . $request->user()->id,
            'status' => $order->status === 'pending' ? 'confirmed' : $order->status,
        ]);
        $order->markReservationPaid();

        return back()->with('success', 'Pembayaran dikonfirmasi. Pesanan menjadi terkonfirmasi.');
    }

    /**
     * Ubah status pesanan (diproses / selesai).
     */
    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => 'required|in:processing,completed',
        ]);

        $status = $data['status'];

        if ($status === 'processing') {
            if ($order->payment_status !== 'paid') {
                throw ValidationException::withMessages([
                    'status' => 'Pembayaran belum lunas. Konfirmasi pembayaran dulu sebelum diproses.',
                ]);
            }
            if (!in_array($order->status, ['pending', 'confirmed'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status saat ini (' . $order->statusLabel() . ') tidak bisa diproses.',
                ]);
            }
        }

        if ($status === 'completed') {
            if (!in_array($order->status, ['processing', 'shipped', 'in_transit'], true)) {
                throw ValidationException::withMessages([
                    'status' => 'Status saat ini (' . $order->statusLabel() . ') tidak bisa diselesaikan.',
                ]);
            }

            $shipment = $order->shipment;
            if ($shipment && !$shipment->delivered_at) {
                $shipment->update(['status' => 'delivered', 'delivered_at' => now()]);
            }
        }

        $order->update(['status' => $status]);

        return back()->with('success', 'Status pesanan diperbarui menjadi "' . $order->statusLabel() . '".');
    }

    /**
     * Kirim pesanan: isi nomor resi, tandai shipment & order sebagai dikirim.
     */
    public function ship(Request $request, Order $order)
    {
        $data = $request->validate([
            'tracking_number' => 'required|string|max:60',
            'courier' => 'nullable|string|max:40',
        ]);

        if ($order->payment_status !== 'paid') {
            throw ValidationException::withMessages([
                'tracking_number' => 'Pembayaran belum lunas, pesanan tidak bisa dikirim.',
            ]);
        }

        if (!in_array($order->status, ['pending', 'confirmed', 'processing'], true)) {
            throw ValidationException::withMessages([
                'tracking_number' => 'Status saat ini (' . $order->statusLabel() . ') tidak bisa dikirim.',
            ]);
        }

        DB::transaction(function () use ($order, $data) {
            $shipment = $order->shipment ?: $order->shipment()->make();
            $shipment->fill([
                'tracking_number' => $data['tracking_number'],
                'status' => 'shipped',
                'shipped_at' => now(),
            ]);
            if (!empty($data['courier'])) {
                $shipment->courier = $data['courier'];
            }
            $shipment->save();

            $order->update(['status' => 'shipped']);
        });

        return back()->with('success', 'Pesanan dikirim. Resi ' . $data['tracking_number'] . ' tercatat.');
    }

    /**
     * Batalkan pesanan + kembalikan stok (belum boleh dikirim).
     */
    public function cancel(Request $request, Order $order)
    {
        if (in_array($order->status, ['shipped', 'in_transit', 'delivered', 'completed'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Pesanan sudah dikirim/selesai dan tidak bisa dibatalkan.',
            ]);
        }

        if ($order->status === 'cancelled') {
            return back()->with('success', 'Pesanan sudah dibatalkan.');
        }

        $order->cancelAndRelease('dibatalkan admin', $request->user()->id);

        return back()->with('success', 'Pesanan dibatalkan dan stok dikembalikan.');
    }

    /**
     * Tombol aksi yang tersedia sesuai status pesanan.
     */
    protected function availableActions(Order $order): array
    {
        $paid = $order->payment_status === 'paid';

        return [
            'mark_paid' => !$paid && !in_array($order->status, ['cancelled', 'completed'], true),
            'process' => $paid && in_array($order->status, ['pending', 'confirmed'], true),
            'ship' => $paid && in_array($order->status, ['pending', 'confirmed', 'processing'], true),
            'complete' => in_array($order->status, ['processing', 'shipped', 'in_transit'], true),
            'cancel' => !in_array($order->status, ['shipped', 'in_transit', 'delivered', 'completed', 'cancelled'], true),
        ];
    }
}
