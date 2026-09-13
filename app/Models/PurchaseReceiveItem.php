<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReceiveItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_receive_id', 'product_id', 'variant_id', 'quantity_received',
    ];

    protected $casts = [
        'quantity_received' => 'integer',
    ];

    public function purchaseReceive()
    {
        return $this->belongsTo(PurchaseReceive::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }
}
