<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pesanan belum bayar otomatis dibatalkan setelah 30 menit + stok dikembalikan.
Schedule::command('orders:expire-unpaid')->everyMinute();
