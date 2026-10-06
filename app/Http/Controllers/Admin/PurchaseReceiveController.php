<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReceive;
use App\Models\PurchaseReceiveItem;
use App\Models\PurchaseOrder;
use App\Services\StockLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class PurchaseReceiveController extends Controller
{
    public function index()
    {
        return view('admin.purchase-receives.index');
    }

    public function datatable()
    {
        $query = PurchaseReceive::with('purchaseOrder', 'user');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('receive_number', fn($r) => '<strong>' . e($r->receive_number) . '</strong>')
            ->addColumn('po_number', fn($r) => e($r->purchaseOrder?->po_number ?? '-'))
            ->addColumn('date_formatted', fn($r) => $r->created_at->format('d/m/Y'))
            ->addColumn('status_badge', function ($r) {
                $badges = [
                    'pending' => '<span class="badge badge-warning">Pending</span>',
                    'received' => '<span class="badge badge-success">Diterima</span>',
                    'rejected' => '<span class="badge badge-danger">Ditolak</span>',
                ];
                return $badges[$r->status] ?? '<span class="badge badge-secondary">' . e($r->status) . '</span>';
            })
            ->addColumn('actions', function ($r) {
                return '<a href="' . route('admin.purchase-receives.show', $r) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-eye"></i></a>';
            })
            ->rawColumns(['receive_number', 'date_formatted', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $purchaseOrders = PurchaseOrder::with('items.product', 'items.variant')
            ->whereIn('status', ['sent', 'partial'])
            ->orderBy('created_at', 'desc')
            ->get();

        $poItemsJson = $purchaseOrders->mapWithKeys(function ($po) {
            return [
                $po->id => $po->items->map(fn ($item) => [
                    'id' => $item->id,
                    'product' => $item->product?->name ?? ('Produk #' . $item->product_id),
                    'variant' => $item->variant?->name,
                    'qty' => (int) $item->quantity,
                ])->values()->all(),
            ];
        });

        return view('admin.purchase-receives.create', compact('purchaseOrders', 'poItemsJson'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'notes' => 'nullable|string',
            'items' => 'sometimes|array',
            'items.*' => 'nullable|integer|min:0',
        ]);

        $purchaseOrder = PurchaseOrder::with('items.product', 'items.variant')
            ->findOrFail($data['purchase_order_id']);

        if (!in_array($purchaseOrder->status, ['sent', 'partial'], true)) {
            throw ValidationException::withMessages([
                'purchase_order_id' => 'Hanya PO berstatus Terkirim/Sebagian yang bisa diterima (status: ' . $purchaseOrder->status . ').',
            ]);
        }

        $submitted = collect($data['items'] ?? [])->filter(fn ($v) => $v !== null && $v !== '');

        if ($purchaseOrder->items->isNotEmpty() && $submitted->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Tentukan jumlah diterima untuk minimal satu item PO.',
            ]);
        }

        $alreadyReceived = $this->receivedQuantities($purchaseOrder);
        $rows = [];

        foreach ($purchaseOrder->items as $poItem) {
            if (!isset($submitted[$poItem->id])) {
                continue;
            }

            $qty = (int) $submitted[$poItem->id];
            if ($qty < 0) {
                throw ValidationException::withMessages(['items' => 'Jumlah diterima tidak boleh negatif.']);
            }
            if ($qty === 0) {
                continue;
            }

            $key = $poItem->product_id . '|' . ($poItem->variant_id ?? 0);
            $remaining = (int) $poItem->quantity - ($alreadyReceived[$key] ?? 0);
            $label = $poItem->product?->name ?? 'Produk #' . $poItem->product_id;
            if ($poItem->variant) {
                $label .= ' - ' . $poItem->variant->name;
            }

            if ($qty > $remaining) {
                throw ValidationException::withMessages([
                    'items' => 'Jumlah diterima "' . $label . '" (' . $qty . ') melebihi sisa PO (' . max(0, $remaining) . ').',
                ]);
            }

            $rows[] = [
                'product_id' => $poItem->product_id,
                'variant_id' => $poItem->variant_id,
                'quantity_received' => $qty,
            ];
        }

        $receive = DB::transaction(function () use ($data, $purchaseOrder, $rows) {
            $receive = PurchaseReceive::create([
                'receive_number' => 'PR-' . strtoupper(uniqid()),
                'purchase_order_id' => $purchaseOrder->id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($rows as $row) {
                $receive->items()->create($row);
            }

            return $receive;
        });

        return redirect()->route('admin.purchase-receives.show', $receive)
            ->with('success', 'Purchase Receive berhasil dibuat. Stok bertambah setelah disetujui.');
    }

    public function show(PurchaseReceive $purchaseReceive)
    {
        $purchaseReceive->load('purchaseOrder', 'items.product', 'items.variant', 'user', 'approvedBy');
        return view('admin.purchase-receives.show', compact('purchaseReceive'));
    }

    public function approve(PurchaseReceive $purchaseReceive)
    {
        if ($purchaseReceive->status !== 'pending') {
            return redirect()
                ->route('admin.purchase-receives.show', $purchaseReceive)
                ->with('error', 'Penerimaan ini sudah diproses (' . $purchaseReceive->status . '). Tidak diproses ulang.');
        }

        try {
            DB::transaction(function () use ($purchaseReceive) {
                $log = app(StockLogService::class);

                foreach ($purchaseReceive->items as $item) {
                    $log->receiveFromPurchase(
                        $item->variant_id,
                        $item->product_id,
                        (int) $item->quantity_received,
                        $purchaseReceive,
                        auth()->id()
                    );
                }

                $purchaseReceive->update([
                    'status' => 'received',
                    'approved_by' => auth()->id(),
                    'received_at' => now(),
                ]);

                $this->syncPoStatus($purchaseReceive->purchase_order_id);
            });
        } catch (\Throwable $e) {
            report($e);

            return redirect()
                ->route('admin.purchase-receives.show', $purchaseReceive)
                ->with('error', 'Penerimaan gagal disetujui: ' . $e->getMessage());
        }

        $count = $purchaseReceive->items()->count();

        return redirect()
            ->route('admin.purchase-receives.show', $purchaseReceive)
            ->with('success', $count
                ? 'Penerimaan disetujui. Stok bertambah untuk ' . $count . ' item.'
                : 'Penerimaan disetujui.');
    }

    public function reject(PurchaseReceive $purchaseReceive)
    {
        if ($purchaseReceive->status !== 'pending') {
            return redirect()
                ->route('admin.purchase-receives.show', $purchaseReceive)
                ->with('error', 'Penerimaan ini sudah diproses (' . $purchaseReceive->status . ').');
        }

        $purchaseReceive->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'received_at' => now(),
        ]);

        return redirect()
            ->route('admin.purchase-receives.show', $purchaseReceive)
            ->with('success', 'Purchase Receive ditolak. Tidak ada perubahan stok.');
    }

    /**
     * Total yang sudah dipesan lewat PR lain (pending/received) per produk/variant.
     */
    private function receivedQuantities(PurchaseOrder $purchaseOrder): array
    {
        $items = PurchaseReceiveItem::query()
            ->join('purchase_receives', 'purchase_receives.id', '=', 'purchase_receive_items.purchase_receive_id')
            ->where('purchase_receives.purchase_order_id', $purchaseOrder->id)
            ->whereIn('purchase_receives.status', ['pending', 'received'])
            ->get([
                'purchase_receive_items.product_id',
                'purchase_receive_items.variant_id',
                'purchase_receive_items.quantity_received',
            ]);

        $totals = [];
        foreach ($items as $item) {
            $key = $item->product_id . '|' . ($item->variant_id ?? 0);
            $totals[$key] = ($totals[$key] ?? 0) + (int) $item->quantity_received;
        }

        return $totals;
    }

    /**
     * Perbarui status PO: semua item sudah diterima -> received,
     * sebagian -> partial.
     */
    private function syncPoStatus(int $purchaseOrderId): void
    {
        $purchaseOrder = PurchaseOrder::with('items')->find($purchaseOrderId);

        if (!$purchaseOrder || $purchaseOrder->items->isEmpty()) {
            return;
        }

        if (in_array($purchaseOrder->status, ['cancelled', 'draft'], true)) {
            return;
        }

        $received = [];
        $receiveItems = PurchaseReceiveItem::query()
            ->join('purchase_receives', 'purchase_receives.id', '=', 'purchase_receive_items.purchase_receive_id')
            ->where('purchase_receives.purchase_order_id', $purchaseOrder->id)
            ->where('purchase_receives.status', 'received')
            ->get([
                'purchase_receive_items.product_id',
                'purchase_receive_items.variant_id',
                'purchase_receive_items.quantity_received',
            ]);

        foreach ($receiveItems as $item) {
            $key = $item->product_id . '|' . ($item->variant_id ?? 0);
            $received[$key] = ($received[$key] ?? 0) + (int) $item->quantity_received;
        }

        $allReceived = true;
        $anyReceived = false;

        foreach ($purchaseOrder->items as $poItem) {
            $key = $poItem->product_id . '|' . ($poItem->variant_id ?? 0);
            $got = $received[$key] ?? 0;

            if ($got > 0) {
                $anyReceived = true;
            }
            if ($got < (int) $poItem->quantity) {
                $allReceived = false;
            }
        }

        $purchaseOrder->update([
            'status' => $allReceived ? 'received' : ($anyReceived ? 'partial' : $purchaseOrder->status),
        ]);
    }
}
