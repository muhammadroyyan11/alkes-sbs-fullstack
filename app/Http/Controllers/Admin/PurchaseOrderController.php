<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        return view('admin.purchase-orders.create', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'notes' => 'nullable|string',
        ]);

        $po = PurchaseOrder::create([
            'po_number' => 'PO-' . strtoupper(uniqid()),
            'supplier_id' => $request->supplier_id,
            'user_id' => auth()->id(),
            'status' => 'draft',
            'total' => 0,
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.purchase-orders.show', $po)->with('success', 'Purchase Order berhasil dibuat.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load('supplier', 'items.product', 'items.variant', 'user');
        return view('admin.purchase-orders.show', compact('purchaseOrder'));
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
