<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = [
        'order_id', 'courier', 'service', 'tracking_number',
        'status', 'shipped_at', 'delivered_at',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Tautan pelacakan untuk customer.
     *
     * Kurir Indonesia umumnya tidak punya URL lacak per-nomor-resi yang
     * stabil, jadi tautan dibuat ke pencarian lacak resi (selalu valid)
     * dengan format "cek resi {kurir} {nomor}".
     */
    public function tracking_url(): ?string
    {
        $number = trim((string) $this->tracking_number);

        if ($number === '') {
            return null;
        }

        $courier = trim((string) $this->courier);

        return 'https://www.google.com/search?q=' . rawurlencode(
            'cek resi ' . ($courier !== '' ? $courier . ' ' : '') . $number
        );
    }
}
