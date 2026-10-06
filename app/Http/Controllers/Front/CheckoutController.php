<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Variant;
use App\Models\Setting;
use App\Services\MidtransService;
use App\Services\ShippingQuoteService;
use App\Services\StockLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(protected MidtransService $midtrans)
    {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        if (empty($cart)) {
            return redirect()->route('cart.index')->with('error', 'Keranjang masih kosong.');
        }
        $addresses = $request->user()->addresses()->orderByDesc('is_primary')->get();

        return view('front.checkout.index', [
            'cart' => $cart,
            'addresses' => $addresses,
            'adminFee' => max(0, Setting::int('admin_fee')),
            'adminFeeLabel' => Setting::get('admin_fee_label', 'Biaya Admin'),
            'regularCost' => max(0, Setting::int('shipping_regular_cost')),
            'instantCost' => max(0, Setting::int('shipping_instant_cost')),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'address_id' => 'required|exists:addresses,id',
            'shipping_method' => 'required|in:regular,instant',
            'shipping_option' => 'nullable|string|max:60',
            'payment_method' => 'required|in:bca_va,bri_va,mandiri_va,bni_va,permata_va,qris,gopay,shopeepay,dana,alfamart,indomaret,credit_card',
            'notes' => 'nullable|string|max:500',
        ]);

        $address = Address::whereKey($data['address_id'])->where('user_id', $request->user()->id)->firstOrFail();
        $cart = $request->session()->get('cart', []);

        if (empty($cart)) {
            return redirect()->route('cart.index');
        }

        // Tarif dihitung di server (sesi quote / RajaOngkir / pengaturan admin),
        // tidak pernah dipercaya dari input klien.
        $shippingOption = $this->resolveShippingOption($request, $data, $address, $cart);

        $order = DB::transaction(function () use ($cart, $data, $address, $request, $shippingOption) {
            $subtotal = collect($cart)->sum(fn ($i) => $i['price'] * $i['quantity']);
            $shipping = (int) $shippingOption['cost'];
            $adminFee = max(0, Setting::int('admin_fee'));

            $order = Order::create([
                'order_number' => 'SBS-' . now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'user_id' => $request->user()->id,
                'address_id' => $address->id,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'payment_method' => $data['payment_method'],
                'shipping_method' => $data['shipping_method'],
                'subtotal' => $subtotal,
                'shipping_cost' => $shipping,
                'admin_fee' => $adminFee,
                'total' => $subtotal + $shipping + $adminFee,
                'shipping_address' => implode(', ', [
                    $address->recipient_name,
                    $address->phone,
                    $address->address,
                    $address->city,
                    $address->province,
                    $address->postal_code,
                ]),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($cart as $item) {
                $model = $item['variant_id']
                    ? Variant::lockForUpdate()->find($item['variant_id'])
                    : Product::lockForUpdate()->find($item['product_id']);

                if (!$model || $model->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => 'Stok ' . $item['name'] . ' tidak mencukupi.',
                    ]);
                }

                $model->decrement('stock', $item['quantity']);

                // Log keluar barang: stok ditahan untuk pesanan ini
                // sampai dibayar (completed) atau dibatalkan (cancel transaction).
                app(StockLogService::class)->recordOut(
                    $order,
                    $item['variant_id'] ?? null,
                    $item['product_id'],
                    (int) $item['quantity'],
                    (int) $model->stock,
                    $request->user()->id
                );

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'product_name' => $item['name'] . ($item['variant_name'] ? ' - ' . $item['variant_name'] : ''),
                    'sku' => $item['sku'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['price'] * $item['quantity'],
                ]);
            }

            $order->shipment()->create([
                'courier' => $shippingOption['courier'],
                'service' => $shippingOption['service'],
                'status' => 'waiting',
            ]);

            return $order;
        });

        $request->session()->forget('cart');

        $snapToken = $this->midtrans->createSnapToken($order, $data['payment_method']);
        if ($snapToken) {
            $order->update(['snap_token' => $snapToken]);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'snap_token' => $snapToken,
                'order_number' => $order->order_number,
            ]);
        }

        return redirect()->route('payment.index', $order)->with('success', 'Pesanan berhasil dibuat!');
    }

    /**
     * Ambil opsi pengiriman yang dipilih: cocokkan kode opsi dengan quote
     * yang tersimpan di sesi; bila tidak ada, hitung ulang lewat RajaOngkir.
     */
    private function resolveShippingOption(Request $request, array $data, Address $address, array $cart): array
    {
        $quotes = app(ShippingQuoteService::class);

        if ($data['shipping_method'] === 'instant') {
            // Sakelar/area instan bisa berubah setelah sesi quote dibuat — cek ulang di server.
            return $quotes->instantAvailableFor($address)
                ? $quotes->instantOption()
                : $quotes->fallbackOption();
        }

        $code = $data['shipping_option'] ?? null;
        $sessionQuotes = (array) $request->session()->get('shipping_quotes.' . $address->id, []);

        if ($code !== null && isset($sessionQuotes[$code]) && is_array($sessionQuotes[$code])) {
            $stored = $sessionQuotes[$code];

            return [
                'code' => $code,
                'courier' => (string) ($stored['courier'] ?? 'RajaOngkir'),
                'service' => (string) ($stored['service'] ?? 'regular'),
                'label' => 'Reguler',
                'etd' => '',
                'cost' => max(0, (int) ($stored['cost'] ?? 0)),
                'fallback' => false,
            ];
        }

        $options = $quotes->regularOptions($address, $quotes->weightForCart($cart));

        if ($code !== null) {
            $matched = collect($options)->firstWhere('code', $code);
            if ($matched) {
                return $matched;
            }
        }

        return $options[0];
    }
}
