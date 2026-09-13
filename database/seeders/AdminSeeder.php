<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@alkessbs.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('password'),
                'phone' => '082231311799',
                'is_active' => true,
                'role' => 'superadmin',
            ]
        );

        $admin->assignRole('superadmin');
    }
}
