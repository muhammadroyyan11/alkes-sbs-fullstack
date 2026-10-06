<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

/**
 * Perhitungan ongkos kirim untuk checkout.
 *
 * Prioritas:
 *  1. Tarif aktual RajaOngkir (kota asal & tujuan resolved dari nama kota)
 *  2. Opsi cadangan dari pengaturan admin (bila API tidak terkonfigurasi/gagal)
 *
 * Hasil quote disimpan di sesi per alamat supaya total yang dihitung server
 * tidak bisa dimanipulasi dari sisi klien.
 */
class ShippingQuoteService
{
    public const CODE_FALLBACK = 'regular';
    public const CODE_INSTANT = 'instant';

    public function __construct(protected RajaOngkirService $rajaongkir)
    {
    }

    /**
     * Berat pengiriman (gram). Produk belum punya kolom berat,
     * jadi pakai 1 kg per item sebagai default.
     */
    public function weightForCart(array $cart): int
    {
        $qty = collect($cart)->sum(fn ($item) => (int) ($item['quantity'] ?? 0));

        return max(1, $qty * 1000);
    }

    /**
     * Ongkir reguler per layanan (JNE REG, JNE YES, dst).
     */
    public function regularOptions(Address $address, int $weight): array
    {
        if (!$this->enabled()) {
            return [$this->fallbackOption()];
        }

        $origin = $this->originCityId();
        $destination = $this->resolveCityId($address->city);

        if ($origin && $destination) {
            $options = [];

            foreach ($this->couriers() as $courier) {
                foreach ($this->rajaongkir->getCost($origin, $destination, $weight, $courier) as $service) {
                    foreach ($service['cost'] ?? [] as $cost) {
                        $options[] = [
                            'code' => $courier . '-' . ($service['service'] ?? ''),
                            'courier' => strtoupper($courier),
                            'service' => $service['service'] ?? '',
                            'label' => strtoupper($courier) . ' ' . ($service['service'] ?? ''),
                            'etd' => (string) ($cost['etd'] ?? ''),
                            'cost' => (int) ($cost['value'] ?? 0),
                            'fallback' => false,
                        ];
                    }
                }
            }

            if ($options) {
                return collect($options)
                    ->sortBy('cost')
                    ->values()
                    ->all();
            }
        }

        return [$this->fallbackOption()];
    }

    /**
     * Opsi cadangan: tarif tetap dari pengaturan admin.
     */
    public function fallbackOption(): array
    {
        return [
            'code' => self::CODE_FALLBACK,
            'courier' => 'RajaOngkir',
            'service' => 'regular',
            'label' => 'Reguler',
            'etd' => '2-5 hari kerja',
            'cost' => max(0, Setting::int('shipping_regular_cost')),
            'fallback' => true,
        ];
    }

    /**
     * Pengiriman instan (GoSend) dari pengaturan admin.
     */
    public function instantOption(): array
    {
        return [
            'code' => self::CODE_INSTANT,
            'courier' => 'GoSend',
            'service' => 'instant',
            'label' => 'GoSend Instant',
            'etd' => '1-2 jam',
            'cost' => max(0, Setting::int('shipping_instant_cost')),
            'fallback' => true,
        ];
    }

    /**
     * Opsi instan hanya ditawarkan bila diaktifkan admin dan alamat
     * tujuan termasuk area layanan (kosong = semua area).
     */
    public function instantAvailableFor(Address $address): bool
    {
        if (!Setting::bool('shipping_instant_enabled')) {
            return false;
        }

        $areas = array_values(array_filter(array_map('trim', explode(
            ',',
            (string) Setting::get('shipping_instant_areas', '')
        ))));

        if (!$areas) {
            return true;
        }

        $city = mb_strtolower(trim((string) $address->city));

        foreach ($areas as $area) {
            $needle = mb_strtolower($area);
            if ($needle !== '' && str_contains($city, $needle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Semua opsi yang ditawarkan ke halaman checkout.
     */
    public function quote(Address $address, int $weight): array
    {
        $options = $this->regularOptions($address, $weight);

        if ($this->instantAvailableFor($address)) {
            $options[] = $this->instantOption();
        }

        return $options;
    }

    /**
     * Cari kode kota RajaOngkir dari nama kota (di-cache 1 hari).
     */
    public function resolveCityId(string $cityName): ?string
    {
        $name = trim(mb_strtolower($cityName));

        if ($name === '' || !$this->enabled()) {
            return null;
        }

        $cities = Cache::remember('rajaongkir.cities', now()->addDay(), function () {
            return $this->rajaongkir->getCities();
        });

        foreach ($cities as $city) {
            if (mb_strtolower((string) ($city['city_name'] ?? '')) === $name) {
                return (string) $city['city_id'];
            }
        }

        foreach ($cities as $city) {
            if (str_contains(mb_strtolower((string) ($city['city_name'] ?? '')), $name)) {
                return (string) $city['city_id'];
            }
        }

        return null;
    }

    /**
     * Kota asal: kode eksplisit di pengaturan, atau resolve dari nama kota.
     */
    public function originCityId(): ?string
    {
        $configured = trim((string) Setting::get('shipping_origin_id'));

        if ($configured !== '') {
            return $configured;
        }

        return $this->resolveCityId((string) Setting::get('shipping_origin_city'));
    }

    /**
     * RajaOngkir aktif hanya bila API key terisi.
     */
    public function enabled(): bool
    {
        return trim((string) config('rajaongkir.api_key')) !== '';
    }

    private function couriers(): array
    {
        $couriers = collect(explode(',', (string) Setting::get('shipping_couriers', 'jne')))
            ->map(fn ($courier) => trim(mb_strtolower($courier)))
            ->filter()
            ->unique()
            ->take(3)
            ->values()
            ->all();

        return $couriers ?: ['jne'];
    }
}
