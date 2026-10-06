<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMutation extends Model
{
    /**
     * Status pada log keluar-masuk barang.
     */
    public const STATUS_WAITING = 'waiting_for_checkout';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = ['stock_id', 'type', 'status', 'quantity', 'reference_type', 'reference_id', 'note', 'created_by'];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Referensi pemanggil (Order, PurchaseOrder, dst).
     */
    public function reference()
    {
        return $this->morphTo();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            'in' => 'IN (Masuk)',
            'out' => 'OUT (Keluar)',
            default => 'Penyesuaian',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING => 'Waiting for Checkout',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancel Transaction',
            default => ucfirst($this->status),
        };
    }

    public function typeBadgeClass(): string
    {
        return match ($this->type) {
            'in' => 'badge-success',
            'out' => 'badge-danger',
            default => 'badge-warning',
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_WAITING => 'badge-warning',
            self::STATUS_COMPLETED => 'badge-success',
            self::STATUS_CANCELLED => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
