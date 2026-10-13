<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PosSale;
use App\Models\Product;
use App\Services\PosSaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Layar kasir (Point of Sale) di panel admin.
 */
class PosCashierController extends Controller
{
    public function __construct(private PosSaleService $pos)
    {
    }

    public function index(): View
    {
        return view('admin.pos.cashier');
    }

    /**
     * Daftar item jual untuk pencarian kasir (produk tanpa varian + varian aktif).
     */
    public function products(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $needle = mb_strtolower($search);

        $products = Product::query()
            ->where('is_active', true)
            ->with(['variants' => fn ($query) => $query->where('is_active', true)])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('name')
            ->limit(200)
            ->get();

        $items = [];

        foreach ($products as $product) {
            $productMatch = $needle === ''
                || str_contains(mb_strtolower((string) $product->name), $needle)
                || str_contains(mb_strtolower((string) $product->sku), $needle);

            if ($product->variants->isEmpty()) {
                $items[] = [
                    'key' => 'p-' . $product->id,
                    'type' => 'product',
                    'product_id' => $product->id,
                    'variant_id' => null,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'price' => (float) $product->price,
                    'stock' => (int) $product->stock,
                ];

                continue;
            }

            foreach ($product->variants as $variant) {
                $variantMatch = $productMatch
                    || ($needle !== '' && (
                        str_contains(mb_strtolower((string) $variant->name), $needle)
                        || str_contains(mb_strtolower((string) $variant->sku), $needle)
                    ));

                if (!$variantMatch) {
                    continue;
                }

                $items[] = [
                    'key' => 'v-' . $variant->id,
                    'type' => 'variant',
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'name' => $product->name . ' - ' . $variant->name,
                    'sku' => $variant->sku ?: $product->sku,
                    'price' => (float) $variant->price,
                    'stock' => (int) $variant->stock,
                ];
            }
        }

        return response()->json(['items' => $items]);
    }

    /**
     * Bayar transaksi: potong stok jual + buku gudang + tulis mutasi.
     */
    public function checkout(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.variant_id' => 'nullable|integer|exists:variants,id',
            'items.*.quantity' => 'required|integer|min:1|max:999',
            'discount' => 'nullable|numeric|min:0|max:1000000000',
            'paid_amount' => 'required|numeric|min:0|max:1000000000',
            'payment_method' => 'required|in:' . implode(',', PosSale::PAYMENT_METHODS),
            'customer_name' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:300',
        ]);

        $sale = $this->pos->checkout(
            collect($data['items'])->map(fn ($item) => [
                'product_id' => $item['product_id'] ?? null,
                'variant_id' => $item['variant_id'] ?? null,
                'quantity' => (int) $item['quantity'],
            ])->all(),
            [
                'discount' => (float) ($data['discount'] ?? 0),
                'paid_amount' => (float) $data['paid_amount'],
                'payment_method' => $data['payment_method'],
                'customer_name' => $data['customer_name'] ?? null,
                'notes' => $data['notes'] ?? null,
            ],
            $request->user()
        );

        return redirect()
            ->route('admin.pos.receipt', $sale)
            ->with('success', 'Transaksi ' . $sale->invoice . ' tersimpan.');
    }

    /**
     * Struk (siap cetak).
     */
    public function receipt(PosSale $posSale): View
    {
        $posSale->load(['user', 'items']);

        return view('admin.pos.receipt', ['sale' => $posSale]);
    }
}
