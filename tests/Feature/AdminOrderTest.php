<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminOrderTest extends TestCase
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
            'address' => 'Jl. Test 2',
            'city' => 'Malang',
            'province' => 'Jawa Timur',
            'postal_code' => '65100',
            'is_primary' => true,
        ]);
        $product = Product::firstOrCreate(
            ['sku' => 'ADMIN-ORDER-001'],
            [
                'name' => 'Produk Admin Order',
                'price' => 150000,
                'stock' => 10,
                'unit' => 'pcs',
                'is_active' => true,
            ]
        );

        $order = Order::create(array_merge([
            'order_number' => 'SBS-ADM-' . uniqid(),
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'qris',
            'shipping_method' => 'regular',
            'subtotal' => 300000,
            'shipping_cost' => 20000,
            'total' => 320000,
            'shipping_address' => 'Jl. Test 2, Malang, Jawa Timur',
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'price' => 150000,
            'quantity' => 2,
            'subtotal' => 300000,
        ]);
        $order->shipment()->create([
            'courier' => 'RajaOngkir',
            'service' => 'regular',
            'status' => 'waiting',
        ]);

        $product->decrement('stock', 2);

        return $order;
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => 'superadmin']);
    }

    public function test_orders_menu_and_pages_are_accessible_for_admin(): void
    {
        $order = $this->makeOrder();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Daftar Pesanan');

        $this->actingAs($admin)->get(route('admin.orders.datatable'))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 1);

        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Produk Admin Order');
    }

    public function test_non_admin_cannot_access_order_pages(): void
    {
        $order = $this->makeOrder();
        $customer = User::factory()->create(['role' => 'user']);

        $this->actingAs($customer)->get(route('admin.orders.index'))->assertStatus(403);
        $this->actingAs($customer)->get(route('admin.orders.show', $order))->assertStatus(403);
    }

    public function test_admin_flow_mark_paid_process_ship_complete(): void
    {
        $order = $this->makeOrder();
        $admin = $this->admin();

        // 1. belum lunas → tidak boleh diproses
        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertSessionHasErrors('status');
        $this->assertSame('pending', $order->fresh()->status);

        // 2. konfirmasi pembayaran manual
        $this->actingAs($admin)->post(route('admin.orders.pay', $order))
            ->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('confirmed', $order->status);
        $this->assertNotNull($order->paid_at);

        // 3. mulai diproses
        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertSessionHas('success');
        $this->assertSame('processing', $order->fresh()->status);

        // 4. kirim dengan resi
        $this->actingAs($admin)
            ->post(route('admin.orders.ship', $order), [
                'tracking_number' => 'JNE-001234',
                'courier' => 'JNE',
            ])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('shipped', $order->status);
        $this->assertSame('JNE-001234', $order->shipment->tracking_number);
        $this->assertSame('shipped', $order->shipment->status);
        $this->assertNotNull($order->shipment->shipped_at);

        // 5. selesaikan
        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['status' => 'completed'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('delivered', $order->shipment->status);
        $this->assertNotNull($order->shipment->delivered_at);
    }

    public function test_admin_cannot_ship_without_tracking_number_or_when_unpaid(): void
    {
        $order = $this->makeOrder();
        $admin = $this->admin();

        // belum bayar → ditolak
        $this->actingAs($admin)
            ->post(route('admin.orders.ship', $order), ['tracking_number' => 'RESI-1'])
            ->assertSessionHasErrors('tracking_number');

        // bayar dulu, tapi resi kosong → validasi error
        $this->actingAs($admin)->post(route('admin.orders.pay', $order));
        $this->actingAs($admin)
            ->post(route('admin.orders.ship', $order), ['tracking_number' => ''])
            ->assertSessionHasErrors('tracking_number');

        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_summary_queue_shows_orders_to_process_today_and_sla_overdue(): void
    {
        $admin = $this->admin();

        // lunas, belum dikirim → siap diproses hari ini
        $ready = $this->makeOrder(['payment_status' => 'paid', 'status' => 'confirmed']);

        // lunas tapi mengendap 2 hari → lewat SLA 1 hari
        // (order BELUM BAYAR tidak akan pernah sampai sini: auto-cancel 30 menit)
        $overdue = $this->makeOrder(['payment_status' => 'paid', 'status' => 'confirmed']);
        $overdue->created_at = now()->subDays(2);
        $overdue->save();

        // sudah dikirim → tidak masuk antrean sama sekali
        $shipped = $this->makeOrder(['payment_status' => 'paid', 'status' => 'shipped']);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));
        $response->assertOk()
            ->assertSee('Antrean Proses Hari Ini')
            ->assertSee('Siap Diproses Hari Ini')
            ->assertSee('Lewat 1 Hari')
            ->assertSee($ready->order_number)
            ->assertSee($overdue->order_number)
            ->assertDontSee($shipped->order_number);

        // filter datatable: hanya yang lewat SLA
        $this->actingAs($admin)
            ->get(route('admin.orders.datatable', ['filter' => 'overdue']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonFragment(['order_number' => '<strong>' . $overdue->order_number . '</strong>']);

        // filter: lunas & belum dikirim (termasuk yang lewat SLA)
        $this->actingAs($admin)
            ->get(route('admin.orders.datatable', ['filter' => 'need_process']))
            ->assertOk()
            ->assertJsonPath('recordsFiltered', 2);

        // filter tidak dikenal → diabaikan, semua pesanan tampil
        $this->actingAs($admin)
            ->get(route('admin.orders.datatable', ['filter' => 'ngasal']))
            ->assertOk()
            ->assertJsonPath('recordsTotal', 3);
    }

    public function test_order_age_and_sla_helpers(): void
    {
        $fresh = $this->makeOrder();
        $this->assertFalse($fresh->isOverdue());
        $this->assertStringContainsString('menit', $fresh->ageLabel());

        $old = $this->makeOrder();
        $old->created_at = now()->subHours(30);
        $old->save();
        $this->assertTrue($old->isOverdue());
        $this->assertStringContainsString('1 hari', $old->ageLabel());
        $this->assertSame('Lewat 6 jam', $old->slaLabel());

        $old->status = 'shipped';
        $old->save();
        $this->assertFalse($old->isOverdue());

        // banner peringatan tampil di halaman detail
        $late = $this->makeOrder();
        $late->created_at = now()->subHours(30);
        $late->save();
        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $late))
            ->assertOk()
            ->assertSee('Lewat 1 Hari!', false);
    }

    public function test_admin_cancel_restores_stock_and_shipped_order_cannot_be_cancelled(): void
    {
        $order = $this->makeOrder();
        $product = Product::where('sku', 'ADMIN-ORDER-001')->first();
        $admin = $this->admin();

        $this->assertSame(8, $product->fresh()->stock);
        $this->actingAs($admin)->post(route('admin.orders.cancel', $order))
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('failed', $order->payment_status);
        $this->assertSame(10, $product->fresh()->stock);

        // pesanan sudah dibatalkan → aksi lain tidak tersedia
        $this->actingAs($admin)
            ->post(route('admin.orders.status', $order), ['status' => 'processing'])
            ->assertSessionHasErrors('status');

        // pesanan terkirim tidak boleh dibatalkan
        $shipped = $this->makeOrder(['status' => 'shipped', 'payment_status' => 'paid']);
        $this->actingAs($admin)->post(route('admin.orders.cancel', $shipped))
            ->assertSessionHasErrors('status');
        $this->assertSame('shipped', $shipped->fresh()->status);
    }
}
