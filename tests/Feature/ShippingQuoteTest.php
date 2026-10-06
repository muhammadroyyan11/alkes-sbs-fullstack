<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingQuoteTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function addressFor(User $user, string $city = 'Kota Malang'): Address
    {
        return Address::create([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => $user->name,
            'phone' => '08123',
            'address' => 'Jl. Test 1',
            'city' => $city,
            'province' => 'Jawa Timur',
            'postal_code' => '65100',
            'is_primary' => true,
        ]);
    }

    private function product(int $stock = 10): Product
    {
        return Product::create([
            'name' => 'Produk Ongkir', 'sku' => 'ONGKIR-001', 'price' => 25000,
            'stock' => $stock, 'unit' => 'pcs', 'is_active' => true,
        ]);
    }

    private function fakeRajaOngkir(): void
    {
        config(['rajaongkir.api_key' => 'test-key', 'rajaongkir.type' => 'starter']);

        Http::fake([
            'api.rajaongkir.com/starter/city' => Http::response([
                'rajaongkir' => ['results' => [
                    ['city_id' => '487', 'city_name' => 'Kota Surabaya'],
                    ['city_id' => '501', 'city_name' => 'Kota Malang'],
                ]],
            ]),
            'api.rajaongkir.com/starter/cost' => Http::response([
                'rajaongkir' => ['results' => [[
                    'costs' => [
                        ['service' => 'REG', 'description' => 'Reguler', 'cost' => [['value' => 30000, 'etd' => '2-3']]],
                        ['service' => 'YES', 'description' => 'Yakin Esok Sampai', 'cost' => [['value' => 55000, 'etd' => '1-2']]],
                    ],
                ]]],
            ]),
        ]);
    }

    public function test_quote_endpoint_returns_actual_costs_and_stores_them_in_session(): void
    {
        $this->fakeRajaOngkir();

        $user = $this->customer();
        $address = $this->addressFor($user);

        $this->actingAs($user)->post(route('cart.store', $this->product()), ['quantity' => 2]);

        $response = $this->postJson(route('shipping.quote'), ['address_id' => $address->id]);
        $response->assertOk();

        $options = collect($response->json('options'));
        $this->assertSame(['jne-REG', 'jne-YES', 'instant'], $options->pluck('code')->all());
        $this->assertSame(30000, $options->firstWhere('code', 'jne-REG')['cost']);
        $this->assertFalse($options->firstWhere('code', 'jne-REG')['fallback']);
        $this->assertSame(2000, $response->json('weight'));

        $response->assertSessionHas('shipping_quotes.' . $address->id);
    }

    public function test_checkout_uses_quoted_cost_from_session(): void
    {
        $this->fakeRajaOngkir();

        $user = $this->customer();
        $address = $this->addressFor($user);
        $product = $this->product();

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 1]);
        $this->postJson(route('shipping.quote'), ['address_id' => $address->id])->assertOk();

        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'shipping_method' => 'regular',
            'shipping_option' => 'jne-YES',
            'payment_method' => 'bca_va',
        ]);

        $order = $user->orders()->firstOrFail();

        $this->assertSame(55000.0, (float) $order->shipping_cost);
        // Subtotal 25.000 + ongkir 55.000 + biaya admin 1.000
        $this->assertSame(81000.0, (float) $order->total);
        $this->assertSame('JNE', $order->shipment()->first()->courier);
        $this->assertSame('YES', $order->shipment()->first()->service);
    }

    public function test_checkout_without_api_falls_back_to_admin_settings(): void
    {
        Setting::putMany([
            'shipping_regular_cost' => 17500,
            'shipping_instant_cost' => 42000,
        ]);

        $user = $this->customer();
        $address = $this->addressFor($user);

        $this->actingAs($user)->post(route('cart.store', $this->product()), ['quantity' => 1]);

        $response = $this->postJson(route('shipping.quote'), ['address_id' => $address->id]);
        $response->assertOk();

        $options = collect($response->json('options'));
        $this->assertSame(17500, $options->firstWhere('code', 'regular')['cost']);
        $this->assertTrue($options->firstWhere('code', 'regular')['fallback']);
        $this->assertSame(42000, $options->firstWhere('code', 'instant')['cost']);

        // Opsi tak dikenal tidak dipercaya: tetap memakai tarif hasil hitung server.
        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'shipping_method' => 'regular',
            'shipping_option' => 'jne-REG',
            'payment_method' => 'bca_va',
        ]);

        $order = $user->orders()->firstOrFail();
        $this->assertSame(17500.0, (float) $order->shipping_cost);
    }

    public function test_instant_checkout_uses_configured_gosend_cost(): void
    {
        Setting::putMany(['shipping_instant_cost' => 48000]);

        $user = $this->customer();
        $address = $this->addressFor($user);

        $this->actingAs($user)->post(route('cart.store', $this->product()), ['quantity' => 1]);
        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'shipping_method' => 'instant',
            'payment_method' => 'bca_va',
        ]);

        $order = $user->orders()->firstOrFail();
        $this->assertSame(48000.0, (float) $order->shipping_cost);
        $this->assertSame('GoSend', $order->shipment()->first()->courier);
    }

    public function test_quote_requires_authentication_and_own_address(): void
    {
        $owner = $this->customer();
        $address = $this->addressFor($owner);
        $other = $this->customer();

        $this->postJson(route('shipping.quote'), ['address_id' => $address->id])->assertUnauthorized();

        $this->actingAs($other)
            ->postJson(route('shipping.quote'), ['address_id' => $address->id])
            ->assertNotFound();
    }
}
