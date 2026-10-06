<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoSendService
{
    protected string $apiKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('gosend.api_key');
        $this->baseUrl = config('gosend.base_url', 'https://api.rajaongkir.com/starter');
    }

    /**
     * Get instant delivery cost estimate via RajaOngkir GoSend
     */
    public function getCost(string $origin, string $destination, int $weight): array
    {
        try {
            $response = Http::withHeaders(['key' => $this->apiKey])
                ->asForm()
                ->post("{$this->baseUrl}/cost", [
                    'origin' => $origin,
                    'destination' => $destination,
                    'weight' => $weight,
                    'courier' => 'gosend',
                ]);

            if ($response->successful()) {
                $results = $response->json('rajaongkir.results', []);
                if (!empty($results[0]['costs'])) {
                    return $results[0]['costs'];
                }
            }

            Log::error('GoSend cost error', ['status' => $response->status()]);
            return $this->getDefaultEstimate();
        } catch (\Exception $e) {
            Log::error('GoSend cost exception: ' . $e->getMessage());
            return $this->getDefaultEstimate();
        }
    }

    /**
     * Default estimate when API is unavailable
     */
    protected function getDefaultEstimate(): array
    {
        return [[
            'service' => 'GOSEND_INSTANT',
            'description' => 'GoSend Instant Delivery',
            'cost' => [[
                'value' => 35000,
                'etd' => '1-2 JAM',
                'note' => 'Estimasi pengiriman instan',
            ]],
        ]];
    }
}
