<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'status',
        'payment_status',
        'payment_method',
        'shipping_method',
        'subtotal',
        'shipping_cost',
        'total',
        'shipping_address',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForCustomerStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'unpaid' => $query
                ->where('payment_status', 'unpaid')
                ->where('payment_method', '!=', 'cod'),
            'processing' => $query
                ->whereIn('status', ['pending', 'confirmed', 'processing'])
                ->where(function (Builder $query) {
                    $query->where('payment_status', 'paid')
                        ->orWhere('payment_method', 'cod');
                }),
            'shipped' => $query->where(function (Builder $query) {
                $query->whereIn('status', ['shipped', 'in_transit'])
                    ->orWhereHas('shipment', fn (Builder $query) => $query->whereIn('status', ['shipped', 'in_transit']));
            }),
            'completed' => $query->where(function (Builder $query) {
                $query->whereIn('status', ['completed', 'delivered'])
                    ->orWhereHas('shipment', fn (Builder $query) => $query->where('status', 'delivered'));
            }),
            default => $query,
        };
    }

    public function customerStatus(): string
    {
        if (in_array($this->status, ['completed', 'delivered'], true) || $this->shipment?->status === 'delivered') {
            return 'completed';
        }

        if (in_array($this->status, ['shipped', 'in_transit'], true) || in_array($this->shipment?->status, ['shipped', 'in_transit'], true)) {
            return 'shipped';
        }

        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        if ($this->payment_status === 'unpaid' && $this->payment_method !== 'cod') {
            return 'unpaid';
        }

        return 'processing';
    }

    public function customerStatusLabel(): string
    {
        return match ($this->customerStatus()) {
            'unpaid' => 'Belum Dibayar',
            'processing' => 'Sedang Diproses',
            'shipped' => 'Sedang Dikirim',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
        };
    }
}
