<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class OrderExpiryTest extends TestCase
{
    use RefreshDatabase;

    protected function placeOrder(array $overrides = []): array
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create([
            'name' => 'Produk Expiry',
            'sku' => 'EXPIRY-001',
            'price' => 100000,
            'stock' => 5,
            'unit' => 'pcs',
            'is_active' => true,
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

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 2]);
        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'shipping_method' => 'regular',
            'payment_method' => 'bca_va',
        ]);

        return [$user, $user->orders()->firstOrFail(), $product];
    }

    public function test_checkout_records_out_log_with_waiting_for_checkout_status(): void
    {
        [, $order, $product] = $this->placeOrder();

        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_mutations', [
            'type' => 'out',
            'status' => StockMutation::STATUS_WAITING,
            'quantity' => 2,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'created_by' => $order->user_id,
        ]);

        $log = StockMutation::where('reference_id', $order->id)->first();
        $this->assertStringContainsString('OUT - waiting for checkout', $log->note);
    }

    public function test_order_is_automatically_cancelled_after_30_minutes_and_stock_returns(): void
    {
        [, $order, $product] = $this->placeOrder();

        // Belum 30 menit -> tidak dibatalkan.
        Artisan::call('orders:expire-unpaid');
        $this->assertSame('pending', $order->fresh()->status);

        // Lewat 30 menit -> dibatalkan otomatis.
        Order::whereKey($order->id)->update(['created_at' => now()->subMinutes(31)]);
        Artisan::call('orders:expire-unpaid');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->shipment()->first()->status);

        // Log IN - cancel transaction + OUT ditutup.
        $this->assertDatabaseHas('stock_mutations', [
            'type' => 'in',
            'status' => StockMutation::STATUS_CANCELLED,
            'quantity' => 2,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
        $in = StockMutation::where('reference_id', $order->id)->where('type', 'in')->first();
        $this->assertStringContainsString('IN - cancel transaction', $in->note);
        $this->assertStringContainsString('lewat 30 menit', $in->note);

        $out = StockMutation::where('reference_id', $order->id)->where('type', 'out')->first();
        $this->assertSame(StockMutation::STATUS_CANCELLED, $out->status);

        // Aman dijalankan dua kali (idempoten).
        Artisan::call('orders:expire-unpaid');
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(1, StockMutation::where('reference_id', $order->id)->where('type', 'in')->count());
    }

    public function test_paid_order_marks_stock_log_completed_and_keeps_stock(): void
    {
        [, $order, $product] = $this->placeOrder();
        $admin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($admin)->post(route('admin.orders.pay', $order))->assertSessionHas('success');

        $out = StockMutation::where('reference_id', $order->id)->where('type', 'out')->first();
        $this->assertSame(StockMutation::STATUS_COMPLETED, $out->status);
        $this->assertStringContainsString('dibayar', $out->note);

        $this->assertSame(3, $product->fresh()->stock);
        $this->assertSame(0, StockMutation::where('reference_id', $order->id)->where('type', 'in')->count());
    }

    public function test_payment_page_shows_cancelled_page_instead_of_404(): void
    {
        [$user, $order] = $this->placeOrder();
        Order::whereKey($order->id)->update(['created_at' => now()->subMinutes(31)]);
        Artisan::call('orders:expire-unpaid');

        $this->actingAs($user)->get(route('payment.index', $order))
            ->assertOk()
            ->assertSee('Pesanan Dibatalkan');

        $this->actingAs($user)->get(route('payment.finish', $order))
            ->assertOk()
            ->assertSee('Pesanan Dibatalkan')
            ->assertDontSee('Bayar Sekarang');
    }

    public function test_admin_cancel_and_order_detail_expose_stock_log(): void
    {
        [, $order] = $this->placeOrder();
        $admin = User::factory()->create(['role' => 'superadmin']);

        $this->actingAs($admin)->post(route('admin.orders.cancel', $order))->assertSessionHas('success');

        $out = StockMutation::where('reference_id', $order->id)->where('type', 'out')->first();
        $this->assertSame(StockMutation::STATUS_CANCELLED, $out->status);

        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Log Stok Pesanan')
            ->assertSee('Cancel Transaction');
    }

    public function test_stock_log_page_is_admin_only(): void
    {
        [, $order] = $this->placeOrder();
        $admin = User::factory()->create(['role' => 'superadmin']);
        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($customer)->get(route('admin.stock-logs.index'))->assertStatus(403);

        $this->actingAs($admin)->get(route('admin.stock-logs.index'))
            ->assertOk()
            ->assertSee('Log Keluar Masuk Barang')
            ->assertSee('Waiting for Checkout');

        $this->actingAs($admin)->get(route('admin.stock-logs.datatable'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1)
            ->assertJsonFragment(['status_badge' => '<span class="badge badge-warning">Waiting for Checkout</span>']);

        $this->actingAs($admin)->get(route('admin.stock-logs.datatable', ['status' => 'completed']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 0);

        $this->actingAs($admin)->get(route('admin.stock-logs.datatable', ['status' => 'waiting_for_checkout']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1);
    }
}
