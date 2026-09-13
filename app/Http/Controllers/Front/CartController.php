<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Variant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function index(Request $request): View
    {
        return view('front.cart.index', ['cart' => $request->session()->get('cart', [])]);
    }

    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1'], 'variant_id' => ['nullable', 'integer', 'exists:variants,id']]);
        $variant = empty($data['variant_id']) ? null : Variant::whereKey($data['variant_id'])->where('product_id', $product->id)->where('is_active', true)->firstOrFail();
        $stock = $variant?->stock ?? $product->stock;
        if (!$product->is_active || $stock <= 0) return back()->with('error', 'Produk sedang tidak tersedia.');

        $key = $product->id.'-'.($variant?->id ?? 'default');
        $cart = $request->session()->get('cart', []);
        $cart[$key] = [
            'key' => $key, 'product_id' => $product->id, 'variant_id' => $variant?->id,
            'name' => $product->name, 'variant_name' => $variant?->name,
            'sku' => $variant?->sku ?? $product->sku, 'price' => (float) ($variant?->price ?? $product->price),
            'quantity' => min(($cart[$key]['quantity'] ?? 0) + $data['quantity'], $stock),
            'stock' => $stock, 'unit' => $product->unit, 'image' => $product->image_url,
        ];
        $request->session()->put('cart', $cart);
        return redirect()->route('cart.index')->with('success', 'Produk ditambahkan ke keranjang.');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(['quantities' => ['required', 'array'], 'quantities.*' => ['required', 'integer', 'min:1']]);
        $cart = $request->session()->get('cart', []);
        foreach ($data['quantities'] as $key => $quantity) if (isset($cart[$key])) $cart[$key]['quantity'] = min($quantity, $cart[$key]['stock']);
        $request->session()->put('cart', $cart);
        return back()->with('success', 'Keranjang berhasil diperbarui.');
    }

    public function destroy(Request $request, string $key): RedirectResponse
    {
        $cart = $request->session()->get('cart', []); unset($cart[$key]); $request->session()->put('cart', $cart);
        return back()->with('success', 'Produk dihapus dari keranjang.');
    }
}
