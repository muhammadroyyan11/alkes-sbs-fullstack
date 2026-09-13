<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'sku', 'price', 'stock', 'unit', 'description', 'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }

    public function getImageUrlAttribute(): string
    {
        $name = strtolower($this->name);

        $images = [
            'tensimeter' => 'gambar/tensimeter.jpg',
            'termometer' => 'monitor/Digital Thermometer MC-246.jpeg',
            'nebulizer' => 'monitor/OMRON (NE-C28).jpeg',
            'kursi roda' => 'disabilitas/KURSI RODA.jpeg',
            'masker' => 'images/products/masker-3ply.jpeg',
            'sarung tangan' => 'images/products/sarung-tangan.jpeg',
            'underpad' => 'kesehatan lainnya/Neo Health Underpad.jpeg',
            'walking stick' => 'disabilitas/SERENITY WALKING STICK FS925L.jpeg',
            'tongkat' => 'disabilitas/SERENITY WALKING STICK FS925L.jpeg',
            'glukometer' => 'monitor/Blood Glucose .jpeg',
            'kolesterol' => 'monitor/Blood Cholesterol.jpeg',
        ];

        foreach ($images as $keyword => $image) {
            if (str_contains($name, $keyword)) {
                return asset($image);
            }
        }

        return asset('gambar/alkes.jpg');
    }
}
