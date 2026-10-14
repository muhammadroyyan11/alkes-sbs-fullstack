<?php

namespace Tests\Feature\Admin;

use App\Models\PosSale;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function product(int $stock = 10, float $price = 25000): Product
    {
        $product = Product::create([
            'name' => 'Termometer POS', 'sku' => 'POS-001', 'price' => $price,
            'stock' => $stock, 'unit' => 'pcs', 'is_active' => true,
        ]);

        // Buku gudang ikut diisi supaya sinkron sejak awal.
        Stock::create(['product_id' => $product->id, 'variant_id' => null, 'quantity' => $stock]);

        return $product;
    }

    private function checkoutPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'items' => [
                ['product_id' => $product->id, 'variant_id' => null, 'quantity' => 3],
            ],
            'discount' => 0,
            'paid_amount' => 100000,
            'payment_method' => 'cash',
        ], $overrides);
    }

    public function test_cashier_page_and_product_search_are_available(): void
    {
        $product = $this->product();
        Product::create([
            'name' => 'Produk Nonaktif', 'sku' => 'POS-NONAKTIF', 'price' => 1000,
            'stock' => 5, 'unit' => 'pcs', 'is_active' => false,
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.pos.cashier'))
            ->assertOk()
            ->assertSee('Keranjang')
            ->assertSee('Pilih Barang');

        $json = $this->getJson(route('admin.pos.products', ['search' => 'Termometer']))->assertOk()->json();
        $names = collect($json['items'])->pluck('name');

        $this->assertContains('Termometer POS', $names);
        $this->assertNotContains('Produk Nonaktif', $names);
        $this->assertSame(25000.0, (float) collect($json['items'])->firstWhere('name', 'Termometer POS')['price']);
        $this->assertSame(10, (int) collect($json['items'])->firstWhere('name', 'Termometer POS')['stock']);
    }

    public function test_checkout_reduces_both_stock_books_and_logs_mutation(): void
    {
        $product = $this->product(stock: 10);
        $admin = $this->admin();

        $response = $this->actingAs($admin)->post(
            route('admin.pos.checkout'),
            $this->checkoutPayload($product, [
                'discount' => 5000,
                'paid_amount' => 90000,
                'customer_name' => 'Budi',
            ])
        );

        $sale = PosSale::firstOrFail();
        $response->assertRedirect(route('admin.pos.receipt', $sale));

        // Angka transaksi
        $this->assertSame('POS-' . now()->format('Ymd') . '-', substr($sale->invoice, 0, 13));
        $this->assertSame(17, strlen($sale->invoice));
        $this->assertSame(75000.0, (float) $sale->subtotal);   // 3 × 25.000
        $this->assertSame(70000.0, (float) $sale->total);       // 75.000 - 5.000
        $this->assertSame(20000.0, (float) $sale->change_amount);
        $this->assertSame('Budi', $sale->customer_name);
        $this->assertCount(1, $sale->items);

        // Kedua buku stok bergerak bersama (konsep stok utama)
        $product->refresh();
        $this->assertSame(7, (int) $product->stock);
        $this->assertSame(7, (int) Stock::where('product_id', $product->id)->whereNull('variant_id')->first()->quantity);

        // Mutasi OUT completed, referensi ke transaksi POS
        $mutation = StockMutation::where('type', 'out')
            ->where('status', StockMutation::STATUS_COMPLETED)
            ->where('reference_type', PosSale::class)
            ->where('reference_id', $sale->id)
            ->firstOrFail();
        $this->assertSame(3, (int) $mutation->quantity);
        $this->assertStringContainsString('penjualan POS', $mutation->note);
        $this->assertStringContainsString($sale->invoice, $mutation->note);

        // Mutasi ikut tampil di menu Log Stok (konsep stok yang sama)
        $this->get(route('admin.stock-logs.datatable'))->assertOk()->assertSee($sale->invoice);
    }

    public function test_receipt_page_clears_the_cashier_cart(): void
    {
        // Struk hanya tampil setelah pembayaran sukses, jadi halaman inilah
        // yang mengosongkan keranjang kasir (sessionStorage['pos_cart']).
        $product = $this->product(stock: 10);
        $admin = $this->admin();

        $this->actingAs($admin)->post(
            route('admin.pos.checkout'),
            $this->checkoutPayload($product)
        );

        $sale = PosSale::firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.pos.receipt', $sale))
            ->assertOk()
            ->assertSee($sale->invoice)
            ->assertSee("sessionStorage.removeItem('pos_cart')", false);
    }

    public function test_successful_checkout_redirects_to_receipt_not_cashier(): void
    {
        $product = $this->product(stock: 10);

        $response = $this->actingAs($this->admin())->post(
            route('admin.pos.checkout'),
            $this->checkoutPayload($product)
        );

        // Sukses -> struk (di situ keranjang dikosongkan).
        // Gagal validasi -> kembali ke kasir dgn keranjang utuh.
        $response->assertRedirect(route('admin.pos.receipt', PosSale::firstOrFail()));

        // Gagal stok: validator `items` jalan duluan (stok dicek saat transaksi)
        $this->post(route('admin.pos.checkout'), $this->checkoutPayload($product, [
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 999]],
            'paid_amount' => 1,
        ]))->assertSessionHasErrors('items');

        // Gagal bayar kurang (stok cukup)
        $this->post(route('admin.pos.checkout'), $this->checkoutPayload($product, [
            'paid_amount' => 1,
        ]))->assertSessionHasErrors('paid_amount');

        // Keduanya tetap kembali ke kasir -- keranjang tak hilang saat gagal.
        $this->post(route('admin.pos.checkout'), $this->checkoutPayload($product, [
            'paid_amount' => 1,
        ]))->assertSessionHasErrors('paid_amount');
        $this->assertSame(1, PosSale::count());
    }

    public function test_checkout_uses_variant_stock(): void
    {
        $product = $this->product();
        $variant = Variant::create([
            'product_id' => $product->id, 'name' => 'Merah', 'sku' => 'POS-001-RED',
            'price' => 30000, 'stock' => 8, 'is_active' => true,
        ]);
        Stock::create(['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 8]);

        $this->actingAs($this->admin())->post(route('admin.pos.checkout'), [
            'items' => [['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2]],
            'paid_amount' => 60000,
            'payment_method' => 'qris',
        ])->assertRedirect();

        $variant->refresh();
        $product->refresh();

        $this->assertSame(6, (int) $variant->stock);
        $this->assertSame(10, (int) $product->stock); // stok produk induk tidak disentuh
        $this->assertSame(6, (int) Stock::where('variant_id', $variant->id)->first()->quantity);

        $item = PosSale::firstOrFail()->items()->firstOrFail();
        $this->assertSame('POS-001-RED', $item->sku);
        $this->assertSame('Merah', $item->variant_name);
    }

    public function test_checkout_blocks_insufficient_stock_and_short_payment(): void
    {
        $product = $this->product(stock: 2);

        $this->actingAs($this->admin())->post(route('admin.pos.checkout'), $this->checkoutPayload($product, [
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 5]],
        ]))->assertSessionHasErrors('items');

        // Stok cukup (qty 1) tapi uang diterima kurang dari total -> error pembayaran
        $this->actingAs($this->admin())->post(route('admin.pos.checkout'), $this->checkoutPayload($product, [
            'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
            'paid_amount' => 1000,
        ]))->assertSessionHasErrors('paid_amount');

        $this->assertDatabaseCount('pos_sales', 0);
        $product->refresh();
        $this->assertSame(2, (int) $product->stock);
        $this->assertSame(2, (int) Stock::where('product_id', $product->id)->whereNull('variant_id')->first()->quantity);
    }

    public function test_cancel_sale_restores_stock_and_closes_out_mutation(): void
    {
        $product = $this->product(stock: 10);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.pos.checkout'), $this->checkoutPayload($product));
        $sale = PosSale::firstOrFail();

        // Batalkan
        $this->actingAs($admin)
            ->post(route('admin.pos.sales.cancel', $sale), ['reason' => 'salah input'])
            ->assertRedirect(route('admin.pos.sales.show', $sale));

        $sale->refresh();
        $product->refresh();

        $this->assertTrue($sale->isCancelled());
        $this->assertSame(10, (int) $product->stock);
        $this->assertSame(10, (int) Stock::where('product_id', $product->id)->whereNull('variant_id')->first()->quantity);
        $this->assertStringContainsString('Dibatalkan', (string) $sale->notes);

        // Mutasi OUT ditutup, mutasi IN dicatat
        $this->assertSame(0, StockMutation::where('reference_type', PosSale::class)
            ->where('reference_id', $sale->id)->where('type', 'out')
            ->where('status', '!=', StockMutation::STATUS_CANCELLED)->count());

        $in = StockMutation::where('reference_type', PosSale::class)
            ->where('reference_id', $sale->id)->where('type', 'in')->firstOrFail();
        $this->assertSame(3, (int) $in->quantity);

        // Pembatalan ganda tidak mengembalikan stok dua kali
        $this->actingAs($admin)->post(route('admin.pos.sales.cancel', $sale))
            ->assertSessionHas('error');

        $product->refresh();
        $this->assertSame(10, (int) $product->stock);
    }

    public function test_sales_report_summarizes_filters_and_exports(): void
    {
        $admin = $this->admin();
        $product = $this->product();

        // Penjualan hari ini
        $this->actingAs($admin)->post(route('admin.pos.checkout'), $this->checkoutPayload($product));
        $today = PosSale::firstOrFail();

        // Penjualan lama (di luar rentang "hari ini")
        $old = PosSale::create([
            'invoice' => 'POS-LAMA-001', 'user_id' => $admin->id,
            'subtotal' => 50000, 'discount' => 0, 'total' => 50000,
            'paid_amount' => 50000, 'change_amount' => 0,
            'payment_method' => 'cash', 'status' => PosSale::STATUS_COMPLETED,
            'sold_at' => now()->subDays(40),
        ]);

        // Ringkasan hari ini: hanya penjualan hari ini
        $this->actingAs($admin)->get(route('admin.pos.sales.index', ['range' => 'today']))
            ->assertOk()
            ->assertSee($today->invoice)
            ->assertDontSee($old->invoice)
            ->assertSee('Total Transaksi')
            ->assertSee('75.000'); // omzet hari ini (3 × 25.000, tanpa diskon)

        // Semua rentang: kedua transaksi masuk
        $this->actingAs($admin)->get(route('admin.pos.sales.index', [
            'date_from' => now()->subDays(60)->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]))
            ->assertOk()
            ->assertSee($today->invoice)
            ->assertSee($old->invoice);

        // Filter metode pembayaran
        $this->actingAs($admin)->get(route('admin.pos.sales.index', ['payment_method' => 'qris']))
            ->assertOk()
            ->assertDontSee($today->invoice);

        // Detail transaksi
        $this->actingAs($admin)->get(route('admin.pos.sales.show', $today))
            ->assertOk()
            ->assertSee($today->invoice)
            ->assertSee('Item Terjual');

        // Ekspor CSV
        $export = $this->actingAs($admin)->get(route('admin.pos.sales.export', ['range' => 'today']));
        $export->assertOk();
        $this->assertStringContainsString('text/csv', (string) $export->headers->get('Content-Type'));
        $csv = $export->streamedContent();
        $this->assertStringContainsString($today->invoice, $csv);
        $this->assertStringContainsString('TOTAL', $csv);
        $this->assertStringNotContainsString($old->invoice, $csv);
    }

    public function test_pos_routes_follow_staff_role_access(): void
    {
        $operasional = User::factory()->create(['role' => 'operasional', 'is_active' => true]);
        $gudang = User::factory()->create(['role' => 'gudang', 'is_active' => true]);

        $this->actingAs($operasional)->get(route('admin.pos.cashier'))->assertOk();
        $this->actingAs($operasional)->get(route('admin.pos.sales.index'))->assertOk();

        $this->actingAs($gudang)->get(route('admin.pos.cashier'))->assertForbidden();
        $this->actingAs($gudang)->get(route('admin.pos.sales.index'))->assertForbidden();
    }

    public function test_pos_menu_is_seeded_in_sidebar(): void
    {
        $this->seed(\Database\Seeders\MenuSeeder::class);

        $admin = $this->admin();
        $names = collect(\App\Models\Menu::getForUser($admin))
            ->flatMap(fn ($group) => collect($group['items'])->pluck('name'))
            ->all();

        $this->assertContains('Kasir (POS)', $names);
        $this->assertContains('Laporan Penjualan', $names);
    }
}
