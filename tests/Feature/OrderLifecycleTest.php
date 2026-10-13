<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\User;
use App\Services\OrderAutoComplete;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class OrderLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function customer(string $email): User
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true, 'email' => $email]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function product(int $stock = 10): Product
    {
        // Satu produk yang dipakai ulang: stok & buku gudang disetel ulang tiap kali.
        $product = Product::firstOrCreate(
            ['sku' => 'LIFE-001'],
            ['name' => 'Produk Lifecycle', 'price' => 25000, 'stock' => $stock, 'unit' => 'pcs', 'is_active' => true]
        );

        $product->update(['stock' => $stock]);
        Stock::updateOrCreate(
            ['product_id' => $product->id, 'variant_id' => null],
            ['quantity' => $stock]
        );

        return $product;
    }

    /**
     * Pesanan lunas + dikirim, lengkap dengan item, shipment, dan buku stok.
     */
    private function shippedOrder(User $owner, array $overrides = [], ?\DateTimeInterface $shippedAt = null): Order
    {
        $product = $this->product();

        $order = Order::create(array_merge([
            'order_number' => 'SBS-LIFE-' . strtoupper(substr(uniqid(), -6)),
            'user_id' => $owner->id,
            'status' => 'shipped',
            'payment_status' => 'paid',
            'payment_method' => 'bca_va',
            'shipping_method' => 'regular',
            'subtotal' => 50000,
            'shipping_cost' => 20000,
            'admin_fee' => 1000,
            'total' => 71000,
            'shipping_address' => 'Jl. Test 1, Malang',
            'paid_at' => now(),
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name,
            'sku' => $product->sku,
            'price' => 25000,
            'quantity' => 2,
            'subtotal' => 50000,
        ]);

        $order->shipment()->create([
            'courier' => 'JNE',
            'service' => 'REG',
            'tracking_number' => 'JNE-001',
            'status' => 'shipped',
            'shipped_at' => $shippedAt ?: now()->subDay(),
        ]);

        // Simulasikan stok sudah terpotong saat checkout + dibayar.
        $product->decrement('stock', 2);
        $book = Stock::where('product_id', $product->id)->whereNull('variant_id')->firstOrFail();
        $book->decrement('quantity', 2);
        $book->mutations()->create([
            'type' => 'out',
            'status' => StockMutation::STATUS_COMPLETED,
            'quantity' => 2,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'note' => 'OUT - completed (dibayar): ' . $order->order_number,
            'created_by' => $owner->id,
        ]);

        return $order;
    }

    // ── 1. Customer: "Pesanan Diterima" ──────────────────────────

    public function test_customer_can_mark_own_shipped_order_as_received(): void
    {
        $owner = $this->customer('penerima@mail.test');
        $order = $this->shippedOrder($owner);

        $this->actingAs($owner)
            ->post(route('orders.receive', $order))
            ->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertSame('delivered', $order->shipment->status);
        $this->assertNotNull($order->shipment->delivered_at);

        // Konfirmasi dua kali aman (idempoten): stok tidak bergerak sama sekali
        // (stok sudah terpotong sejak checkout — di sini 8).
        $product = Product::where('sku', 'LIFE-001')->firstOrFail();
        $stockBefore = (int) $product->stock;

        $this->actingAs($owner)->post(route('orders.receive', $order))->assertSessionHas('success');

        $this->assertSame($stockBefore, (int) $product->refresh()->stock);
        $this->assertSame('completed', $order->refresh()->status);
    }

    public function test_receive_is_protected_by_owner_and_status(): void
    {
        $owner = $this->customer('pemilik@mail.test');
        $other = $this->customer('asing@mail.test');
        $order = $this->shippedOrder($owner);

        // Boleh dilihat, tapi tidak boleh dikonfirmasi orang lain.
        $this->actingAs($other)->get(route('orders.show', $order))->assertForbidden();
        $this->actingAs($other)->post(route('orders.receive', $order))->assertForbidden();

        // Status belum dikirim -> ditolak.
        $pending = $this->shippedOrder($owner, ['status' => 'confirmed', 'payment_status' => 'paid']);
        $this->actingAs($owner)
            ->post(route('orders.receive', $pending))
            ->assertSessionHas('error');

        $this->assertSame('confirmed', $pending->refresh()->status);
    }

    // ── 2. Auto-complete N hari ──────────────────────────────────

    public function test_auto_complete_runs_after_configured_days(): void
    {
        Setting::putMany(['auto_complete_days' => '3']);

        $owner = $this->customer('auto@mail.test');
        $old = $this->shippedOrder($owner, [], now()->subDays(4));   // lewat 3 hari
        $fresh = $this->shippedOrder($owner, [], now()->subDay());   // masih dalam batas

        $this->artisan('orders:auto-complete')->assertSuccessful();

        $this->assertSame('completed', $old->refresh()->status);
        $this->assertSame('shipped', $fresh->refresh()->status);
    }

    public function test_auto_complete_can_be_disabled_from_settings(): void
    {
        Setting::putMany(['auto_complete_days' => '0']);

        $owner = $this->customer('mati@mail.test');
        $order = $this->shippedOrder($owner, [], now()->subDays(30));

        $this->artisan('orders:auto-complete')->assertSuccessful();
        $this->assertSame('shipped', $order->refresh()->status);

        // Web fallback service juga tidak menjalankan apa pun saat dimatikan.
        Setting::putMany(['auto_complete_days' => '0']);
        OrderAutoComplete::run();
        $this->assertSame('shipped', $order->refresh()->status);
    }

    public function test_auto_complete_service_fallback_completes_old_orders(): void
    {
        Setting::putMany(['auto_complete_days' => '3']);

        $owner = $this->customer('fallback@mail.test');
        $order = $this->shippedOrder($owner, [], now()->subDays(5));

        OrderAutoComplete::run();

        $this->assertSame('completed', $order->refresh()->status);
    }

    // ── 3. Refund ────────────────────────────────────────────────

    public function test_refund_returns_stock_and_records_who_when_why(): void
    {
        $owner = $this->customer('refund@mail.test');
        $admin = $this->admin();
        $order = $this->shippedOrder($owner);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order), [
                'refund_reason' => 'Barang rusak saat diterima',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame('refunded', $order->status);
        $this->assertSame('refunded', $order->payment_status);
        $this->assertNotNull($order->refunded_at);
        $this->assertSame('Barang rusak saat diterima', $order->refund_reason);
        $this->assertSame($admin->id, (int) $order->refunded_by);

        // Kedua buku stok kembali utuh.
        $product = Product::where('sku', 'LIFE-001')->firstOrFail();
        $this->assertSame(10, (int) $product->stock);
        $this->assertSame(10, (int) Stock::where('product_id', $product->id)->whereNull('variant_id')->first()->quantity);

        // Mutasi IN refund tercatat, mutasi OUT lama ditutup.
        $in = StockMutation::where('type', 'in')
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->firstOrFail();
        $this->assertSame(2, (int) $in->quantity);
        $this->assertStringContainsString('refund', $in->note);
        $this->assertStringContainsString($order->order_number, $in->note);

        $openOut = StockMutation::where('type', 'out')
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('status', '!=', StockMutation::STATUS_CANCELLED)
            ->count();
        $this->assertSame(0, $openOut);
    }

    public function test_refund_requires_paid_order_and_cannot_run_twice(): void
    {
        $owner = $this->customer('guard@mail.test');
        $admin = $this->admin();

        // Pesanan belum lunas -> ditolak.
        $unpaid = $this->shippedOrder($owner, ['payment_status' => 'unpaid', 'paid_at' => null]);
        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $unpaid), ['refund_reason' => 'x'])
            ->assertSessionHasErrors('refund_reason');
        $this->assertSame('unpaid', $unpaid->refresh()->payment_status);

        // Refund kedua -> ditolak (stok tidak dobel kembali).
        $paid = $this->shippedOrder($owner);
        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $paid), ['refund_reason' => 'alasan pertama'])
            ->assertRedirect(route('admin.orders.show', $paid));

        $product = Product::where('sku', 'LIFE-001')->firstOrFail();
        $stockAfterFirst = (int) $product->refresh()->stock;

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $paid), ['refund_reason' => 'alasan kedua'])
            ->assertSessionHasErrors('refund_reason');

        $this->assertSame($stockAfterFirst, (int) $product->refresh()->stock);
        $this->assertSame('alasan pertama', $paid->refresh()->refund_reason);
    }

    public function test_refunded_order_is_labeled_and_filterable_everywhere(): void
    {
        $owner = $this->customer('label@mail.test');
        $admin = $this->admin();
        $order = $this->shippedOrder($owner);

        $this->actingAs($admin)
            ->post(route('admin.orders.refund', $order), ['refund_reason' => 'komplain pembeli']);

        // Customer melihat label & tab refund.
        $this->actingAs($owner)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Dana Dikembalikan')
            ->assertSee('Refund diproses')
            ->assertSee('komplain pembeli');

        $this->actingAs($owner)->get(route('orders.index', ['status' => 'refunded']))
            ->assertOk()
            ->assertSee($order->order_number);

        // Admin: ringkasan + filter refund + badge.
        $response = $this->actingAs($admin)->get(route('admin.orders.index', ['filter' => 'refunded']));
        $response->assertOk()->assertSee('Refund Hari Ini');
        $this->assertArrayHasKey('refunded', $response->viewData('summary'));

        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Refund Diproses')
            ->assertSee('komplain pembeli');
    }

    // ── Rekening customer & bukti transfer refund ────────────────

    public function test_customer_can_save_bank_account_for_refund(): void
    {
        $owner = $this->customer('rekening@mail.test');

        $this->actingAs($owner)
            ->from(route('account.index'))
            ->patch(route('account.update'), [
                'name' => $owner->name,
                'bank_account' => 'BCA 1234567890',
            ])
            ->assertRedirect(route('account.index'));

        $this->assertSame('BCA 1234567890', $owner->fresh()->bank_account);

        // Tampil di form refund admin.
        $admin = $this->admin();
        $order = $this->shippedOrder($owner);
        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('BCA 1234567890');
    }

    public function test_refund_accepts_and_shows_transfer_proof(): void
    {
        $owner = $this->customer('bukti@mail.test');
        $owner->forceFill(['bank_account' => 'BRI 9876543210'])->save();
        $admin = $this->admin();
        $order = $this->shippedOrder($owner);

        $this->actingAs($admin)->post(route('admin.orders.refund', $order), [
            'refund_reason' => 'Barang tidak sesuai, dana dikembalikan',
            'refund_proof' => UploadedFile::fake()->image('bukti-transfer.png', 200, 120),
        ])->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();

        $this->assertSame('refunded', $order->status);
        $this->assertNotNull($order->refund_proof);
        $this->assertStringStartsWith('img/refunds/', $order->refund_proof);
        $this->assertFileExists(public_path($order->refund_proof));

        // Admin & customer sama-sama melihat bukti + rekening.
        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Bukti Transfer')
            ->assertSee(asset($order->refund_proof), false)
            ->assertSee('BRI 9876543210');

        $this->actingAs($owner)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee(asset($order->refund_proof), false)
            ->assertSee('BRI 9876543210');

        // Bersihkan file hasil uji.
        @unlink(public_path($order->refund_proof));
    }

    public function test_refund_form_is_available_for_completed_paid_orders(): void
    {
        $owner = $this->customer('selesai@mail.test');
        $admin = $this->admin();

        // Pesanan yang SUDAH selesai pun tetap bisa direfund (kasus barang kembali).
        $order = $this->shippedOrder($owner, ['status' => 'completed']);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Prosedur Refund')
            ->assertSee('refund_proof', false)
            ->assertSee('belum diisi'); // customer belum mengisi nomor rekening

        // Tapi refund tetap butuh pembayaran lunas.
        $unpaid = $this->shippedOrder($owner, ['status' => 'completed', 'payment_status' => 'unpaid', 'paid_at' => null]);
        $this->actingAs($admin)
            ->get(route('admin.orders.show', $unpaid))
            ->assertOk()
            ->assertDontSee('Prosedur Refund');
    }

    public function test_refund_rejects_invalid_proof_file(): void
    {
        $owner = $this->customer('validasi@mail.test');
        $admin = $this->admin();
        $order = $this->shippedOrder($owner);

        $this->actingAs($admin)->post(route('admin.orders.refund', $order), [
            'refund_reason' => 'uji validasi',
            'refund_proof' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('refund_proof');

        $this->assertSame('shipped', $order->refresh()->status);
    }

    // ── Pengaturan auto-complete ─────────────────────────────────

    public function test_auto_complete_days_can_be_saved_from_website_settings(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.website.update'), [
                'site_name' => 'ALKES SBS',
                'shipping_regular_cost' => 20000,
                'shipping_instant_cost' => 35000,
                'shipping_instant_enabled' => '1',
                'shipping_instant_areas' => '',
                'admin_fee' => 1000,
                'admin_fee_label' => 'Biaya Admin',
                'auto_complete_days' => '5',
                'feature_reviews' => '1',
                'feature_wishlist' => '0',
                'feature_live_chat' => '1',
                'feature_cod' => '0',
            ])
            ->assertRedirect(route('admin.website.edit'));

        $this->assertSame(5, Setting::int('auto_complete_days'));

        // Nilai di luar rentang ditolak.
        $this->actingAs($this->admin())
            ->put(route('admin.website.update'), [
                'site_name' => 'ALKES SBS',
                'shipping_regular_cost' => 20000,
                'shipping_instant_cost' => 35000,
                'shipping_instant_enabled' => '1',
                'shipping_instant_areas' => '',
                'admin_fee' => 1000,
                'admin_fee_label' => 'Biaya Admin',
                'auto_complete_days' => '999',
                'feature_reviews' => '1',
                'feature_wishlist' => '0',
                'feature_live_chat' => '1',
                'feature_cod' => '0',
            ])
            ->assertSessionHasErrors('auto_complete_days');
    }
}
