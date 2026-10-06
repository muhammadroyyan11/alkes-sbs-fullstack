<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('rajaongkir.api_key');
        $this->baseUrl = config('rajaongkir.type') === 'pro'
            ? 'https://api.rajaongkir.com/pro'
            : 'https://api.rajaongkir.com/starter';
    }

    public function getProvinces(): array
    {
        try {
            $response = Http::withHeaders(['key' => $this->apiKey])
                ->get("{$this->baseUrl}/province");

            if ($response->successful()) {
                return $response->json('rajaongkir.results', []);
            }

            Log::error('RajaOngkir provinces error', ['status' => $response->status()]);
            return [];
        } catch (\Exception $e) {
            Log::error('RajaOngkir provinces exception: ' . $e->getMessage());
            return [];
        }
    }

    public function getCities(string $provinceId = ''): array
    {
        try {
            $params = $provinceId !== '' ? ['province' => $provinceId] : [];
            $response = Http::withHeaders(['key' => $this->apiKey])
                ->get("{$this->baseUrl}/city", $params);

            if ($response->successful()) {
                return $response->json('rajaongkir.results', []);
            }

            Log::error('RajaOngkir cities error', ['status' => $response->status()]);
            return [];
        } catch (\Exception $e) {
            Log::error('RajaOngkir cities exception: ' . $e->getMessage());
            return [];
        }
    }

    public function getSubDistricts(string $cityId): array
    {
        try {
            $response = Http::withHeaders(['key' => $this->apiKey])
                ->get("{$this->baseUrl}/subdistrict", ['city' => $cityId]);

            if ($response->successful()) {
                return $response->json('rajaongkir.results', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error('RajaOngkir subdistricts exception: ' . $e->getMessage());
            return [];
        }
    }

    public function getCost(
        string $origin,
        string $destination,
        int $weight,
        string $courier = 'jne'
    ): array {
        try {
            $response = Http::withHeaders(['key' => $this->apiKey])
                ->asForm()
                ->post("{$this->baseUrl}/cost", [
                    'origin' => $origin,
                    'destination' => $destination,
                    'weight' => $weight,
                    'courier' => $courier,
                ]);

            if ($response->successful()) {
                $results = $response->json('rajaongkir.results', []);

                if (!empty($results[0]['costs'])) {
                    return $results[0]['costs'];
                }
            }

            Log::error('RajaOngkir cost error', ['status' => $response->status(), 'body' => $response->body()]);
            return [];
        } catch (\Exception $e) {
            Log::error('RajaOngkir cost exception: ' . $e->getMessage());
            return [];
        }
    }

    public function getMultipleCourierCosts(
        string $origin,
        string $destination,
        int $weight,
        array $couriers = ['jne', 'tiki', 'pos']
    ): array {
        $allCosts = [];

        foreach ($couriers as $courier) {
            $costs = $this->getCost($origin, $destination, $weight, $courier);
            $allCosts[$courier] = $costs;
        }

        return $allCosts;
    }
}
