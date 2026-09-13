<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            'manage-users',
            'manage-products',
            'manage-variants',
            'manage-stock',
            'manage-stock-opname',
            'manage-purchase-orders',
            'manage-website',
            'view-reports',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // Create roles and assign permissions
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->syncPermissions($permissions);

        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions([
            'manage-products',
            'manage-variants',
            'manage-stock',
            'manage-stock-opname',
            'manage-purchase-orders',
            'manage-website',
            'view-reports',
        ]);

        $gudang = Role::firstOrCreate(['name' => 'gudang']);
        $gudang->syncPermissions([
            'manage-stock',
            'manage-stock-opname',
            'manage-purchase-orders',
        ]);

        $cs = Role::firstOrCreate(['name' => 'cs']);
        $cs->syncPermissions([
            'view-reports',
        ]);

        $customer = Role::firstOrCreate(['name' => 'customer']);
    }
}
