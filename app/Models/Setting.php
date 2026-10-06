<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Pengaturan aplikasi (tabel `settings`): satu baris per key.
 *
 * Nilai yang belum pernah disimpan memakai default statis sehingga
 * aplikasi tetap jalan tanpa seeder. Simpan lewat Setting::putMany().
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static array $defaults = [
        // Identitas website
        'site_name' => 'ALKES SBS',
        'site_description' => 'Sistem Bisnis Alat Kesehatan',
        'site_email' => '',
        'site_phone' => '',
        'site_address' => '',
        'site_banner' => '',
        'site_banner_title' => '',
        'site_banner_subtitle' => '',

        // Kontak & media sosial
        'wa_number' => '',
        'instagram_url' => '',
        'facebook_url' => '',
        'youtube_url' => '',

        // Marketplace
        'tokopedia_url' => '',
        'shopee_url' => '',
        'lazada_url' => '',
        'blibli_url' => '',
        'tiktok_shop_url' => '',

        // Pengiriman
        'shipping_origin_city' => 'Malang',
        'shipping_origin_province' => 'Jawa Timur',
        'shipping_origin_postal' => '',
        'shipping_origin_id' => '',
        'shipping_couriers' => 'jne',
        'shipping_regular_cost' => '20000',
        'shipping_instant_cost' => '35000',
        'shipping_instant_enabled' => '1',
        'shipping_instant_areas' => '',

        // Biaya transaksi
        'admin_fee' => '1000',
        'admin_fee_label' => 'Biaya Admin',

        // Sakelar fitur (1 = aktif, 0 = nonaktif)
        'feature_reviews' => '1',
        'feature_wishlist' => '0',
        'feature_live_chat' => '1',
        'feature_cod' => '0',
    ];

    /**
     * Semua pengaturan (default digabung nilai tersimpan).
     */
    public static function values(): array
    {
        return Cache::rememberForever('settings.values', function () {
            $stored = self::query()->pluck('value', 'key')->all();

            return array_merge(self::$defaults, array_filter(
                $stored,
                fn ($key) => array_key_exists($key, self::$defaults),
                ARRAY_FILTER_USE_KEY
            ));
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $values = self::values();

        return $values[$key] ?? $default;
    }

    public static function int(string $key, ?int $default = null): int
    {
        $value = self::get($key, $default);

        return (int) $value;
    }

    public static function bool(string $key): bool
    {
        return in_array((string) self::get($key, '0'), ['1', 'true', 'yes'], true);
    }

    /**
     * Simpan beberapa pengaturan sekaligus (hanya key yang dikenal).
     */
    public static function putMany(array $pairs): void
    {
        foreach ($pairs as $key => $value) {
            if (!array_key_exists($key, self::$defaults)) {
                continue;
            }

            self::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        self::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget('settings.values');
    }
}
