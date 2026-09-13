<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseReceive;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
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
        $purchaseOrders = PurchaseOrder::whereIn('status', ['sent', 'partial'])->orderBy('created_at', 'desc')->get();
        return view('admin.purchase-receives.create', compact('purchaseOrders'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'notes' => 'nullable|string',
        ]);

        $receive = PurchaseReceive::create([
            'receive_number' => 'PR-' . strtoupper(uniqid()),
            'purchase_order_id' => $request->purchase_order_id,
            'user_id' => auth()->id(),
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.purchase-receives.show', $receive)->with('success', 'Purchase Receive berhasil dibuat.');
    }

    public function show(PurchaseReceive $purchaseReceive)
    {
        $purchaseReceive->load('purchaseOrder', 'items.product', 'items.variant', 'user');
        return view('admin.purchase-receives.show', compact('purchaseReceive'));
    }

    public function approve(PurchaseReceive $purchaseReceive)
    {
        $purchaseReceive->update(['status' => 'received']);
        return redirect()->route('admin.purchase-receives.show', $purchaseReceive)->with('success', 'Purchase Receive berhasil disetujui.');
    }

    public function reject(PurchaseReceive $purchaseReceive)
    {
        $purchaseReceive->update(['status' => 'rejected']);
        return redirect()->route('admin.purchase-receives.show', $purchaseReceive)->with('success', 'Purchase Receive berhasil ditolak.');
    }
}
