<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
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
            ->addColumn('category', fn($p) => e($p->category?->name ?? '-'))
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
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
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
            'category_id' => 'nullable|integer|exists:categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        Product::create([
            'name' => $request->name,
            'sku' => $request->sku ?: 'SKU-' . strtoupper(Str::random(8)),
            'price' => $request->price,
            'stock' => $request->stock,
            'unit' => $request->unit,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'category_id' => $request->category_id ?: null,
            'image' => $this->storeUploadedImage($request),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
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
            'category_id' => 'nullable|integer|exists:categories,id',
            'image' => 'nullable|image|max:2048',
        ]);

        $image = $product->image;
        if ($request->boolean('remove_image')) {
            $this->deleteUploadedImage($image);
            $image = null;
        }
        $uploaded = $this->storeUploadedImage($request);
        if ($uploaded) {
            $this->deleteUploadedImage($product->image);
            $image = $uploaded;
        }

        $product->update([
            'name' => $request->name,
            'sku' => $request->sku ?: $product->sku,
            'price' => $request->price,
            'stock' => $request->stock,
            'unit' => $request->unit,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'category_id' => $request->category_id ?: null,
            'image' => $image,
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        $this->deleteUploadedImage($product->image);
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Produk berhasil dihapus.');
    }

    /**
     * Simpan gambar produk yang diunggah ke public/img/products.
     * Mengembalikan nama file, atau null bila tidak ada unggahan.
     */
    private function storeUploadedImage(Request $request): ?string
    {
        if (!$request->hasFile('image') || !$request->file('image')->isValid()) {
            return null;
        }

        $directory = public_path('img/products');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = $request->file('image');
        $name = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
        $file->move($directory, $name);

        return $name;
    }

    private function deleteUploadedImage(?string $name): void
    {
        if (!$name) {
            return;
        }

        $path = public_path('img/products/' . basename($name));
        if (is_file($path)) {
            @unlink($path);
        }
    }
}
