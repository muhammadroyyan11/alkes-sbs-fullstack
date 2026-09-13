<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Variant;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class VariantController extends Controller
{
    public function index()
    {
        return view('admin.variants.index');
    }

    public function datatable()
    {
        $query = Variant::with('product');

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('product_name', fn($v) => '<strong>' . e($v->product?->name ?? '-') . '</strong>')
            ->addColumn('name', fn($v) => e($v->name))
            ->addColumn('sku', fn($v) => e($v->sku ?? '-'))
            ->addColumn('price_fmt', fn($v) => 'Rp ' . number_format($v->price, 0, ',', '.'))
            ->addColumn('stock_badge', function ($v) {
                $cls = $v->stock <= 5 ? 'badge-danger' : ($v->stock <= 20 ? 'badge-warning' : 'badge-success');
                return '<span class="badge ' . $cls . '">' . $v->stock . '</span>';
            })
            ->addColumn('status_badge', fn($v) => '<span class="badge ' . ($v->is_active ? 'badge-success' : 'badge-secondary') . '">' . ($v->is_active ? 'Aktif' : 'Nonaktif') . '</span>')
            ->addColumn('actions', function ($v) {
                $edit = '<a href="' . route('admin.variants.edit', $v) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pen"></i></a>';
                $del  = '<form method="POST" action="' . route('admin.variants.destroy', $v) . '" style="display:inline;" onsubmit="return confirm(\'Hapus variant ini?\')"><input type="hidden" name="_token" value="' . csrf_token() . '"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['product_name', 'stock_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        return view('admin.variants.create', compact('products'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:200',
            'sku' => 'nullable|string|max:50|unique:variants,sku',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
        ]);

        Variant::create([
            'product_id' => $request->product_id,
            'name' => $request->name,
            'sku' => $request->sku ?: 'VAR-' . strtoupper(Str::random(8)),
            'price' => $request->price,
            'stock' => $request->stock,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.variants.index')->with('success', 'Variant berhasil ditambahkan.');
    }

    public function edit(Variant $variant)
    {
        $products = Product::orderBy('name')->get();
        return view('admin.variants.edit', compact('variant', 'products'));
    }

    public function update(Request $request, Variant $variant)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'required|string|max:200',
            'sku' => 'nullable|string|max:50|unique:variants,sku,' . $variant->id,
            'price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
        ]);

        $variant->update([
            'product_id' => $request->product_id,
            'name' => $request->name,
            'sku' => $request->sku ?: $variant->sku,
            'price' => $request->price,
            'stock' => $request->stock,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.variants.index')->with('success', 'Variant berhasil diperbarui.');
    }

    public function destroy(Variant $variant)
    {
        $variant->delete();
        return redirect()->route('admin.variants.index')->with('success', 'Variant berhasil dihapus.');
    }
}
