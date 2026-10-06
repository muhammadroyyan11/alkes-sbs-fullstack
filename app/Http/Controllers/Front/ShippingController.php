<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\RajaOngkirService;
use App\Services\ShippingQuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    public function __construct(
        protected RajaOngkirService $rajaongkir,
        protected ShippingQuoteService $quotes,
    ) {
    }

    /**
     * Ambil opsi ongkir untuk alamat tertentu (dipakai halaman checkout).
     * Biaya per opsi juga disimpan di sesi sebagai sumber kebenaran server.
     */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address_id' => 'required|integer',
        ]);

        $address = $request->user()
            ->addresses()
            ->whereKey($data['address_id'])
            ->firstOrFail();

        $weight = $this->quotes->weightForCart($request->session()->get('cart', []));
        $options = $this->quotes->quote($address, $weight);

        $request->session()->put(
            'shipping_quotes.' . $address->id,
            collect($options)->mapWithKeys(fn ($option) => [
                $option['code'] => [
                    'cost' => $option['cost'],
                    'courier' => $option['courier'],
                    'service' => $option['service'],
                ],
            ])->all()
        );

        return response()->json([
            'address_id' => $address->id,
            'weight' => $weight,
            'options' => $options,
        ]);
    }

    public function getProvinces(): JsonResponse
    {
        $provinces = $this->rajaongkir->getProvinces();
        return response()->json($provinces);
    }

    public function getCities(string $provinceId): JsonResponse
    {
        $cities = $this->rajaongkir->getCities($provinceId);
        return response()->json($cities);
    }

    public function getCost(Request $request): JsonResponse
    {
        $data = $request->validate([
            'origin' => 'required|string',
            'destination' => 'required|string',
            'weight' => 'required|integer|min:1',
            'courier' => 'required|in:jne,tiki,pos',
        ]);

        $costs = $this->rajaongkir->getCost(
            $data['origin'],
            $data['destination'],
            $data['weight'],
            $data['courier']
        );

        return response()->json($costs);
    }
}
