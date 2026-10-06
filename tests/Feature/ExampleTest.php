<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Address;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk()->assertSee('Alat Kesehatan');
    }

    public function test_product_catalog_can_be_searched_and_product_detail_is_available(): void
    {
        $product = Product::create([
            'name' => 'Tensimeter Pengujian',
            'sku' => 'FRONT-TEST-001',
            'price' => 250000,
            'stock' => 5,
            'unit' => 'pcs',
            'description' => 'Produk pengujian katalog.',
            'is_active' => true,
        ]);

        $this->get('/produk?q=Tensimeter')
            ->assertOk()
            ->assertSee($product->name)
            ->assertDontSee('Produk tidak ditemukan');

        $this->get(route('products.show', ['product' => $product->sku]))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('Rp 250.000');
    }

    public function test_guest_cart_is_preserved_through_login_before_checkout(): void
    {
        $product = Product::create(['name'=>'Produk Cart','sku'=>'CART-001','price'=>100000,'stock'=>5,'unit'=>'pcs','is_active'=>true]);
        $user = User::factory()->create(['role' => 'user']);

        $this->post(route('cart.store', $product), ['quantity' => 2])->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertOk()->assertSee('Produk Cart')->assertSee('Rp 200.000');
        $this->get(route('checkout.index'))->assertRedirect(route('login'));
        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('checkout.index'));
        $this->get(route('checkout.index'))->assertOk()->assertSee('Produk Cart')->assertSee('Selesaikan Pesanan')
            ->assertSee('img/payments/bca_va.svg', false)
            ->assertSee('img/payments/qris.svg', false)
            ->assertSee('img/payments/dana.svg', false)
            ->assertSee('img/payments/visa.svg', false);
    }

    public function test_customer_can_place_order_and_stock_is_reduced(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::create(['name'=>'Produk Order','sku'=>'ORDER-001','price'=>100000,'stock'=>5,'unit'=>'pcs','is_active'=>true]);
        $address = Address::create(['user_id'=>$user->id,'label'=>'Rumah','recipient_name'=>$user->name,'phone'=>'08123','address'=>'Jl. Test 1','city'=>'Malang','province'=>'Jawa Timur','postal_code'=>'65100','is_primary'=>true]);

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 2]);
        $response = $this->post(route('checkout.store'), ['address_id'=>$address->id,'shipping_method'=>'regular','payment_method'=>'bca_va']);

        $order = $user->orders()->firstOrFail();
        $response->assertRedirect(route('payment.index', $order));
        $this->assertDatabaseHas('order_items', ['order_id'=>$order->id,'sku'=>'ORDER-001','quantity'=>2]);
        $this->assertDatabaseHas('shipments', ['order_id'=>$order->id,'courier'=>'RajaOngkir']);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertEmpty(session('cart', []));
    }

    public function test_account_dashboard_shows_order_status_counts_and_filters_history(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $address = Address::create([
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => $user->name,
            'phone' => '08123456789',
            'address' => 'Jl. Kesehatan 10',
            'city' => 'Malang',
            'province' => 'Jawa Timur',
            'postal_code' => '65100',
            'is_primary' => true,
        ]);

        $baseOrder = [
            'user_id' => $user->id,
            'address_id' => $address->id,
            'shipping_method' => 'regular',
            'subtotal' => 100000,
            'shipping_cost' => 20000,
            'total' => 120000,
            'shipping_address' => 'Jl. Kesehatan 10, Malang',
        ];

        $unpaid = Order::create($baseOrder + [
            'order_number' => 'SBS-ACCOUNT-UNPAID',
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'bank_transfer',
        ]);
        Order::create($baseOrder + [
            'order_number' => 'SBS-ACCOUNT-PROCESS',
            'status' => 'processing',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
        ]);
        $shipped = Order::create($baseOrder + [
            'order_number' => 'SBS-ACCOUNT-SHIPPED',
            'status' => 'shipped',
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
        ]);
        $shipped->shipment()->create(['courier' => 'JNE', 'service' => 'REG', 'status' => 'in_transit']);
        $completed = Order::create($baseOrder + [
            'order_number' => 'SBS-ACCOUNT-DONE',
            'status' => 'completed',
            'payment_status' => 'paid',
            'payment_method' => 'bank_transfer',
        ]);
        $completed->shipment()->create(['courier' => 'JNE', 'service' => 'REG', 'status' => 'delivered']);

        $this->actingAs($user)
            ->withSession(['cart' => [['quantity' => 2]]])
            ->get(route('account.index'))
            ->assertOk()
            ->assertSee('Status Pesanan')
            ->assertSee('Transaksi Terakhir')
            ->assertSee('1 pesanan')
            ->assertViewHas('orderStatusCounts', fn ($counts) => $counts->all() === [
                'unpaid' => 1,
                'processing' => 1,
                'shipped' => 1,
                'completed' => 1,
            ]);

        $this->get(route('orders.index', ['status' => 'shipped']))
            ->assertOk()
            ->assertSee($shipped->order_number)
            ->assertDontSee($unpaid->order_number)
            ->assertDontSee($completed->order_number);
    }
}
