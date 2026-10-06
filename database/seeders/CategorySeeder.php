<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Kategori contoh + pemetaan produk (hanya untuk produk yang belum punya kategori).
     * Aman dijalankan berulang (idempoten).
     */
    public function run(): void
    {
        $map = [
            'Diagnosa' => ['tensimeter', 'glukometer', 'stetoskop', 'termometer', 'oximeter', 'timbangan', 'alat ukur'],
            'Perawatan Luka' => ['masker', 'plester', 'kasa', 'betadin', 'underpad', 'sarung tangan', 'handsanitizer'],
            'Mobilitas & Disabilitas' => ['kursi roda', 'tongkat', 'walking', 'kruk', 'brankar'],
            'Respirasi' => ['nebulizer', 'oxygen', 'oksigen', 'inhaler', 'spuit'],
        ];

        foreach ($map as $name => $keywords) {
            $category = Category::firstOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($name)],
                ['name' => $name, 'is_active' => true]
            );

            Product::whereNull('category_id')
                ->where(function ($query) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $query->orWhere('name', 'like', '%' . $keyword . '%');
                    }
                })
                ->update(['category_id' => $category->id]);
        }

        Category::firstOrCreate(
            ['slug' => 'alat-kesehatan-lainnya'],
            ['name' => 'Alat Kesehatan Lainnya', 'is_active' => true]
        );
    }
}
