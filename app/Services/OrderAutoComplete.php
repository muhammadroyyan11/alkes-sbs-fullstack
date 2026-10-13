<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Jalankan auto-complete pesanan terkirim (N hari) dari dalam request web,
 * dengan kunci cache supaya tidak dipanggil tiap request.
 *
 * Pelengkap scheduler (php artisan schedule:run) supaya tetap jalan walau
 * container scheduler belum hidup — sama seperti OrderExpiry.
 */
class OrderAutoComplete
{
    public const LOCK_KEY = 'orders:auto-complete.lock';

    public static function run(): void
    {
        try {
            // Aktif paling cepat tiap 5 menit per aplikasi.
            if (! Cache::add(self::LOCK_KEY, 1, 300)) {
                return;
            }

            // Kalau dimatikan di pengaturan, lewati (lock tetap terpakai, hemat query).
            if (Setting::int('auto_complete_days') <= 0) {
                return;
            }

            Artisan::call('orders:auto-complete');
        } catch (\Throwable $e) {
            Log::warning('OrderAutoComplete gagal dijalankan: ' . $e->getMessage());
        }
    }
}
