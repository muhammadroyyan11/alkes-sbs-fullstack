<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{
    public function index()
    {
        return view('admin.products.index');
    }

    public function datatable()
    {
        $query = Product::query();

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('name', fn($p) => '<strong>' . e($p->name) . '</strong>')
            ->editColumn('sku', fn($p) => e($p->sku ?? '-'))
            ->filterColumn('name', fn($query, $keyword) => $query->where('name', 'like', "%{$keyword}%"))
            ->filterColumn('sku', fn($query, $keyword) => $query->where('sku', 'like', "%{$keyword}%"))
            ->addColumn('price_fmt', fn($p) => 'Rp ' . number_format($p->price, 0, ',', '.'))
            ->addColumn('stock_badge', function ($p) {
                $cls = $p->stock <= 5 ? 'badge-danger' : ($p->stock <= 20 ? 'badge-warning' : 'badge-success');
                return '<span class="badge ' . $cls . '">' . $p->stock . ' ' . e($p->unit) . '</span>';
            })
            ->addColumn('status_badge', fn($p) => '<span class="badge ' . ($p->is_active ? 'badge-success' : 'badge-secondary') . '">' . ($p->is_active ? 'Aktif' : 'Nonaktif') . '</span>')
            ->addColumn('actions', function ($p) {
                $edit = '<a href="' . route('admin.products.edit', $p) . '" class="btn btn-sm btn-secondary"><i class="fa-solid fa-pen"></i></a>';
                $del  = '<form method="POST" action="' . route('admin.products.destroy', $p) . '" style="display:inline;" onsubmit="return confirm(\'Hapus produk ini?\')"><input type="hidden" name="_token" value="' . csrf_token() . '"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i></button></form>';
                return $edit . ' ' . $del;
            })
            ->rawColumns(['name', 'stock_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.products.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'sku' => 'nullable|string|max:50|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        Product::create([
            'name' => $request->name,
            'sku' => $request->sku ?: 'SKU-' . strtoupper(Str::random(8)),
            'price' => $request->price,
            'stock' => $request->stock,
            'unit' => $request->unit,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        return view('admin.products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $request->validate([
            'name' => 'required|string|max:200',
            'sku' => 'nullable|string|max:50|unique:products,sku,' . $product->id,
            'price' => 'required|numeric|min:0',
            'stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        $product->update([
            'name' => $request->name,
            'sku' => $request->sku ?: $product->sku,
            'price' => $request->price,
            'stock' => $request->stock,
            'unit' => $request->unit,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
