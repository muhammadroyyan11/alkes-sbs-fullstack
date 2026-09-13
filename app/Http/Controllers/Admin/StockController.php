<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class StockController extends Controller
{
    public function index()
    {
        return view('admin.stocks.index');
    }

    public function datatable()
    {
        $query = Stock::with('product', 'variant', 'warehouse');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('product_name', fn($s) => '<strong>' . e($s->product?->name ?? '-') . '</strong>')
            ->addColumn('variant_name', fn($s) => e($s->variant?->name ?? '-'))
            ->addColumn('warehouse_name', fn($s) => e($s->warehouse?->name ?? '-'))
            ->addColumn('quantity_badge', function ($s) {
                $cls = $s->quantity <= 5 ? 'badge-danger' : ($s->quantity <= 20 ? 'badge-warning' : 'badge-success');
                return '<span class="badge ' . $cls . '">' . $s->quantity . '</span>';
            })
            ->addColumn('actions', function ($s) {
                return '<a href="' . route('admin.stocks.show', $s) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-eye"></i></a>';
            })
            ->rawColumns(['product_name', 'quantity_badge', 'actions'])
            ->make(true);
    }

    public function show(Stock $stock)
    {
        $stock->load('product', 'variant', 'warehouse', 'mutations.creator');
        return view('admin.stocks.show', compact('stock'));
    }
}
