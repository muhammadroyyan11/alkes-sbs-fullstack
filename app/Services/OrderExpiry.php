<?php

namespace App\Services;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Jalankan pembatalan otomatis pesanan belum bayar (30 menit) dari dalam
 * request web, dengan kunci cache agar tidak dipanggil tiap request.
 *
 * Pelengkap scheduler (php artisan schedule:run) supaya tetap jalan walau
 * proses scheduler belum hidup.
 */
class OrderExpiry
{
    public const MINUTES = 30;
    public const LOCK_KEY = 'orders:expire-unpaid.lock';

    public static function run(): void
    {
        try {
            // Cukup sekali per menit per aplikasi.
            if (!Cache::add(self::LOCK_KEY, 1, 60)) {
                return;
            }

            Artisan::call('orders:expire-unpaid', ['--minutes' => self::MINUTES]);
        } catch (\Throwable $e) {
            Log::warning('OrderExpiry gagal dijalankan: ' . $e->getMessage());
        }
    }
}
