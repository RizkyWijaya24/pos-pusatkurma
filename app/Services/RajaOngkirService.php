<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RajaOngkirService
{
    private string $apiKey;
    private string $baseUrl;
    private int $originCityId;

    public function __construct()
    {
        $this->apiKey      = config('rajaongkir.api_key', '');
        $this->baseUrl     = config('rajaongkir.base_url', 'https://api.rajaongkir.com/starter');
        $this->originCityId = (int) config('rajaongkir.origin_city_id', 82);
    }

    /**
     * Cek apakah API key sudah dikonfigurasi.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Ambil semua kota/kabupaten dari RajaOngkir.
     * Di-cache 24 jam untuk mengurangi API hit.
     *
     * @return array<int, array{city_id: string, city_name: string, province: string, type: string}>
     */
    public function getCities(): array
    {
        if (!$this->isConfigured()) {
            return $this->getMockCities();
        }

        $cacheKey = 'rajaongkir_cities';
        $cacheMins = config('rajaongkir.cities_cache_minutes', 1440);

        $cities = Cache::remember($cacheKey, now()->addMinutes($cacheMins), function () {
            try {
                $response = Http::withHeaders(['key' => $this->apiKey])
                    ->timeout(8) // Lower timeout slightly to fail faster in dev/prod
                    ->get("{$this->baseUrl}/city");

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['rajaongkir']['results'] ?? [];
                }

                Log::error('RajaOngkir getCities failed: ' . $response->body());
                return [];
            } catch (\Exception $e) {
                Log::error('RajaOngkir getCities exception: ' . $e->getMessage());
                return [];
            }
        });

        if (empty($cities)) {
            Log::info('RajaOngkir getCities empty, using fallback mock cities');
            return $this->getMockCities();
        }

        return $cities;
    }

    /**
     * Cari kota berdasarkan nama (autocomplete).
     *
     * @param string $search
     * @return array
     */
    public function searchCities(string $search): array
    {
        $cities = $this->getCities();
        if (empty($search) || empty($cities)) {
            return array_slice($cities, 0, 20);
        }

        $search = strtolower(trim($search));

        $filtered = array_filter($cities, function ($city) use ($search) {
            return str_contains(strtolower($city['city_name']), $search)
                || str_contains(strtolower($city['province']), $search);
        });

        return array_values(array_slice($filtered, 0, 30));
    }

    /**
     * Hitung ongkos kirim dari kota asal ke tujuan.
     *
     * @param int    $destinationCityId  ID kota tujuan (dari RajaOngkir)
     * @param int    $weightGrams        Berat total paket dalam gram
     * @param string $courier            Kode kurir: jne, jnt, sicepat, pos, dll
     * @return array  Array layanan dengan harga, e.g. [['service'=>'REG','cost'=>15000,'etd'=>'2-3 hari'], ...]
     */
    public function getCost(int $destinationCityId, int $weightGrams, string $courier): array
    {
        if (!$this->isConfigured()) {
            return $this->generateMockCosts($destinationCityId, $weightGrams, $courier);
        }

        // Minimum 1 kg (1000g) agar tidak error di beberapa kurir
        $weightGrams = max(1000, $weightGrams);

        try {
            $response = Http::withHeaders(['key' => $this->apiKey])
                ->timeout(8)
                ->asForm()
                ->post("{$this->baseUrl}/cost", [
                    'origin'      => $this->originCityId,
                    'destination' => $destinationCityId,
                    'weight'      => $weightGrams,
                    'courier'     => $courier,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $results = $data['rajaongkir']['results'][0]['costs'] ?? [];

                // Normalisasi ke format konsisten
                return array_map(function ($cost) use ($courier) {
                    return [
                        'courier'      => strtoupper($courier),
                        'service'      => $cost['service'],
                        'description'  => $cost['description'],
                        'cost'         => (int) ($cost['cost'][0]['value'] ?? 0),
                        'etd'          => trim($cost['cost'][0]['etd'] ?? '-') . ' hari',
                    ];
                }, $results);
            }

            Log::warning('RajaOngkir getCost failed, using mock fallback', [
                'destination' => $destinationCityId,
                'courier'     => $courier,
                'response'    => $response->body(),
            ]);

        } catch (\Exception $e) {
            Log::error('RajaOngkir getCost exception: ' . $e->getMessage() . ', using mock fallback');
        }

        return $this->generateMockCosts($destinationCityId, $weightGrams, $courier);
    }

    /**
     * Hitung ongkir dari beberapa kurir sekaligus.
     *
     * @param int      $destinationCityId
     * @param int      $weightGrams
     * @param string[] $couriers  Array kode kurir
     * @return array
     */
    public function getMultipleCourierCosts(int $destinationCityId, int $weightGrams, array $couriers): array
    {
        $all = [];
        foreach ($couriers as $courier) {
            $results = $this->getCost($destinationCityId, $weightGrams, $courier);
            foreach ($results as $r) {
                $all[] = $r;
            }
        }
        // Sort by cost ascending
        usort($all, fn($a, $b) => $a['cost'] <=> $b['cost']);
        return $all;
    }

    /**
     * Fallback daftar kota jika API RajaOngkir offline/error.
     * Mencakup semua kota/kabupaten penting di Indonesia.
     */
    private function getMockCities(): array
    {
        return [
            // ── Jawa Barat ────────────────────────────────────────
            ['city_id' => '82',  'city_name' => 'Cianjur',          'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '83',  'city_name' => 'Cianjur',          'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '23',  'city_name' => 'Bandung',          'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '22',  'city_name' => 'Bandung',          'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '79',  'city_name' => 'Bogor',            'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '80',  'city_name' => 'Bogor',            'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '115', 'city_name' => 'Depok',            'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '55',  'city_name' => 'Bekasi',           'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '54',  'city_name' => 'Bekasi',           'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '422', 'city_name' => 'Sukabumi',         'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '421', 'city_name' => 'Sukabumi',         'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '74',  'city_name' => 'Cirebon',          'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '75',  'city_name' => 'Cirebon',          'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '179', 'city_name' => 'Garut',            'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '396', 'city_name' => 'Tasikmalaya',      'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '397', 'city_name' => 'Tasikmalaya',      'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '289', 'city_name' => 'Karawang',         'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '269', 'city_name' => 'Purwakarta',       'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '400', 'city_name' => 'Subang',           'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '56',  'city_name' => 'Indramayu',        'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '292', 'city_name' => 'Kuningan',         'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '96',  'city_name' => 'Majalengka',       'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '94',  'city_name' => 'Sumedang',         'province' => 'Jawa Barat', 'type' => 'Kabupaten'],
            ['city_id' => '25',  'city_name' => 'Banjar',           'province' => 'Jawa Barat', 'type' => 'Kota'],
            ['city_id' => '74',  'city_name' => 'Pangandaran',      'province' => 'Jawa Barat', 'type' => 'Kabupaten'],

            // ── DKI Jakarta ───────────────────────────────────────
            ['city_id' => '152', 'city_name' => 'Jakarta Pusat',    'province' => 'DKI Jakarta', 'type' => 'Kota'],
            ['city_id' => '151', 'city_name' => 'Jakarta Barat',    'province' => 'DKI Jakarta', 'type' => 'Kota'],
            ['city_id' => '153', 'city_name' => 'Jakarta Selatan',  'province' => 'DKI Jakarta', 'type' => 'Kota'],
            ['city_id' => '154', 'city_name' => 'Jakarta Timur',    'province' => 'DKI Jakarta', 'type' => 'Kota'],
            ['city_id' => '155', 'city_name' => 'Jakarta Utara',    'province' => 'DKI Jakarta', 'type' => 'Kota'],

            // ── Banten ────────────────────────────────────────────
            ['city_id' => '456', 'city_name' => 'Tangerang',        'province' => 'Banten', 'type' => 'Kabupaten'],
            ['city_id' => '455', 'city_name' => 'Tangerang',        'province' => 'Banten', 'type' => 'Kota'],
            ['city_id' => '457', 'city_name' => 'Tangerang Selatan','province' => 'Banten', 'type' => 'Kota'],
            ['city_id' => '53',  'city_name' => 'Serang',           'province' => 'Banten', 'type' => 'Kabupaten'],
            ['city_id' => '403', 'city_name' => 'Cilegon',          'province' => 'Banten', 'type' => 'Kota'],
            ['city_id' => '149', 'city_name' => 'Lebak',            'province' => 'Banten', 'type' => 'Kabupaten'],
            ['city_id' => '254', 'city_name' => 'Pandeglang',       'province' => 'Banten', 'type' => 'Kabupaten'],

            // ── Jawa Tengah ───────────────────────────────────────
            ['city_id' => '399', 'city_name' => 'Semarang',         'province' => 'Jawa Tengah', 'type' => 'Kota'],
            ['city_id' => '427', 'city_name' => 'Surakarta (Solo)', 'province' => 'Jawa Tengah', 'type' => 'Kota'],
            ['city_id' => '263', 'city_name' => 'Purwokerto',       'province' => 'Jawa Tengah', 'type' => 'Kabupaten'],
            ['city_id' => '289', 'city_name' => 'Pekalongan',       'province' => 'Jawa Tengah', 'type' => 'Kota'],
            ['city_id' => '395', 'city_name' => 'Tegal',            'province' => 'Jawa Tengah', 'type' => 'Kota'],
            ['city_id' => '209', 'city_name' => 'Magelang',         'province' => 'Jawa Tengah', 'type' => 'Kota'],
            ['city_id' => '291', 'city_name' => 'Kudus',            'province' => 'Jawa Tengah', 'type' => 'Kabupaten'],

            // ── DI Yogyakarta ─────────────────────────────────────
            ['city_id' => '501', 'city_name' => 'Yogyakarta',       'province' => 'DI Yogyakarta', 'type' => 'Kota'],
            ['city_id' => '16',  'city_name' => 'Bantul',           'province' => 'DI Yogyakarta', 'type' => 'Kabupaten'],
            ['city_id' => '233', 'city_name' => 'Sleman',           'province' => 'DI Yogyakarta', 'type' => 'Kabupaten'],

            // ── Jawa Timur ────────────────────────────────────────
            ['city_id' => '444', 'city_name' => 'Surabaya',         'province' => 'Jawa Timur', 'type' => 'Kota'],
            ['city_id' => '256', 'city_name' => 'Malang',           'province' => 'Jawa Timur', 'type' => 'Kabupaten'],
            ['city_id' => '255', 'city_name' => 'Malang',           'province' => 'Jawa Timur', 'type' => 'Kota'],
            ['city_id' => '185', 'city_name' => 'Jember',           'province' => 'Jawa Timur', 'type' => 'Kabupaten'],
            ['city_id' => '61',  'city_name' => 'Banyuwangi',       'province' => 'Jawa Timur', 'type' => 'Kabupaten'],
            ['city_id' => '204', 'city_name' => 'Kediri',           'province' => 'Jawa Timur', 'type' => 'Kota'],
            ['city_id' => '276', 'city_name' => 'Madiun',           'province' => 'Jawa Timur', 'type' => 'Kota'],

            // ── Bali ──────────────────────────────────────────────
            ['city_id' => '105', 'city_name' => 'Denpasar',         'province' => 'Bali', 'type' => 'Kota'],
            ['city_id' => '16',  'city_name' => 'Badung',           'province' => 'Bali', 'type' => 'Kabupaten'],
            ['city_id' => '188', 'city_name' => 'Gianyar',          'province' => 'Bali', 'type' => 'Kabupaten'],

            // ── Sumatera Utara ────────────────────────────────────
            ['city_id' => '278', 'city_name' => 'Medan',            'province' => 'Sumatera Utara', 'type' => 'Kota'],
            ['city_id' => '20',  'city_name' => 'Binjai',           'province' => 'Sumatera Utara', 'type' => 'Kota'],
            ['city_id' => '280', 'city_name' => 'Deli Serdang',     'province' => 'Sumatera Utara', 'type' => 'Kabupaten'],

            // ── Sumatera Selatan ──────────────────────────────────
            ['city_id' => '252', 'city_name' => 'Palembang',        'province' => 'Sumatera Selatan', 'type' => 'Kota'],

            // ── Riau ──────────────────────────────────────────────
            ['city_id' => '268', 'city_name' => 'Pekanbaru',        'province' => 'Riau', 'type' => 'Kota'],
            ['city_id' => '69',  'city_name' => 'Batam',            'province' => 'Kepulauan Riau', 'type' => 'Kota'],

            // ── Kalimantan ────────────────────────────────────────
            ['city_id' => '215', 'city_name' => 'Balikpapan',       'province' => 'Kalimantan Timur', 'type' => 'Kota'],
            ['city_id' => '295', 'city_name' => 'Samarinda',        'province' => 'Kalimantan Timur', 'type' => 'Kota'],
            ['city_id' => '93',  'city_name' => 'Banjarmasin',      'province' => 'Kalimantan Selatan', 'type' => 'Kota'],
            ['city_id' => '244', 'city_name' => 'Pontianak',        'province' => 'Kalimantan Barat', 'type' => 'Kota'],

            // ── Sulawesi ──────────────────────────────────────────
            ['city_id' => '241', 'city_name' => 'Makassar',         'province' => 'Sulawesi Selatan', 'type' => 'Kota'],
            ['city_id' => '260', 'city_name' => 'Manado',           'province' => 'Sulawesi Utara', 'type' => 'Kota'],
            ['city_id' => '292', 'city_name' => 'Kendari',          'province' => 'Sulawesi Tenggara', 'type' => 'Kota'],

            // ── NTB & NTT ─────────────────────────────────────────
            ['city_id' => '283', 'city_name' => 'Mataram',          'province' => 'Nusa Tenggara Barat', 'type' => 'Kota'],
            ['city_id' => '213', 'city_name' => 'Kupang',           'province' => 'Nusa Tenggara Timur', 'type' => 'Kota'],

            // ── Papua ─────────────────────────────────────────────
            ['city_id' => '248', 'city_name' => 'Jayapura',         'province' => 'Papua', 'type' => 'Kota'],
        ];
    }


    /**
     * Fallback biaya ongkir jika API RajaOngkir offline/error.
     */
    private function generateMockCosts(int $destinationCityId, int $weightGrams, string $courier): array
    {
        $weightKg = ceil($weightGrams / 1000);
        $baseRate = 12000; // default (e.g. Jawa Barat)

        if ($destinationCityId == 82) { // Cianjur (local)
            $baseRate = 8000;
        } elseif (in_array($destinationCityId, [23, 79, 115, 55])) { // Bandung, Bogor, Depok, Bekasi
            $baseRate = 10000;
        } elseif (in_array($destinationCityId, [151, 152, 153, 154, 155, 456])) { // Jakarta / Tangerang
            $baseRate = 12000;
        } elseif (in_array($destinationCityId, [444, 501, 399, 427, 256])) { // Jawa Timur / Tengah
            $baseRate = 18000;
        } else { // Luar Jawa
            $baseRate = 35000;
        }

        $courierLower = strtolower($courier);

        if ($courierLower === 'jne') {
            return [
                [
                    'courier'     => 'JNE',
                    'service'     => 'REG',
                    'description' => 'Layanan Reguler JNE',
                    'cost'        => (int) ($baseRate * $weightKg),
                    'etd'         => '2-3 hari',
                ],
                [
                    'courier'     => 'JNE',
                    'service'     => 'YES',
                    'description' => 'Yakin Esok Sampai',
                    'cost'        => (int) (($baseRate + 10000) * $weightKg),
                    'etd'         => '1-1 hari',
                ]
            ];
        } elseif ($courierLower === 'jnt') {
            return [
                [
                    'courier'     => 'J&T',
                    'service'     => 'EZ',
                    'description' => 'Regular Service J&T',
                    'cost'        => (int) (($baseRate - 1000) * $weightKg),
                    'etd'         => '2-3 hari',
                ]
            ];
        } elseif ($courierLower === 'jntcargo') {
            $cargoWeightKg = max(5, $weightKg); // Minimum berat kargo 5kg
            $cargoBaseRate = max(3000, round($baseRate * 0.45)); // Lebih murah per kg (45% tarif reguler)
            return [
                [
                    'courier'     => 'J&T CARGO',
                    'service'     => 'CARGO',
                    'description' => 'Layanan Kargo J&T (Tarif Min. Rp 38.000)',
                    'cost'        => (int) max(38000, $cargoBaseRate * $cargoWeightKg),
                    'etd'         => '3-6 hari',
                ]
            ];
        } elseif ($courierLower === 'sicepat') {
            return [
                [
                    'courier'     => 'SICEPAT',
                    'service'     => 'SIUNTUK',
                    'description' => 'Layanan Reguler SiCepat',
                    'cost'        => (int) ($baseRate * $weightKg),
                    'etd'         => '2-3 hari',
                ]
            ];
        } else {
            return [
                [
                    'courier'     => strtoupper($courier),
                    'service'     => 'REG',
                    'description' => 'Layanan Reguler ' . strtoupper($courier),
                    'cost'        => (int) ($baseRate * $weightKg),
                    'etd'         => '2-4 hari',
                ]
            ];
        }
    }
}
