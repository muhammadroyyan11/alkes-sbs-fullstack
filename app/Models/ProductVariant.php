<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductVariant extends Model
{
    protected $fillable = ['product_id', 'name', 'sku', 'price', 'weight', 'stock_qty', 'attributes', 'is_active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'weight' => 'decimal:2',
            'attributes' => 'array',
            'is_active' => 'boolean',
            'stock_qty' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stock(): HasOne
    {
        return $this->hasOne(Stock::class);
    }

    public function stock_mutations(): HasMany
    {
        return $this->hasMany(StockMutation::class);
    }

    public function purchase_order_items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function purchase_receive_items(): HasMany
    {
        return $this->hasMany(PurchaseReceiveItem::class);
    }

    public function stock_opname_items(): HasMany
    {
        return $this->hasMany(StockOpnameItem::class);
    }
}
