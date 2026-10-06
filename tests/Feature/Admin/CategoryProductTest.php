<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CategoryProductTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_admin_can_manage_categories(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/categories')->assertOk();
        $this->get('/admin/categories/create')->assertOk();

        $this->post('/admin/categories', [])->assertSessionHasErrors('name');
        $this->post('/admin/categories', [
            'name' => 'Diagnosa',
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index'));

        $category = Category::where('name', 'Diagnosa')->firstOrFail();
        $this->assertSame('diagnosa', $category->slug);
        $this->get(route('admin.categories.edit', $category))->assertOk();

        $this->put(route('admin.categories.update', $category), [
            'name' => 'Diagnosa & Pemeriksaan',
            'slug' => $category->slug,
            'is_active' => '1',
        ])->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Diagnosa & Pemeriksaan']);

        // Duplikat nama ditolak.
        $this->post('/admin/categories', ['name' => 'Diagnosa & Pemeriksaan'])
            ->assertSessionHasErrors('name');

        $this->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_category_in_use_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin);

        $category = Category::create(['name' => 'Terpakai', 'is_active' => true]);
        Product::create([
            'name' => 'Produk Kategori', 'sku' => 'CAT-001', 'price' => 1000,
            'stock' => 1, 'unit' => 'pcs', 'is_active' => true,
            'category_id' => $category->id,
        ]);

        $this->from(route('admin.categories.index'))
            ->delete(route('admin.categories.destroy', $category))
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_product_store_accepts_category_and_image_upload(): void
    {
        $this->actingAs($this->admin);

        $category = Category::create(['name' => 'Respirasi', 'is_active' => true]);
        $file = UploadedFile::fake()->image('produk.png', 400, 400);

        $this->post('/admin/products', [
            'name' => 'Produk Berfoto',
            'sku' => 'IMG-001',
            'price' => 15000,
            'stock' => 4,
            'unit' => 'pcs',
            'is_active' => '1',
            'category_id' => $category->id,
            'image' => $file,
        ])->assertRedirect(route('admin.products.index'));

        $product = Product::where('sku', 'IMG-001')->firstOrFail();
        $this->assertSame($category->id, $product->category_id);
        $this->assertNotNull($product->image);
        $this->assertFileExists(public_path('img/products/' . $product->image));
        $this->assertStringContainsString('img/products/' . $product->image, $product->image_url);

        // Ganti gambar -> file lama terhapus.
        $oldImage = $product->image;
        $this->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => 15000,
            'stock' => 4,
            'unit' => 'pcs',
            'is_active' => '1',
            'category_id' => $category->id,
            'image' => UploadedFile::fake()->image('baru.png', 200, 200),
        ])->assertRedirect(route('admin.products.index'));

        $product->refresh();
        $this->assertNotSame($oldImage, $product->image);
        $this->assertFileDoesNotExist(public_path('img/products/' . $oldImage));
        $this->assertFileExists(public_path('img/products/' . $product->image));

        // Hapus gambar lewat checkbox.
        $current = $product->image;
        $this->put(route('admin.products.update', $product), [
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => 15000,
            'stock' => 4,
            'unit' => 'pcs',
            'is_active' => '1',
            'category_id' => $category->id,
            'remove_image' => '1',
        ]);
        $product->refresh();
        $this->assertNull($product->image);
        $this->assertFileDoesNotExist(public_path('img/products/' . $current));
    }

    public function test_front_catalog_filters_products_by_category(): void
    {
        $kategori = Category::create(['name' => 'Mobilitas', 'is_active' => true]);
        $lain = Category::create(['name' => 'Lainnya', 'is_active' => true]);

        $pakai = Product::create([
            'name' => 'Kursi Roda Manual', 'sku' => 'FIL-001', 'price' => 500000,
            'stock' => 2, 'unit' => 'pcs', 'is_active' => true,
            'category_id' => $kategori->id,
        ]);
        Product::create([
            'name' => 'Termometer Digital', 'sku' => 'FIL-002', 'price' => 45000,
            'stock' => 2, 'unit' => 'pcs', 'is_active' => true,
            'category_id' => $lain->id,
        ]);

        $response = $this->get('/produk?kategori=' . $kategori->slug);
        $response->assertOk()->assertSee('Kursi Roda Manual')->assertDontSee('Termometer Digital');
        $response->assertSee('Semua kategori');

        $this->get('/produk')->assertOk()
            ->assertSee('Kursi Roda Manual')
            ->assertSee('Termometer Digital');

        // Kategori nonaktif tidak ditawarkan di filter.
        $kategori->update(['is_active' => false]);
        $this->get('/produk')->assertDontSee('value="' . $kategori->slug . '"');
    }
}
