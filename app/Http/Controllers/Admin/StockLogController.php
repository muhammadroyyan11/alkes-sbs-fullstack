<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StockLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = [
            'type' => in_array($request->query('type'), ['in', 'out'], true) ? $request->query('type') : null,
            'status' => in_array($request->query('status'), [
                StockMutation::STATUS_WAITING,
                StockMutation::STATUS_COMPLETED,
                StockMutation::STATUS_CANCELLED,
            ], true) ? $request->query('status') : null,
        ];

        return view('admin.stock-logs.index', compact('filters'));
    }

    public function datatable(Request $request)
    {
        $query = StockMutation::with(['stock.product', 'stock.variant', 'creator', 'reference']);

        if (in_array($request->query('type'), ['in', 'out'], true)) {
            $query->where('type', $request->query('type'));
        }

        if (in_array($request->query('status'), [
            StockMutation::STATUS_WAITING,
            StockMutation::STATUS_COMPLETED,
            StockMutation::STATUS_CANCELLED,
        ], true)) {
            $query->where('status', $request->query('status'));
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('date_formatted', fn (StockMutation $m) => $m->created_at->format('d/m/Y H:i'))
            ->addColumn('product', function (StockMutation $m) {
                $variant = $m->stock?->variant;
                $product = $m->stock?->product;

                return e(trim(
                    ($product?->name ?? '-') . ($variant ? ' - ' . $variant->name : '')
                ));
            })
            ->addColumn('type_badge', fn (StockMutation $m) => '<span class="badge ' . $m->typeBadgeClass() . '">' . $m->typeLabel() . '</span>')
            ->addColumn('status_badge', fn (StockMutation $m) => '<span class="badge ' . $m->statusBadgeClass() . '">' . $m->statusLabel() . '</span>')
            ->addColumn('quantity', fn (StockMutation $m) => $m->type === 'out' ? '-' . $m->quantity : '+' . $m->quantity)
            ->addColumn('reference', function (StockMutation $m) {
                if ($m->reference instanceof Order) {
                    return '<a href="' . route('admin.orders.show', $m->reference) . '">' . e($m->reference->order_number) . '</a>';
                }

                return $m->reference_type ? class_basename($m->reference_type) . ' #' . $m->reference_id : '-';
            })
            ->addColumn('note', fn (StockMutation $m) => e($m->note ?? '-'))
            ->addColumn('user', fn (StockMutation $m) => e($m->creator?->name ?? 'Sistem'))
            ->rawColumns(['type_badge', 'status_badge', 'reference'])
            ->make(true);
    }
}
