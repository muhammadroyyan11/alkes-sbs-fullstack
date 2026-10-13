<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'status',
        'payment_status',
        'payment_method',
        'snap_token',
        'midtrans_order_id',
        'paid_at',
        'payment_reference',
        'refunded_at',
        'refund_reason',
        'refunded_by',
        'refund_proof',
        'shipping_method',
        'subtotal',
        'shipping_cost',
        'admin_fee',
        'total',
        'shipping_address',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    /**
     * Terima ID maupun order number di URL, mis. /pembayaran/5/selesai
     * dan /pembayaran/SBS-20261002-9728CC/selesai sama-sama jalan.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        if ($field !== null) {
            return parent::resolveRouteBinding($value, $field);
        }

        if (is_numeric($value)) {
            $order = $this->find($value);
            if ($order) {
                return $order;
            }
        }

        return $this->where('order_number', $value)->first();
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Target SLA pengolahan pesanan (jam) sejak pesanan dibuat.
     */
    public const SLA_HOURS = 24;

    /**
     * Pesanan yang masih harus ditangani admin (belum terkirim/selesai/batal).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['pending', 'confirmed', 'processing']);
    }

    /**
     * Lewat target SLA 1 hari dan masih belum diproses.
     */
    public function isOverdue(): bool
    {
        return in_array($this->status, ['pending', 'confirmed', 'processing'], true)
            && $this->created_at->lt(now()->subHour(self::SLA_HOURS));
    }

    /**
     * Umur pesanan dalam teks manusiawi: "35 menit", "5 jam", "2 hari 3 jam".
     */
    public function ageLabel(): string
    {
        return $this->durationLabel($this->ageMinutes());
    }

    /**
     * Sisa waktu sebelum lewat SLA, atau lama keterlambatan.
     * "Sisa 5 jam" / "Lewat 2 jam".
     */
    public function slaLabel(): string
    {
        $limit = self::SLA_HOURS * 60;
        $age = $this->ageMinutes();

        if ($age < $limit) {
            return 'Sisa ' . $this->durationLabel($limit - $age);
        }

        return 'Lewat ' . $this->durationLabel($age - $limit);
    }

    protected function ageMinutes(): int
    {
        return max(0, (int) round($this->created_at->diffInMinutes(now())));
    }

    protected function durationLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' menit';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours . ' jam';
        }

        $days = intdiv($hours, 24);
        $rest = $hours % 24;

        return $days . ' hari' . ($rest > 0 ? ' ' . $rest . ' jam' : '');
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function shipment()
    {
        return $this->hasOne(Shipment::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Kembalikan stok yang telah dikurangi saat checkout.
     * Aman dipanggil berulang (idempoten) selama transisi status dijaga pemanggilnya.
     *
     * Sekaligus menulis log masuk (IN - cancel transaction) dan menutup
     * log keluar yang masih berstatus waiting_for_checkout.
     */
    public function restoreStock(?string $reason = null, ?int $userId = null): void
    {
        $reason = $reason ?: 'cancel transaction';
        $log = app(\App\Services\StockLogService::class);

        foreach ($this->items as $item) {
            if ($item->variant_id) {
                $item->variant()?->increment('stock', $item->quantity);
                $model = $item->variant;
            } else {
                $item->product()?->increment('stock', $item->quantity);
                $model = $item->product;
            }

            $log->recordIn(
                $this,
                $item->variant_id,
                $item->product_id,
                (int) $item->quantity,
                $reason,
                (int) ($model?->stock ?? 0),
                $userId
            );
        }

        // Reservasi yang belum pernah dibayar ditutup menjadi cancelled.
        $this->stockLogs()
            ->where('type', 'out')
            ->where('status', \App\Models\StockMutation::STATUS_WAITING)
            ->update(['status' => \App\Models\StockMutation::STATUS_CANCELLED]);
    }

    /**
     * Pesanan lunas -> log keluar berubah dari waiting_for_checkout ke completed.
     */
    public function markReservationPaid(): void
    {
        $this->stockLogs()
            ->where('type', 'out')
            ->where('status', \App\Models\StockMutation::STATUS_WAITING)
            ->update([
                'status' => \App\Models\StockMutation::STATUS_COMPLETED,
                'note' => 'OUT - completed (dibayar): ' . $this->order_number,
            ]);
    }

    /**
     * Batalkan pesanan + kembalikan stok + tutup log stok (satu pintu).
     * Dipakai oleh admin, webhook gagal bayar, dan pembatalan otomatis 30 menit.
     */
    public function cancelAndRelease(string $reason, ?int $userId = null): bool
    {
        if ($this->status === 'cancelled') {
            return false;
        }

        DB::transaction(function () use ($reason, $userId) {
            $this->update([
                'status' => 'cancelled',
                'payment_status' => $this->payment_status === 'paid' ? 'paid' : 'failed',
            ]);

            $this->load('items');
            $this->restoreStock($reason, $userId);

            $shipment = $this->shipment;
            if ($shipment && $shipment->status === 'waiting') {
                $shipment->update(['status' => 'cancelled']);
            }
        });

        return true;
    }

    /**
     * Log keluar-masuk barang milik pesanan ini.
     */
    public function stockLogs()
    {
        return $this->hasMany(\App\Models\StockMutation::class, 'reference_id')
            ->where('reference_type', static::class)
            ->latest();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Menunggu Konfirmasi',
            'confirmed' => 'Terkonfirmasi',
            'processing' => 'Sedang Diproses',
            'shipped' => 'Dikirim',
            'in_transit' => 'Dalam Perjalanan',
            'delivered' => 'Terkirim',
            'completed' => 'Selesai',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Refund',
            default => ucfirst($this->status),
        };
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'badge-warning',
            'confirmed' => 'badge-info',
            'processing' => 'badge-info',
            'shipped', 'in_transit' => 'badge-primary',
            'delivered', 'completed' => 'badge-success',
            'cancelled' => 'badge-danger',
            'refunded' => 'badge-warning',
            default => 'badge-secondary',
        };
    }

    public function paymentStatusLabel(): string
    {
        return match ($this->payment_status) {
            'paid' => 'Lunas',
            'failed' => 'Gagal',
            'refunded' => 'Dikembalikan',
            default => 'Belum Dibayar',
        };
    }

    public function paymentBadgeClass(): string
    {
        return match ($this->payment_status) {
            'paid' => 'badge-success',
            'failed' => 'badge-danger',
            'refunded' => 'badge-warning',
            default => 'badge-warning',
        };
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
            'refunded' => $query->where(function (Builder $query) {
                $query->where('status', 'refunded')
                    ->orWhere('payment_status', 'refunded');
            }),
            default => $query,
        };
    }

    public function customerStatus(): string
    {
        if ($this->status === 'refunded' || $this->payment_status === 'refunded') {
            return 'refunded';
        }

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
            'refunded' => 'Dana Dikembalikan',
        };
    }

    /**
     * Konfirmasi "Pesanan Diterima" oleh customer (gaya marketplace).
     * Hanya pesanan yang sudah dikirim/diterima yang boleh diselesaikan.
     */
    public function markReceived(): bool
    {
        if (! in_array($this->status, ['shipped', 'in_transit', 'delivered'], true)) {
            return false;
        }

        $this->update(['status' => 'completed']);

        $shipment = $this->shipment;
        if ($shipment) {
            $shipment->update([
                'status' => 'delivered',
                'delivered_at' => $shipment->delivered_at ?: now(),
            ]);
        }

        return true;
    }

    /**
     * Prosedur refund: dana dikembalikan + barang masuk kembali ke stok.
     *
     * Syarat: pesanan lunas (paid) dan belum pernah dibatalkan/direfund.
     * Efek:
     *  - status `refunded`, payment_status `refunded`, alasan + waktu + admin tercatat
     *  - stok jual & buku gudang naik kembali (IN - refund) untuk setiap item
     *  - seluruh mutasi OUT pesanan ini ditutup `cancelled`
     *
     * Pemanggil (controller) melanjutkan dengan panggilan refund Midtrans
     * best-effort setelah transaksi DB sukses.
     */
    public function applyRefund(string $reason, ?int $adminId = null, ?string $proofPath = null): bool
    {
        if ($this->payment_status !== 'paid' || in_array($this->status, ['cancelled', 'refunded'], true)) {
            return false;
        }

        DB::transaction(function () use ($reason, $adminId, $proofPath) {
            $this->load('items');

            $log = app(\App\Services\StockLogService::class);

            foreach ($this->items as $item) {
                if ($item->variant_id) {
                    $item->variant()?->increment('stock', $item->quantity);
                    $model = $item->variant;
                } else {
                    $item->product()?->increment('stock', $item->quantity);
                    $model = $item->product;
                }

                $log->recordRefund(
                    $this,
                    $item->variant_id,
                    $item->product_id,
                    (int) $item->quantity,
                    $reason,
                    (int) ($model?->stock ?? 0),
                    $adminId
                );
            }

            // Barang sudah kembali: tutup semua mutasi OUT milik pesanan ini.
            $this->stockLogs()
                ->where('type', 'out')
                ->update(['status' => \App\Models\StockMutation::STATUS_CANCELLED]);

            $this->update([
                'status' => 'refunded',
                'payment_status' => 'refunded',
                'refunded_at' => now(),
                'refund_reason' => $reason,
                'refunded_by' => $adminId,
                'refund_proof' => $proofPath ?: $this->refund_proof,
            ]);
        });

        return true;
    }
}
