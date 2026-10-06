<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFeeTest extends TestCase
{
    use RefreshDatabase;

    private function customer(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    private function placeOrder(User $user, Product $product, Address $address, string $method = 'regular'): \App\Models\Order
    {
        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 2]);
        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'shipping_method' => $method,
            'payment_method' => 'bca_va',
        ]);

        return $user->orders()->latest('id')->firstOrFail();
    }

    public function test_cart_and_checkout_show_default_admin_fee(): void
    {
        $user = $this->customer();
        $product = Product::create([
            'name' => 'Produk Biaya', 'sku' => 'FEE-001', 'price' => 50000,
            'stock' => 10, 'unit' => 'pcs', 'is_active' => true,
        ]);

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 1]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Biaya Admin')
            ->assertSee('Rp 1.000');

        $address = Address::create([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => $user->name,
            'phone' => '08123',
            'address' => 'Jl. Test 1',
            'city' => 'Malang',
            'province' => 'Jawa Timur',
            'postal_code' => '65100',
            'is_primary' => true,
        ]);

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('Biaya Admin');
    }

    public function test_checkout_saves_admin_fee_and_includes_it_in_total(): void
    {
        $user = $this->customer();
        $product = Product::create([
            'name' => 'Produk Biaya 2', 'sku' => 'FEE-002', 'price' => 50000,
            'stock' => 10, 'unit' => 'pcs', 'is_active' => true,
        ]);
        $address = Address::create([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => $user->name,
            'phone' => '08123',
            'address' => 'Jl. Test 1',
            'city' => 'Malang',
            'province' => 'Jawa Timur',
            'postal_code' => '65100',
            'is_primary' => true,
        ]);

        $order = $this->placeOrder($user, $product, $address);

        // Subtotal 100.000 + ongkir 20.000 + biaya admin 1.000
        $this->assertSame(100000.0, (float) $order->subtotal);
        $this->assertSame(20000.0, (float) $order->shipping_cost);
        $this->assertSame(1000.0, (float) $order->admin_fee);
        $this->assertSame(121000.0, (float) $order->total);

        $this->get(route('payment.index', $order))
            ->assertOk()
            ->assertSee('Biaya Admin')
            ->assertSee('Rp 1.000');
    }

    public function test_admin_changed_fee_and_shipping_costs_are_used_on_checkout(): void
    {
        Setting::putMany([
            'admin_fee' => 5000,
            'admin_fee_label' => 'Biaya Layanan',
            'shipping_regular_cost' => 15000,
            'shipping_instant_cost' => 45000,
        ]);

        $user = $this->customer();
        $product = Product::create([
            'name' => 'Produk Biaya 3', 'sku' => 'FEE-003', 'price' => 10000,
            'stock' => 10, 'unit' => 'pcs', 'is_active' => true,
        ]);
        $address = Address::create([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => $user->name,
            'phone' => '08123',
            'address' => 'Jl. Test 1',
            'city' => 'Malang',
            'province' => 'Jawa Timur',
            'postal_code' => '65100',
            'is_primary' => true,
        ]);

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 1]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Biaya Layanan')
            ->assertSee('Rp 5.000');

        $order = $this->placeOrder($user, $product, $address);

        // Subtotal 30.000 (3 x 10.000) + ongkir 15.000 + biaya admin 5.000
        $this->assertSame(30000.0, (float) $order->subtotal);
        $this->assertSame(5000.0, (float) $order->admin_fee);
        $this->assertSame(15000.0, (float) $order->shipping_cost);
        $this->assertSame(50000.0, (float) $order->total);
    }
}
