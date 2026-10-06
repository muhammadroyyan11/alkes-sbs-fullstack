<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceive;
use App\Models\Stock;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AdminFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    public function test_admin_pages_require_an_authenticated_admin(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');

        $customer = User::factory()->create(['role' => 'user']);
        $this->actingAs($customer)->get('/admin')->assertForbidden();

        $this->actingAs($this->admin)->get('/admin')->assertOk();
    }

    public function test_all_admin_index_and_create_pages_render(): void
    {
        $this->actingAs($this->admin);

        foreach ([
            '/admin',
            '/admin/products',
            '/admin/products/create',
            '/admin/variants',
            '/admin/variants/create',
            '/admin/stocks',
            '/admin/stock-opnames',
            '/admin/stock-opnames/create',
            '/admin/suppliers',
            '/admin/suppliers/create',
            '/admin/purchase-orders',
            '/admin/purchase-orders/create',
            '/admin/purchase-receives',
            '/admin/purchase-receives/create',
            '/admin/users',
            '/admin/users/create',
            '/admin/website',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_product_crud_validation_and_datatable_search(): void
    {
        $this->actingAs($this->admin);

        $this->post('/admin/products', [])->assertSessionHasErrors(['name', 'price', 'stock', 'unit']);

        $this->post('/admin/products', [
            'name' => 'Produk Pengujian',
            'sku' => 'FULL-TEST-PRODUCT',
            'price' => 125000,
            'stock' => 7,
            'unit' => 'pcs',
            'description' => 'Produk untuk pengujian otomatis.',
            'is_active' => 1,
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('sku', 'FULL-TEST-PRODUCT')->firstOrFail();
        $this->get(route('admin.products.edit', $product))->assertOk();

        $this->put(route('admin.products.update', $product), [
            'name' => 'Produk Pengujian Diperbarui',
            'sku' => 'FULL-TEST-PRODUCT',
            'price' => 130000,
            'stock' => 8,
            'unit' => 'box',
            'is_active' => 1,
        ])->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Produk Pengujian Diperbarui',
            'stock' => 8,
        ]);

        $this->getJson(route('admin.products.datatable', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'FULL-TEST-PRODUCT', 'regex' => false],
        ]))->assertOk()->assertJsonPath('recordsFiltered', 1);

        $this->delete(route('admin.products.destroy', $product))->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_variant_crud_and_validation(): void
    {
        $this->actingAs($this->admin);
        $product = Product::create([
            'name' => 'Produk Induk', 'sku' => 'PARENT-001', 'price' => 1000,
            'stock' => 10, 'unit' => 'pcs', 'is_active' => true,
        ]);

        $this->post('/admin/variants', [])->assertSessionHasErrors(['product_id', 'name', 'price', 'stock']);
        $this->post('/admin/variants', [
            'product_id' => $product->id,
            'name' => 'Ukuran Besar',
            'sku' => 'VAR-FULL-TEST',
            'price' => 1500,
            'stock' => 4,
            'is_active' => 1,
        ])->assertRedirect(route('admin.variants.index'));

        $variant = Variant::where('sku', 'VAR-FULL-TEST')->firstOrFail();
        $this->put(route('admin.variants.update', $variant), [
            'product_id' => $product->id,
            'name' => 'Ukuran XL',
            'sku' => 'VAR-FULL-TEST',
            'price' => 1750,
            'stock' => 6,
            'is_active' => 1,
        ])->assertRedirect(route('admin.variants.index'));
        $this->assertDatabaseHas('variants', ['id' => $variant->id, 'name' => 'Ukuran XL', 'stock' => 6]);

        $this->delete(route('admin.variants.destroy', $variant))->assertRedirect(route('admin.variants.index'));
        $this->assertDatabaseMissing('variants', ['id' => $variant->id]);
    }

    public function test_supplier_crud_and_validation(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/suppliers', ['email' => 'bukan-email'])->assertSessionHasErrors(['name', 'email']);

        $this->post('/admin/suppliers', [
            'name' => 'Supplier Pengujian',
            'contact_person' => 'Tester',
            'phone' => '08123456789',
            'email' => 'supplier@example.test',
            'address' => 'Jakarta',
        ])->assertRedirect(route('admin.suppliers.index'));

        $supplier = Supplier::where('email', 'supplier@example.test')->firstOrFail();
        $this->put(route('admin.suppliers.update', $supplier), [
            'name' => 'Supplier Diperbarui',
            'email' => 'supplier@example.test',
        ])->assertRedirect(route('admin.suppliers.index'));
        $this->assertDatabaseHas('suppliers', ['id' => $supplier->id, 'name' => 'Supplier Diperbarui']);

        $this->delete(route('admin.suppliers.destroy', $supplier))->assertRedirect(route('admin.suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_user_crud_password_and_self_delete_protection(): void
    {
        $this->actingAs($this->admin);
        $this->post('/admin/users', [])->assertSessionHasErrors(['name', 'email', 'password', 'role']);

        $this->post('/admin/users', [
            'name' => 'User Pengujian',
            'email' => 'admin-test@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
            'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'admin-test@example.test')->firstOrFail();
        $this->put(route('admin.users.update', $user), [
            'name' => 'User Diperbarui',
            'email' => 'admin-test@example.test',
            'role' => 'user',
            'is_active' => 1,
        ])->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'User Diperbarui', 'role' => 'user']);

        $this->from('/admin/users')->delete(route('admin.users.destroy', $this->admin))
            ->assertRedirect('/admin/users')
            ->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);

        $this->delete(route('admin.users.destroy', $user))->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_purchase_order_and_receive_status_workflows(): void
    {
        $this->actingAs($this->admin);
        $supplier = Supplier::create(['name' => 'Supplier PO']);
        $product = Product::create([
            'name' => 'Produk PO', 'sku' => 'PO-TEST-PRODUCT', 'price' => 1000,
            'stock' => 3, 'unit' => 'pcs', 'is_active' => true,
        ]);

        $this->post('/admin/purchase-orders', [])->assertSessionHasErrors('supplier_id');
        $this->post('/admin/purchase-orders', [
            'supplier_id' => $supplier->id,
            'notes' => 'PO full test',
            'items' => [
                ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 10, 'price' => 500],
            ],
        ])->assertRedirect();

        $purchaseOrder = PurchaseOrder::where('supplier_id', $supplier->id)->firstOrFail();
        $this->assertSame('draft', $purchaseOrder->status);
        $this->assertSame(5000, (int) $purchaseOrder->total);
        $this->get(route('admin.purchase-orders.show', $purchaseOrder))->assertOk();
        $this->post(route('admin.purchase-orders.send', $purchaseOrder))->assertRedirect();
        $this->assertSame('sent', $purchaseOrder->fresh()->status);

        $poItem = $purchaseOrder->items()->firstOrFail();
        $this->post('/admin/purchase-receives', [
            'purchase_order_id' => $purchaseOrder->id,
            'notes' => 'Receive full test',
            'items' => [$poItem->id => 6],
        ])->assertRedirect();
        $receive = PurchaseReceive::where('purchase_order_id', $purchaseOrder->id)->firstOrFail();
        $this->assertSame(6, $receive->items()->firstOrFail()->quantity_received);
        $this->get(route('admin.purchase-receives.show', $receive))->assertOk();
        $this->post(route('admin.purchase-receives.approve', $receive))->assertRedirect();
        $this->assertSame('received', $receive->fresh()->status);
        $this->assertSame(9, $product->fresh()->stock);

        $this->post(route('admin.purchase-orders.cancel', $purchaseOrder))->assertRedirect();
        $this->assertSame('cancelled', $purchaseOrder->fresh()->status);
    }

    public function test_stock_opname_count_approve_and_reject_workflows(): void
    {
        $this->actingAs($this->admin);
        $product = Product::create([
            'name' => 'Produk Stok', 'sku' => 'STOCK-TEST', 'price' => 1000,
            'stock' => 10, 'unit' => 'pcs', 'is_active' => true,
        ]);
        $stock = Stock::create(['product_id' => $product->id, 'quantity' => 10]);

        $this->post('/admin/stock-opnames', ['notes' => 'Full test'])->assertRedirect();
        $opname = StockOpname::where('user_id', $this->admin->id)->firstOrFail();
        $this->get(route('admin.stock-opnames.show', $opname))->assertOk();

        $this->post(route('admin.stock-opnames.count', $opname), [
            'items' => [['stock_id' => $stock->id, 'physical_stock' => 8]],
        ])->assertRedirect();
        $this->assertSame('counted', $opname->fresh()->status);
        $this->assertDatabaseHas('stock_opname_items', [
            'stock_opname_id' => $opname->id,
            'system_stock' => 10,
            'physical_stock' => 8,
        ]);

        $this->post(route('admin.stock-opnames.approve', $opname))->assertRedirect();
        $this->assertSame('approved', $opname->fresh()->status);
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(8, $stock->fresh()->quantity);
        $this->assertDatabaseHas('stock_mutations', [
            'stock_id' => $stock->id,
            'type' => 'out',
            'quantity' => 2,
            'reference_type' => StockOpname::class,
            'reference_id' => $opname->id,
        ]);

        // Approval ganda ditolak: stok tidak dihitung dua kali.
        $this->post(route('admin.stock-opnames.approve', $opname))->assertRedirect();
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertDatabaseCount('stock_mutations', 1);

        $pending = StockOpname::create(['user_id' => $this->admin->id, 'status' => 'pending']);
        $this->post(route('admin.stock-opnames.approve', $pending))->assertRedirect();
        $this->assertSame('pending', $pending->fresh()->status);

        $rejected = StockOpname::create(['user_id' => $this->admin->id, 'status' => 'pending']);
        $this->post(route('admin.stock-opnames.reject', $rejected))->assertRedirect();
        $this->assertSame('rejected', $rejected->fresh()->status);
    }

    public function test_website_settings_are_validated_and_saved(): void
    {
        $this->actingAs($this->admin);
        $this->put('/admin/website', ['site_email' => 'invalid'])->assertSessionHasErrors(['site_name', 'site_email']);

        $this->put('/admin/website', [
            'site_name' => 'ALKES SBS Test',
            'site_description' => 'Pengujian pengaturan',
            'site_email' => 'info@example.test',
            'site_phone' => '021123456',
            'site_address' => 'Jakarta',
            'wa_number' => '6281111111',
            'shipping_origin_city' => 'Surabaya',
            'shipping_origin_province' => 'Jawa Timur',
            'shipping_regular_cost' => 25000,
            'shipping_instant_cost' => 40000,
            'shipping_instant_enabled' => '1',
            'shipping_instant_areas' => 'Kota Surabaya',
            'admin_fee' => 2500,
            'feature_reviews' => '1',
            'feature_wishlist' => '0',
            'feature_live_chat' => '1',
            'feature_cod' => '0',
        ])->assertRedirect(route('admin.website.edit'));

        $this->assertSame('ALKES SBS Test', Setting::get('site_name'));
        $this->assertSame(2500, Setting::int('admin_fee'));
        $this->assertSame(40000, Setting::int('shipping_instant_cost'));
        $this->assertSame('1', (string) Setting::get('shipping_instant_enabled'));
        $this->assertSame('Kota Surabaya', (string) Setting::get('shipping_instant_areas'));
        $this->assertSame('Surabaya', Setting::get('shipping_origin_city'));

        $this->assertDatabaseHas('settings', ['key' => 'admin_fee', 'value' => '2500']);

        // Halaman depan membaca pengaturan yang sama.
        $this->get(route('cart.index'))->assertOk();
    }

    public function test_website_settings_reject_invalid_urls_and_out_of_range_values(): void
    {
        $this->actingAs($this->admin);

        $this->put('/admin/website', [
            'site_name' => 'ALKES SBS',
            'tokopedia_url' => 'bukan-url',
            'admin_fee' => -100,
            'feature_reviews' => 'maybe',
        ])->assertSessionHasErrors(['tokopedia_url', 'admin_fee', 'feature_reviews']);
    }
}
