<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ShippingQuoteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GapClosureTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function customer(): User
    {
        return User::factory()->create(['role' => 'user', 'is_active' => true]);
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
            'name' => 'Produk Uji Gap', 'sku' => 'GAP-001', 'price' => 15000,
            'stock' => $stock, 'unit' => 'pcs', 'is_active' => true,
        ]);
    }

    private function purchaseOrder(): PurchaseOrder
    {
        $supplier = Supplier::create(['name' => 'PT Uji Gap']);
        $product = $this->product();

        $po = PurchaseOrder::create([
            'po_number' => 'PO-TEST-001',
            'supplier_id' => $supplier->id,
            'user_id' => $this->admin()->id,
            'status' => 'sent',
            'total' => 0,
        ]);

        $po->items()->create([
            'product_id' => $product->id,
            'quantity' => 5,
            'price' => 10000,
            'subtotal' => 50000,
        ]);

        $po->update(['total' => 50000]);

        return $po->fresh();
    }

    // ── #5 Cetak & ekspor PO ─────────────────────────────────────

    public function test_purchase_order_can_be_printed(): void
    {
        $po = $this->purchaseOrder();

        $this->actingAs($this->admin())
            ->get(route('admin.purchase-orders.print', $po))
            ->assertOk()
            ->assertSee('PURCHASE ORDER')
            ->assertSee('PO-TEST-001')
            ->assertSee('PT Uji Gap')
            ->assertSee('Produk Uji Gap');
    }

    public function test_purchase_order_can_be_exported_to_csv(): void
    {
        $po = $this->purchaseOrder();

        $response = $this->actingAs($this->admin())
            ->get(route('admin.purchase-orders.export', $po));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('Content-Disposition')
        );

        $csv = $response->streamedContent();

        $this->assertStringContainsString('PO-TEST-001', $csv);
        $this->assertStringContainsString('Produk Uji Gap', $csv);
        $this->assertStringContainsString('50000', $csv);
    }

    // ── #13 Sakelar & area pengiriman instan ─────────────────────

    public function test_instant_shipping_can_be_disabled_by_admin(): void
    {
        $user = $this->customer();
        $address = $this->addressFor($user);
        $quotes = app(ShippingQuoteService::class);

        // Default: aktif, tanpa batasan area.
        $codes = collect($quotes->quote($address, 1000))->pluck('code');
        $this->assertContains('instant', $codes);

        Setting::putMany(['shipping_instant_enabled' => '0']);

        $codes = collect($quotes->quote($address, 1000))->pluck('code');
        $this->assertNotContains('instant', $codes);
        $this->assertNotEmpty($codes); // opsi reguler tetap ada
    }

    public function test_instant_shipping_restricted_to_configured_areas(): void
    {
        $user = $this->customer();
        $quotes = app(ShippingQuoteService::class);

        Setting::putMany([
            'shipping_instant_enabled' => '1',
            'shipping_instant_areas' => 'Kota Bandung, Kota Surabaya',
        ]);

        $outside = $this->addressFor($user, 'Kota Malang');
        $inside = $this->addressFor($user, 'Kota Surabaya');

        $this->assertNotContains(
            'instant',
            collect($quotes->quote($outside, 1000))->pluck('code')->all()
        );
        $this->assertContains(
            'instant',
            collect($quotes->quote($inside, 1000))->pluck('code')->all()
        );
    }

    public function test_checkout_ignores_instant_when_disabled(): void
    {
        Setting::putMany([
            'shipping_instant_enabled' => '0',
            'shipping_regular_cost' => 21000,
            'shipping_instant_cost' => 99000,
        ]);

        $user = $this->customer();
        $address = $this->addressFor($user);

        $this->actingAs($user)->post(route('cart.store', $this->product()), ['quantity' => 1]);
        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'shipping_method' => 'instant',
            'payment_method' => 'bca_va',
        ]);

        $order = $user->orders()->firstOrFail();

        // Request menyebut instan, tapi server menolak: memakai tarif reguler.
        $this->assertSame(21000.0, (float) $order->shipping_cost);
        $this->assertSame('RajaOngkir', $order->shipment()->first()->courier);
    }

    public function test_admin_can_save_instant_settings(): void
    {
        $this->actingAs($this->admin())
            ->put(route('admin.website.update'), $this->websitePayload([
                'shipping_instant_enabled' => '0',
                'shipping_instant_areas' => 'Kota Malang',
            ]))
            ->assertRedirect(route('admin.website.edit'));

        $this->assertSame('0', (string) Setting::get('shipping_instant_enabled'));
        $this->assertSame('Kota Malang', (string) Setting::get('shipping_instant_areas'));
    }

    // ── #15 Riwayat pengiriman: resi, tanggal, tautan lacak ──────

    public function test_customer_order_page_shows_tracking_details(): void
    {
        $user = $this->customer();
        $address = $this->addressFor($user);

        $order = Order::create([
            'order_number' => 'SBS-GAP-001',
            'user_id' => $user->id,
            'address_id' => $address->id,
            'status' => 'shipped',
            'payment_status' => 'paid',
            'payment_method' => 'bca_va',
            'shipping_method' => 'regular',
            'subtotal' => 15000,
            'shipping_cost' => 20000,
            'admin_fee' => 1000,
            'total' => 36000,
            'shipping_address' => 'Jl. Test 1, Kota Malang',
        ]);

        // Sebelum dikirim: pesan jelas.
        $this->actingAs($user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Belum dikirim')
            ->assertDontSee('Lacak Pengiriman');

        Shipment::create([
            'order_id' => $order->id,
            'courier' => 'JNE',
            'service' => 'REG',
            'tracking_number' => 'JNE001234567',
            'status' => 'shipped',
            'shipped_at' => now()->subDay(),
        ]);

        $this->actingAs($user)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('No. Resi')
            ->assertSee('JNE001234567')
            ->assertSee('Dikirim:')
            ->assertSee('Lacak Pengiriman')
            ->assertSee('cek%20resi%20JNE', false);
    }

    public function test_tracking_link_is_absent_without_resi(): void
    {
        $shipment = new Shipment(['tracking_number' => '   ']);
        $this->assertNull($shipment->tracking_url());

        $shipment = new Shipment(['tracking_number' => 'SICEPAT123', 'courier' => 'SiCepat']);
        $url = (string) $shipment->tracking_url();
        $this->assertStringStartsWith('https://', $url);
        $this->assertStringContainsString('SICEPAT123', $url);
    }

    // ── #17 Tombol WhatsApp & marketplace di halaman produk ──────

    public function test_product_page_shows_whatsapp_and_marketplace_links(): void
    {
        Setting::putMany([
            'wa_number' => '081234567890',
            'tokopedia_url' => 'https://www.tokopedia.com/alkessbs',
            'shopee_url' => '',
        ]);

        $product = $this->product();

        $this->get(route('products.show', ['product' => $product->sku]))
            ->assertOk()
            ->assertSee('wa.me/6281234567890', false)
            ->assertSee('Tanya via WhatsApp')
            ->assertSee('https://www.tokopedia.com/alkessbs', false)
            ->assertSee('Beli di Tokopedia');
    }

    public function test_product_page_hides_contact_buttons_when_not_configured(): void
    {
        Setting::putMany([
            'wa_number' => '',
            'tokopedia_url' => '',
            'shopee_url' => '',
            'lazada_url' => '',
            'blibli_url' => '',
            'tiktok_shop_url' => '',
        ]);

        $product = $this->product();

        $this->get(route('products.show', ['product' => $product->sku]))
            ->assertOk()
            ->assertDontSee('wa.me')
            ->assertDontSee('<div class="product-contact-links">', false);
    }

    /**
     * Payload dasar form /admin/website (hanya field yang divalidasi wajib).
     */
    private function websitePayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'ALKES SBS',
            'shipping_regular_cost' => 20000,
            'shipping_instant_cost' => 35000,
            'shipping_instant_enabled' => '1',
            'shipping_instant_areas' => '',
            'admin_fee' => 1000,
            'admin_fee_label' => 'Biaya Admin',
            'feature_reviews' => '1',
            'feature_wishlist' => '0',
            'feature_live_chat' => '1',
            'feature_cod' => '0',
        ], $overrides);
    }
}
