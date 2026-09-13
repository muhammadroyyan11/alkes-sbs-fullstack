<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockOpname;
use App\Models\StockOpnameItem;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class StockOpnameController extends Controller
{
    public function index()
    {
        return view('admin.stock-opnames.index');
    }

    public function datatable()
    {
        $query = StockOpname::with('user');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('code', fn($s) => '<strong>SO-' . str_pad($s->id, 4, '0', STR_PAD_LEFT) . '</strong>')
            ->addColumn('date_formatted', fn($s) => $s->created_at->format('d/m/Y H:i'))
            ->addColumn('status_badge', function ($s) {
                $badges = [
                    'pending' => '<span class="badge badge-warning">Pending</span>',
                    'counted' => '<span class="badge badge-info">Dihitung</span>',
                    'approved' => '<span class="badge badge-success">Disetujui</span>',
                    'rejected' => '<span class="badge badge-danger">Ditolak</span>',
                ];
                return $badges[$s->status] ?? '<span class="badge badge-secondary">' . e($s->status) . '</span>';
            })
            ->addColumn('user_name', fn($s) => e($s->user?->name ?? '-'))
            ->addColumn('notes', fn($s) => e($s->notes ?? '-'))
            ->addColumn('actions', function ($s) {
                return '<a href="' . route('admin.stock-opnames.show', $s) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-eye"></i></a>';
            })
            ->rawColumns(['code', 'date_formatted', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.stock-opnames.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'notes' => 'nullable|string',
        ]);

        $opname = StockOpname::create([
            'user_id' => auth()->id(),
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return redirect()->route('admin.stock-opnames.show', $opname)->with('success', 'Stok Opname berhasil dibuat.');
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load('items.product', 'items.variant', 'user');
        $stocks = Stock::with('product', 'variant')->get();
        return view('admin.stock-opnames.show', compact('stockOpname', 'stocks'));
    }

    public function count(Request $request, StockOpname $stockOpname)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.stock_id' => 'required|exists:stocks,id',
            'items.*.physical_stock' => 'required|integer|min:0',
        ]);

        foreach ($request->items as $item) {
            $stock = Stock::find($item['stock_id']);
            if ($stock) {
                StockOpnameItem::firstOrCreate(
                    [
                        'stock_opname_id' => $stockOpname->id,
                        'product_id' => $stock->product_id,
                        'variant_id' => $stock->variant_id,
                    ],
                    [
                        'system_stock' => $stock->quantity,
                        'physical_stock' => $item['physical_stock'],
                    ]
                );
            }
        }

        $stockOpname->update(['status' => 'counted']);
        return redirect()->route('admin.stock-opnames.show', $stockOpname)->with('success', 'Data counting berhasil disimpan.');
    }

    public function approve(StockOpname $stockOpname)
    {
        $stockOpname->update(['status' => 'approved']);
        return redirect()->route('admin.stock-opnames.show', $stockOpname)->with('success', 'Stok Opname berhasil disetujui.');
    }

    public function reject(StockOpname $stockOpname)
    {
        $stockOpname->update(['status' => 'rejected']);
        return redirect()->route('admin.stock-opnames.show', $stockOpname)->with('success', 'Stok Opname berhasil ditolak.');
    }
}
