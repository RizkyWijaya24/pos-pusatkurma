<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\RajaOngkirService;
use Illuminate\Http\Request;

class ShippingController extends Controller
{
    private RajaOngkirService $service;

    public function __construct(RajaOngkirService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /shop/shipping/cities?search=bandung
     * AJAX: Autocomplete kota tujuan pengiriman.
     */
    public function cities(Request $request)
    {
        $search = mb_substr(trim($request->get('search', '')), 0, 50);

        if (!$this->service->isConfigured()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Layanan ongkir belum dikonfigurasi. Silakan hubungi admin.',
                'cities'  => [],
            ], 503);
        }

        $cities = $this->service->searchCities($search);

        return response()->json([
            'status' => 'ok',
            'cities' => $cities,
        ]);
    }

    /**
     * POST /shop/shipping/cost
     * AJAX: Hitung ongkir dari beberapa kurir.
     *
     * Request body: { destination_city_id, weight_grams, couriers[] }
     */
    public function cost(Request $request)
    {
        $request->validate([
            'destination_city_id' => 'required|integer|min:1',
            'weight_grams'        => 'required|integer|min:1',
            'couriers'            => 'required|array|min:1',
            'couriers.*'          => 'string|in:jne,jnt,jntcargo,sicepat,pos,tiki,anteraja',
        ]);

        if (!$this->service->isConfigured()) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Layanan ongkir belum dikonfigurasi.',
                'results' => [],
            ], 503);
        }

        $results = $this->service->getMultipleCourierCosts(
            (int) $request->destination_city_id,
            (int) $request->weight_grams,
            $request->couriers
        );

        if (empty($results)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ongkir tidak tersedia untuk tujuan ini. Silakan hubungi kami via WhatsApp.',
                'results' => [],
            ]);
        }

        return response()->json([
            'status'  => 'ok',
            'results' => $results,
        ]);
    }
}
