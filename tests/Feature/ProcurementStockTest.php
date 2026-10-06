<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReceive;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\StockOpname;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcurementStockTest extends TestCase
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

    private function product(array $overrides = []): Product
    {
        return Product::create(array_merge([
            'name' => 'Produk Procurement',
            'sku' => 'PROC-001',
            'price' => 1000,
            'stock' => 5,
            'unit' => 'pcs',
            'is_active' => true,
        ], $overrides));
    }

    private function purchaseOrder(Product $product, int $qty, array $overrides = []): PurchaseOrder
    {
        $supplier = Supplier::create(['name' => 'Supplier Procurement']);
        $po = PurchaseOrder::create(array_merge([
            'po_number' => 'PO-PROC-' . strtoupper(uniqid()),
            'supplier_id' => $supplier->id,
            'user_id' => $this->admin->id,
            'status' => 'sent',
            'total' => $qty * 1000,
            'notes' => null,
        ], $overrides));

        $po->items()->create([
            'product_id' => $product->id,
            'variant_id' => null,
            'quantity' => $qty,
            'price' => 1000,
            'subtotal' => $qty * 1000,
        ]);

        return $po;
    }

    public function test_purchase_receive_approval_increments_stock_once_and_logs_mutation(): void
    {
        $this->actingAs($this->admin);

        $product = $this->product(['stock' => 5]);
        Stock::create(['product_id' => $product->id, 'quantity' => 4]);
        $po = $this->purchaseOrder($product, 20);
        $poItem = $po->items()->firstOrFail();

        $this->post(route('admin.purchase-receives.store'), [
            'purchase_order_id' => $po->id,
            'items' => [$poItem->id => 10],
        ])->assertRedirect();

        $receive = PurchaseReceive::where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertSame(10, $receive->items()->firstOrFail()->quantity_received);

        $this->post(route('admin.purchase-receives.approve', $receive))->assertRedirect();

        $receive->refresh();
        $this->assertSame('received', $receive->status);
        $this->assertSame($this->admin->id, $receive->approved_by);
        $this->assertNotNull($receive->received_at);

        // Stok jual +10, buku gudang +10 (selisih awal tetap).
        $this->assertSame(15, $product->fresh()->stock);
        $this->assertSame(14, Stock::where('product_id', $product->id)->first()->quantity);

        $this->assertDatabaseHas('stock_mutations', [
            'type' => 'in',
            'status' => StockMutation::STATUS_COMPLETED,
            'quantity' => 10,
            'reference_type' => PurchaseReceive::class,
            'reference_id' => $receive->id,
            'created_by' => $this->admin->id,
        ]);

        // PO belum penuh -> sebagian.
        $this->assertSame('partial', $po->fresh()->status);

        // Approval kedua tidak menambah stok lagi.
        $this->post(route('admin.purchase-receives.approve', $receive))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(15, $product->fresh()->stock);
        $this->assertSame(1, StockMutation::where('reference_type', PurchaseReceive::class)->count());
    }

    public function test_partial_then_full_receive_marks_po_received_for_variant(): void
    {
        $this->actingAs($this->admin);

        $product = $this->product(['stock' => 0]);
        $variant = Variant::create([
            'product_id' => $product->id,
            'name' => 'Varian Kecil',
            'sku' => 'PROC-VAR-1',
            'price' => 1200,
            'stock' => 2,
            'is_active' => true,
        ]);
        $po = $this->purchaseOrder($product, 5);
        $po->items()->first()->update(['variant_id' => $variant->id]);
        $poItem = $po->items()->firstOrFail();

        // Terima sebagian (2 dari 5).
        $this->post(route('admin.purchase-receives.store'), [
            'purchase_order_id' => $po->id,
            'items' => [$poItem->id => 2],
        ])->assertRedirect();
        $first = PurchaseReceive::where('purchase_order_id', $po->id)->firstOrFail();
        $this->post(route('admin.purchase-receives.approve', $first))->assertRedirect();

        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertSame('partial', $po->fresh()->status);

        // Melebihi sisa PO ditolak.
        $this->post(route('admin.purchase-receives.store'), [
            'purchase_order_id' => $po->id,
            'items' => [$poItem->id => 10],
        ])->assertSessionHasErrors('items');

        // Sisa (3) diterima -> PO penuh.
        $this->post(route('admin.purchase-receives.store'), [
            'purchase_order_id' => $po->id,
            'items' => [$poItem->id => 3],
        ])->assertRedirect();
        $second = PurchaseReceive::where('purchase_order_id', $po->id)
            ->where('id', '!=', $first->id)
            ->firstOrFail();
        $this->post(route('admin.purchase-receives.approve', $second))->assertRedirect();

        $this->assertSame(7, $variant->fresh()->stock);
        $this->assertSame('received', $po->fresh()->status);
    }

    public function test_purchase_receive_validation_blocks_missing_items_and_draft_po(): void
    {
        $this->actingAs($this->admin);

        $product = $this->product();
        $draft = $this->purchaseOrder($product, 5, ['status' => 'draft']);

        $this->post(route('admin.purchase-receives.store'), [
            'purchase_order_id' => $draft->id,
            'items' => [$draft->items()->first()->id => 1],
        ])->assertSessionHasErrors('purchase_order_id');

        $sent = $this->purchaseOrder($product, 5);
        $this->post(route('admin.purchase-receives.store'), [
            'purchase_order_id' => $sent->id,
        ])->assertSessionHasErrors('items');

        $this->assertDatabaseCount('purchase_receives', 0);
    }

    public function test_rejected_purchase_receive_does_not_change_stock(): void
    {
        $this->actingAs($this->admin);

        $product = $this->product(['stock' => 5]);
        $po = $this->purchaseOrder($product, 10);
        $poItem = $po->items()->firstOrFail();

        $this->post(route('admin.purchase-receives.store'), [
            'purchase_order_id' => $po->id,
            'items' => [$poItem->id => 4],
        ])->assertRedirect();

        $receive = PurchaseReceive::where('purchase_order_id', $po->id)->firstOrFail();
        $this->post(route('admin.purchase-receives.reject', $receive))->assertRedirect();

        $this->assertSame('rejected', $receive->fresh()->status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseMissing('stock_mutations', [
            'reference_type' => PurchaseReceive::class,
            'reference_id' => $receive->id,
        ]);

        // Ditolak tidak bisa diproses ulang.
        $this->post(route('admin.purchase-receives.approve', $receive))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_stock_opname_approval_applies_selisih_to_variant_stock(): void
    {
        $this->actingAs($this->admin);

        $product = $this->product(['stock' => 30]);
        $variant = Variant::create([
            'product_id' => $product->id,
            'name' => 'Varian Opname',
            'sku' => 'OPNAME-VAR-1',
            'price' => 1500,
            'stock' => 6,
            'is_active' => true,
        ]);
        $stock = Stock::create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 6,
        ]);

        $this->post(route('admin.stock-opnames.store'), ['notes' => 'Opname varian']);
        $opname = StockOpname::where('user_id', $this->admin->id)->firstOrFail();

        $this->post(route('admin.stock-opnames.count', $opname), [
            'items' => [['stock_id' => $stock->id, 'physical_stock' => 4]],
        ])->assertRedirect();

        $this->post(route('admin.stock-opnames.approve', $opname))->assertRedirect();

        $opname->refresh();
        $this->assertSame('approved', $opname->status);
        $this->assertSame($this->admin->id, $opname->approved_by);

        // Selisih -2 diterapkan ke stok jual varian dan buku gudang.
        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertSame(4, $stock->fresh()->quantity);

        $this->assertDatabaseHas('stock_mutations', [
            'stock_id' => $stock->id,
            'type' => 'out',
            'status' => StockMutation::STATUS_COMPLETED,
            'quantity' => 2,
            'reference_type' => StockOpname::class,
            'reference_id' => $opname->id,
            'created_by' => $this->admin->id,
        ]);

        // Approval ganda tidak mengubah stok lagi.
        $this->post(route('admin.stock-opnames.approve', $opname))
            ->assertRedirect()
            ->assertSessionHas('error');
        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertSame(1, StockMutation::where('reference_type', StockOpname::class)->count());
    }

    public function test_checkout_and_cancel_keep_warehouse_book_in_step(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = $this->product(['stock' => 10]);
        Stock::create(['product_id' => $product->id, 'quantity' => 10]);
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

        $this->actingAs($user)->post(route('cart.store', $product), ['quantity' => 3]);
        $this->post(route('checkout.store'), [
            'address_id' => $address->id,
            'shipping_method' => 'regular',
            'payment_method' => 'bca_va',
        ]);

        $order = $user->orders()->firstOrFail();

        // Checkout memotong kedua buku stok.
        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame(7, Stock::where('product_id', $product->id)->first()->quantity);

        $order->cancelAndRelease('test cancel');

        // Pembatalan mengembalikan kedua buku stok.
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(10, Stock::where('product_id', $product->id)->first()->quantity);
    }
}
