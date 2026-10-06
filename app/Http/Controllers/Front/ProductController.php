<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $stock = $request->query('stock');
        $sort = $request->query('sort', 'latest');
        $kategori = trim((string) $request->query('kategori'));
        $categories = Category::where('is_active', true)->orderBy('name')->get();

        $products = Product::query()
            ->where('is_active', true)
            ->withCount('variants')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($kategori !== '', function ($query) use ($kategori) {
                $query->whereHas('category', fn ($category) => $category->where('slug', $kategori));
            })
            ->when($stock === 'available', fn ($query) => $query->where('stock', '>', 0))
            ->when($stock === 'low', fn ($query) => $query->whereBetween('stock', [1, 10]))
            ->when($stock === 'empty', fn ($query) => $query->where('stock', 0))
            ->when($sort === 'price_low', fn ($query) => $query->orderBy('price'))
            ->when($sort === 'price_high', fn ($query) => $query->orderByDesc('price'))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name'))
            ->when($sort === 'latest', fn ($query) => $query->latest())
            ->paginate(12)
            ->withQueryString();

        return view('front.products.index', compact('products', 'search', 'stock', 'sort', 'categories', 'kategori'));
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['variants' => fn ($query) => $query->where('is_active', true)]);

        $relatedProducts = Product::query()
            ->where('is_active', true)
            ->whereKeyNot($product->id)
            ->limit(4)
            ->get();

        return view('front.products.show', compact('product', 'relatedProducts'));
    }
}
