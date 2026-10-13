<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosSale extends Model
{
    use HasFactory;

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /** Metode pembayaran kasir (disesuaikan dengan POS global). */
    public const PAYMENT_METHODS = ['cash', 'transfer', 'qris', 'ewallet', 'card'];

    protected $fillable = [
        'invoice', 'user_id', 'customer_name',
        'subtotal', 'discount', 'total', 'paid_amount', 'change_amount',
        'payment_method', 'status', 'notes', 'sold_at', 'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'sold_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PosSaleItem::class);
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /** Jumlah item terjual (qty). */
    public function itemsQuantity(): int
    {
        return (int) $this->items()->sum('quantity');
    }

    public static function paymentMethodLabel(string $method): string
    {
        return match ($method) {
            'cash' => 'Tunai',
            'transfer' => 'Transfer',
            'qris' => 'QRIS',
            'ewallet' => 'E-Wallet',
            'card' => 'Kartu',
            default => ucfirst($method),
        };
    }
}
