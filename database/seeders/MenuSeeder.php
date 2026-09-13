<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menu;
use App\Models\User;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        \DB::table('menu_user')->truncate();
        Menu::truncate();
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $menus = [
            // ═══ UTAMA ═══
            ['name' => 'Dashboard', 'icon' => 'fa-solid fa-gauge', 'route' => 'admin.dashboard', 'group' => 'Utama', 'group_order' => 1, 'order' => 1],

            // ═══ PRODUK ═══
            ['name' => 'Produk', 'icon' => 'fa-solid fa-box', 'route' => 'admin.products.index', 'group' => 'Produk', 'group_order' => 2, 'order' => 1],
            ['name' => 'Variant', 'icon' => 'fa-solid fa-layer-group', 'route' => 'admin.variants.index', 'group' => 'Produk', 'group_order' => 2, 'order' => 2],

            // ═══ STOK ═══
            ['name' => 'Stok', 'icon' => 'fa-solid fa-cubes', 'route' => 'admin.stocks.index', 'group' => 'Stok', 'group_order' => 3, 'order' => 1],
            ['name' => 'Stok Opname', 'icon' => 'fa-solid fa-clipboard-check', 'route' => 'admin.stock-opnames.index', 'group' => 'Stok', 'group_order' => 3, 'order' => 2],

            // ═══ PEMBELIAN ═══
            ['name' => 'Supplier', 'icon' => 'fa-solid fa-truck', 'route' => 'admin.suppliers.index', 'group' => 'Pembelian', 'group_order' => 4, 'order' => 1],
            ['name' => 'Purchase Order', 'icon' => 'fa-solid fa-file-invoice', 'route' => 'admin.purchase-orders.index', 'group' => 'Pembelian', 'group_order' => 4, 'order' => 2],
            ['name' => 'Purchase Receive', 'icon' => 'fa-solid fa-boxes-stacked', 'route' => 'admin.purchase-receives.index', 'group' => 'Pembelian', 'group_order' => 4, 'order' => 3],

            // ═══ PENGATURAN ═══
            ['name' => 'Users', 'icon' => 'fa-solid fa-user-gear', 'route' => 'admin.users.index', 'group' => 'Pengaturan', 'group_order' => 5, 'order' => 1],
            ['name' => 'Website', 'icon' => 'fa-solid fa-globe', 'route' => 'admin.website.edit', 'group' => 'Pengaturan', 'group_order' => 5, 'order' => 2],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }

        // Assign semua menu ke semua user
        $allMenuIds = Menu::pluck('id')->toArray();
        $users = User::all();
        foreach ($users as $user) {
            $user->menus()->sync($allMenuIds);
        }

        $this->command->info('✅ Menu berhasil di-seed! (' . count($menus) . ' menu)');
    }
}
