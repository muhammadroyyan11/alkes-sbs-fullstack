<?php

namespace Tests\Feature\Admin;

use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffRoleTest extends TestCase
{
    use RefreshDatabase;

    private function staff(string $role): User
    {
        return User::factory()->create(['role' => $role, 'is_active' => true]);
    }

    private function menuNames(User $user): array
    {
        return collect(Menu::getForUser($user))
            ->flatMap(fn ($group) => collect($group['items'])->pluck('name'))
            ->all();
    }

    public function test_gudang_staff_can_login_and_open_warehouse_pages(): void
    {
        $gudang = $this->staff('gudang');

        $this->post('/admin/login', [
            'email' => $gudang->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.stocks.index'))->assertOk();
        $this->get(route('admin.stock-opnames.index'))->assertOk();
        $this->get(route('admin.purchase-orders.index'))->assertOk();
        $this->get(route('admin.purchase-receives.index'))->assertOk();
        $this->get(route('admin.products.index'))->assertOk();
        $this->get(route('admin.categories.index'))->assertOk();
        $this->get(route('admin.suppliers.index'))->assertOk();
    }

    public function test_operasional_staff_can_login_and_open_order_pages(): void
    {
        $operasional = $this->staff('operasional');

        $this->post('/admin/login', [
            'email' => $operasional->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard'));

        $this->get(route('admin.dashboard'))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
    }

    public function test_staff_are_forbidden_from_pages_outside_their_role(): void
    {
        $gudang = $this->staff('gudang');
        $this->actingAs($gudang);
        $this->get(route('admin.orders.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->get(route('admin.website.edit'))->assertForbidden();
        $this->post(route('admin.users.store'), [])->assertForbidden();

        $operasional = $this->staff('operasional');
        $this->actingAs($operasional);
        $this->get(route('admin.stocks.index'))->assertForbidden();
        $this->get(route('admin.products.index'))->assertForbidden();
        $this->get(route('admin.purchase-orders.index'))->assertForbidden();
        $this->get(route('admin.users.index'))->assertForbidden();
        $this->put(route('admin.website.update'), [])->assertForbidden();
    }

    public function test_customer_role_cannot_enter_admin_panel(): void
    {
        $customer = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
    }

    public function test_admin_login_rejects_customer_account(): void
    {
        $customer = User::factory()->create(['role' => 'user', 'is_active' => true]);

        $this->post('/admin/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');
    }

    public function test_front_login_rejects_staff_but_accepts_customer(): void
    {
        // Staf ditolak dulu (login gagal -> tidak ada sesi login yang tersisa).
        $gudang = $this->staff('gudang');
        $this->post('/login', [
            'email' => $gudang->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $operasional = $this->staff('operasional');
        $this->post('/login', [
            'email' => $operasional->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        // Customer tetap bisa login lewat halaman biasa.
        $customer = User::factory()->create(['role' => 'user', 'is_active' => true]);
        $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ])->assertRedirect(route('home'));
    }

    public function test_sidebar_menus_follow_staff_role(): void
    {
        // MenuSeeder memakai SQL khusus MySQL (FK checks) — pasang menunya manual (test jalan di SQLite).
        $menus = [
            ['Dashboard', 'admin.dashboard', 'Utama', 1, 1],
            ['Produk', 'admin.products.index', 'Produk', 2, 1],
            ['Kategori', 'admin.categories.index', 'Produk', 2, 3],
            ['Stok', 'admin.stocks.index', 'Stok', 3, 1],
            ['Stok Opname', 'admin.stock-opnames.index', 'Stok', 3, 2],
            ['Purchase Order', 'admin.purchase-orders.index', 'Pembelian', 4, 2],
            ['Pesanan', 'admin.orders.index', 'Penjualan', 5, 1],
            ['Users', 'admin.users.index', 'Pengaturan', 6, 1],
            ['Website', 'admin.website.edit', 'Pengaturan', 6, 2],
        ];
        foreach ($menus as [$name, $route, $group, $groupOrder, $order]) {
            \App\Models\Menu::create([
                'name' => $name,
                'icon' => 'fa-solid fa-circle',
                'route' => $route,
                'group' => $group,
                'group_order' => $groupOrder,
                'order' => $order,
                'is_active' => true,
            ]);
        }

        $admin = $this->staff('admin');
        $adminMenus = $this->menuNames($admin);
        $this->assertContains('Users', $adminMenus);
        $this->assertContains('Stok Opname', $adminMenus);
        $this->assertContains('Pesanan', $adminMenus);

        $gudangMenus = $this->menuNames($this->staff('gudang'));
        $this->assertContains('Dashboard', $gudangMenus);
        $this->assertContains('Stok', $gudangMenus);
        $this->assertContains('Purchase Order', $gudangMenus);
        $this->assertContains('Kategori', $gudangMenus);
        $this->assertNotContains('Users', $gudangMenus);
        $this->assertNotContains('Website', $gudangMenus);
        $this->assertNotContains('Pesanan', $gudangMenus);

        $operasionalMenus = $this->menuNames($this->staff('operasional'));
        $this->assertContains('Pesanan', $operasionalMenus);
        $this->assertNotContains('Stok', $operasionalMenus);
        $this->assertNotContains('Purchase Order', $operasionalMenus);
        $this->assertNotContains('Users', $operasionalMenus);

        // Sidebar rendered tanpa link ke menu yang dilarang.
        $this->actingAs($this->staff('gudang'))
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('/admin/users', false)
            ->assertDontSee('/admin/orders', false)
            ->assertSee('/admin/stocks', false);
    }

    public function test_admin_can_create_staff_accounts(): void
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin);

        $this->post(route('admin.users.store'), [
            'name' => 'Staf Gudang Baru',
            'email' => 'gudang@alkessbs.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'gudang',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $gudang = User::where('email', 'gudang@alkessbs.com')->firstOrFail();
        $this->assertSame('gudang', $gudang->role);
        $this->assertTrue($gudang->isStaff());
        $this->assertFalse($gudang->isAdmin());

        $this->post(route('admin.users.store'), [
            'name' => 'Operasional Baru',
            'email' => 'ops@alkessbs.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'operasional',
            'is_active' => '1',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertSame('operasional', User::where('email', 'ops@alkessbs.com')->firstOrFail()->role);

        // Role di luar daftar ditolak.
        $this->post(route('admin.users.store'), [
            'name' => 'Ngawur',
            'email' => 'ngawur@alkessbs.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'superuser',
        ])->assertSessionHasErrors('role');
    }
}
