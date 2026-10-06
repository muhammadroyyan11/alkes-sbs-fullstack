<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(array $overrides = []): Order
    {
        $user = User::factory()->create(['role' => 'user']);
        $address = Address::create([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => $user->name,
            'phone' => '081234567890',
            'address' => 'Jl. Test 1',
            'city' => 'Malang',
            'province' => 'Jawa Timur',
            'postal_code' => '65100',
            'is_primary' => true,
        ]);
        $product = Product::firstOrCreate(
            ['sku' => 'WEBHOOK-001'],
            [
                'name' => 'Produk Webhook',
                'price' => 100000,
                'stock' => 8,
                'unit' => 'pcs',
                'is_active' => true,
            ]
        );

        $order = Order::create(array_merge([
            'order_number' => 'SBS-TEST-' . uniqid(),
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'bca_va',
            'shipping_method' => 'regular',
            'subtotal' => 200000,
            'shipping_cost' => 20000,
            'total' => 220000,
            'shipping_address' => 'Jl. Test 1, Malang, Jawa Timur',
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'price' => 100000,
            'quantity' => 2,
            'subtotal' => 200000,
        ]);
        $order->shipment()->create([
            'courier' => 'RajaOngkir',
            'service' => 'regular',
            'status' => 'waiting',
        ]);

        // stok sudah dikurangi saat checkout
        $product->decrement('stock', 2);

        return $order;
    }

    protected function signedPayload(Order $order, array $overrides = []): array
    {
        $payload = array_merge([
            'order_id' => $order->order_number,
            'status_code' => '200',
            'gross_amount' => number_format((float) $order->total, 2, '.', ''),
            'transaction_status' => 'settlement',
            'transaction_id' => 'txn-' . uniqid(),
            'payment_type' => 'bank_transfer',
            'fraud_status' => 'accept',
        ], $overrides);

        $payload['signature_key'] = hash(
            'sha512',
            $payload['order_id'] . $payload['status_code'] . $payload['gross_amount'] . config('midtrans.server_key')
        );

        return $payload;
    }

    public function test_webhook_settlement_marks_order_paid_and_confirmed(): void
    {
        $order = $this->makeOrder();
        $product = Product::where('sku', 'WEBHOOK-001')->first();

        $this->postJson('/payment/callback', $this->signedPayload($order))
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertStringStartsWith('txn-', $order->payment_reference);
        // stok tetap 6 (dikurangi 2 saat checkout, tidak dikembalikan saat lunas)
        $this->assertSame(6, $product->fresh()->stock);
    }

    public function test_webhook_capture_with_fraud_accept_marks_order_paid(): void
    {
        $order = $this->makeOrder();

        $this->postJson('/payment/callback', $this->signedPayload($order, [
            'transaction_status' => 'capture',
            'fraud_status' => 'accept',
        ]))->assertOk();

        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_webhook_is_idempotent_on_duplicate_notification(): void
    {
        $order = $this->makeOrder();

        $payload = $this->signedPayload($order);
        $this->postJson('/payment/callback', $payload)->assertOk();
        $paidAt = $order->fresh()->paid_at;

        // notifikasi duplikat tidak mengubah apa pun
        $this->postJson('/payment/callback', $payload)->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals($paidAt, $order->paid_at);
        $this->assertSame('confirmed', $order->status);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $order = $this->makeOrder();

        $payload = $this->signedPayload($order);
        $payload['signature_key'] = str_repeat('0', 128);

        $this->postJson('/payment/callback', $payload)->assertStatus(403);

        $order->refresh();
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertNull($order->paid_at);
    }

    public function test_webhook_with_wrong_amount_does_not_mark_paid(): void
    {
        $order = $this->makeOrder();

        $payload = $this->signedPayload($order, ['gross_amount' => '1.00']);

        $this->postJson('/payment/callback', $payload)
            ->assertOk()
            ->assertJson(['status' => 'error']);

        $this->assertSame('unpaid', $order->fresh()->payment_status);
    }

    public function test_webhook_cancel_cancels_order_and_restores_stock_once(): void
    {
        $order = $this->makeOrder();
        $product = Product::where('sku', 'WEBHOOK-001')->first();
        $this->assertSame(6, $product->fresh()->stock);

        $payload = $this->signedPayload($order, [
            'transaction_status' => 'cancel',
            'status_code' => '400',
        ]);
        $this->postJson('/payment/callback', $payload)->assertOk();

        $order->refresh();
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame('cancelled', $order->status);
        $this->assertSame(8, $product->fresh()->stock);

        // notifikasi ulang tidak mengembalikan stok dua kali
        $this->postJson('/payment/callback', $payload)->assertOk();
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_webhook_for_unknown_order_returns_404(): void
    {
        $order = $this->makeOrder();

        $payload = $this->signedPayload($order, ['order_id' => 'SBS-TIDAK-ADA']);

        $this->postJson('/payment/callback', $payload)->assertStatus(404);
    }

    public function test_webhook_ignores_late_pending_notification_after_settlement(): void
    {
        $order = $this->makeOrder();

        $this->postJson('/payment/callback', $this->signedPayload($order))->assertOk();
        $paidAt = $order->fresh()->paid_at;

        $this->postJson('/payment/callback', $this->signedPayload($order, [
            'transaction_status' => 'pending',
            'fraud_status' => null,
        ]))->assertOk();

        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertEquals($paidAt, $order->paid_at);
    }
}
