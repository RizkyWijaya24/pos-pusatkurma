<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\StockLocation;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;


class CatalogController extends Controller
{
    /** In-request cache for online location — avoids duplicate queries per request. */
    private static ?StockLocation $cachedOnlineLocation = null;
    private static bool $locationResolved = false;

    /**
     * Temukan atau tentukan "Cabang Rumah" sebagai sumber stok utama toko.
     * Cached within the request to prevent multiple identical DB queries.
     */
    private function getOnlineLocation(): ?StockLocation
    {
        if (!self::$locationResolved) {
            self::$cachedOnlineLocation = StockLocation::where('name', 'Cabang Rumah')
                ->where('is_active', true)
                ->first();
            self::$locationResolved = true;
        }
        return self::$cachedOnlineLocation;
    }

    /**
     * GET /shop — Halaman katalog produk publik
     */
    public function index(Request $request)
    {
        $onlineLocation = $this->getOnlineLocation();

        // Sanitize inputs — cap search to 80 chars to prevent CPU abuse via huge Levenshtein inputs
        $search    = mb_substr(trim($request->get('search', '')), 0, 80);
        $category  = trim($request->get('category', ''));
        $sort      = $request->get('sort', 'name_asc');

        // Whitelist sort values to prevent query injection
        $allowedSorts = ['name_asc', 'name_desc', 'price_asc', 'price_desc'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'name_asc';
        }

        // Ambil semua kategori unik untuk filter
        $categories = Product::select('category')
            ->where('is_active_in_shop', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->filter()
            ->sort()
            ->values();

        // Query produk
        $query = Product::where('is_active', true)->where('is_active_in_shop', true);

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            // Fetch only the columns needed for fuzzy matching — avoids loading price_tiers JSON blob etc.
            $allProducts = $query->select(['id', 'name', 'sku', 'category', 'selling_price', 'price_unit', 'image_path', 'weight_grams', 'price_tiers'])->get();

            $filteredProducts = [];
            $searchQueryNormalized = strtolower(trim($search));
            $searchWords = array_filter(explode(' ', $searchQueryNormalized));

            foreach ($allProducts as $product) {
                $productName = strtolower($product->name);
                $productSku = strtolower($product->sku ?? '');
                $productCategory = strtolower($product->category ?? '');

                // Check exact substring match on name, sku or category (relevance score 0)
                if (str_contains($productName, $searchQueryNormalized) || 
                    str_contains($productSku, $searchQueryNormalized) || 
                    str_contains($productCategory, $searchQueryNormalized)) {
                    $product->search_score = 0;
                    $filteredProducts[] = $product;
                    continue;
                }

                // If not exact substring, check fuzzy matches word-by-word
                $productWords = array_filter(explode(' ', "{$productName} {$productSku} {$productCategory}"));
                $totalDistance = 0;
                $allMatched = true;

                foreach ($searchWords as $sWord) {
                    // Skip single-character noise words (prepositions like 'di', 'ke' handled by longer matches)
                    if (strlen($sWord) < 2) continue;
                    $bestWordDist = null;

                    // Substring match on word level
                    foreach ($productWords as $pWord) {
                        if (str_contains($pWord, $sWord)) {
                            $bestWordDist = 0;
                            break;
                        }
                    }

                    // Levenshtein match on word level
                    if ($bestWordDist === null) {
                        foreach ($productWords as $pWord) {
                            $dist = levenshtein($sWord, $pWord);
                            $len = strlen($sWord);
                            $threshold = ($len <= 2) ? 0 : (($len <= 5) ? 1 : 2);

                            if ($dist <= $threshold) {
                                if ($bestWordDist === null || $dist < $bestWordDist) {
                                    $bestWordDist = $dist;
                                }
                            }
                        }
                    }

                    // If a search word didn't match any word in the product, reject this product
                    if ($bestWordDist === null) {
                        $allMatched = false;
                        break;
                    }

                    $totalDistance += $bestWordDist;
                }

                if ($allMatched) {
                    $product->search_score = $totalDistance;
                    $filteredProducts[] = $product;
                }
            }

            // Perform sorting
            usort($filteredProducts, function ($a, $b) use ($sort) {
                if ($sort === 'price_asc') {
                    return $a->selling_price <=> $b->selling_price;
                } elseif ($sort === 'price_desc') {
                    return $b->selling_price <=> $a->selling_price;
                } elseif ($sort === 'name_desc') {
                    // Sort by search relevance score first, then name desc
                    if ($a->search_score !== $b->search_score) {
                        return $a->search_score <=> $b->search_score;
                    }
                    return strcasecmp($b->name, $a->name);
                } else {
                    // Default or name_asc: Sort by search relevance score first, then name asc
                    if ($a->search_score !== $b->search_score) {
                        return $a->search_score <=> $b->search_score;
                    }
                    return strcasecmp($a->name, $b->name);
                }
            });

            // Handle Manual Pagination
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 12;
            $currentPageItems = array_slice($filteredProducts, ($currentPage - 1) * $perPage, $perPage);

            $products = new LengthAwarePaginator(
                $currentPageItems,
                count($filteredProducts),
                $perPage,
                $currentPage,
                [
                    'path' => LengthAwarePaginator::resolveCurrentPath(),
                    'query' => $request->query(),
                ]
            );
        } else {
            // Default query sorting and database pagination when there is no search query
            match ($sort) {
                'price_asc'  => $query->orderBy('selling_price', 'asc'),
                'price_desc' => $query->orderBy('selling_price', 'desc'),
                'name_desc'  => $query->orderBy('name', 'desc'),
                default      => $query->orderBy('name', 'asc'),
            };

            $products = $query->paginate(12)->withQueryString();
        }

        // Load featured products
        $featuredIdsJson = \App\Models\Setting::get('featured_product_ids', '[]');
        $featuredIds = json_decode($featuredIdsJson, true) ?: [];

        $featuredProducts = collect();
        if (!empty($featuredIds)) {
            $featuredProducts = Product::whereIn('id', $featuredIds)->where('is_active_in_shop', true)->get();
            // Attach display stock
            $featuredProducts->transform(function ($product) use ($onlineLocation) {
                if ($onlineLocation) {
                    $product->display_stock = $product->getStockAtLocation($onlineLocation->id);
                } else {
                    $product->display_stock = $product->getTotalStock();
                }
                return $product;
            });
        }

        // Lampirkan info stok ke setiap produk
        $products->getCollection()->transform(function ($product) use ($onlineLocation) {
            if ($onlineLocation) {
                $product->display_stock = $product->getStockAtLocation($onlineLocation->id);
            } else {
                $product->display_stock = $product->getTotalStock();
            }
            return $product;
        });

        // Load active banners for the promo slider
        $banners = Banner::active()->get();

        return view('shop.index', compact(
            'products',
            'categories',
            'search',
            'category',
            'sort',
            'onlineLocation',
            'featuredProducts',
            'banners'
        ));
    }

    /**
     * GET /shop/product/{product} — Halaman detail produk
     */
    public function show(Product $product)
    {
        if (!$product->is_active || !$product->is_active_in_shop) {
            abort(404, 'Produk tidak ditemukan atau tidak tersedia.');
        }

        $onlineLocation = $this->getOnlineLocation();

        if ($onlineLocation) {
            $displayStock = $product->getStockAtLocation($onlineLocation->id);
        } else {
            $displayStock = $product->getTotalStock();
        }

        // Ambil produk terkait (kategori sama), maksimal 4
        $relatedProducts = Product::where('category', $product->category)
            ->where('is_active', true)
            ->where('is_active_in_shop', true)
            ->where('id', '!=', $product->id)
            ->limit(4)
            ->get()
            ->map(function ($p) use ($onlineLocation) {
                if ($onlineLocation) {
                    $p->display_stock = $p->getStockAtLocation($onlineLocation->id);
                } else {
                    $p->display_stock = $p->getTotalStock();
                }
                return $p;
            });

        // Nomor WhatsApp toko (sinkron dengan pengaturan database)
        $whatsappNumber = \App\Models\Setting::get('shop_whatsapp', '6281234567890');

        // Ambil ulasan yang sudah disetujui (10 terbaru)
        $reviewsQuery = ProductReview::where('product_id', $product->id)->approved();
        
        $totalReviewsCount = (clone $reviewsQuery)->count();
        $rawAvgRating = (clone $reviewsQuery)->avg('rating');
        $avgRating = $rawAvgRating ? round((float)$rawAvgRating, 1) : null;

        $reviews = $reviewsQuery->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return view('shop.show', compact(
            'product',
            'displayStock',
            'relatedProducts',
            'whatsappNumber',
            'reviews',
            'avgRating',
            'totalReviewsCount'
        ));
    }

    /**
     * GET /shop/checkout — Halaman formulir alamat pengiriman
     */
    public function checkout()
    {
        return view('shop.checkout');
    }

    /**
     * GET /shop/hampers — Halaman Hampers Builder
     */
    public function hampers()
    {
        $onlineLocation = $this->getOnlineLocation();

        $products = Product::where('is_active', true)
            ->where('is_active_in_shop', true)
            ->where('is_bundle', false) // Hanya produk individual
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->map(function ($p) use ($onlineLocation) {
                $p->display_stock = $onlineLocation
                    ? $p->getStockAtLocation($onlineLocation->id)
                    : $p->getTotalStock();
                return $p;
            });

        $categories = $products->pluck('category')->unique()->filter()->sort()->values();

        return view('shop.hampers', compact('products', 'categories'));
    }

    /**
     * POST /shop/checkout — Simpan pesanan ke database & buat sesi pembayaran iPaymu
     */
    public function storeOrder(Request $request)
    {
        $request->validate([
            'name'                  => 'required|string|max:255',
            'phone'                 => 'required|string|max:30',
            'email'                 => 'required|email|max:255',
            'address'               => 'required|string',
            'notes'                 => 'nullable|string|max:500',
            'items'                 => 'required|array|min:1',
            'items.*.id'            => 'required|exists:products,id',
            'items.*.qty'           => 'required|numeric|min:0.01',
            // Shipping fields — wajib jika fitur ongkir aktif
            'destination_city_id'   => 'required|integer|min:1',
            'destination_city_name' => 'required|string|max:100',
            'shipping_courier'      => 'required|string|in:jne,jnt,jntcargo,sicepat,pos,tiki,anteraja',
            'shipping_service'      => 'required|string|max:20',
            'shipping_service_name' => 'required|string|max:100',
            'shipping_cost'         => 'required|integer|min:0',
            'shipping_etd'          => 'nullable|string|max:50',
            'payment_channel'       => 'required|string|in:QRIS,VIRTUAL_ACCOUNT,EMONEY,RETAIL',
            'coupon_code'           => 'nullable|string|max:30',
            'referral_code'         => 'nullable|string|max:30',
        ]);

        try {
            // Validate stock levels before placing the order (aggregated to handle duplicates)
            $onlineLocation = $this->getOnlineLocation();
            $aggregatedItems = [];
            foreach ($request->items as $cartItem) {
                $product = Product::find($cartItem['id']);
                if (!$product) {
                    throw new \Exception("Produk dengan ID {$cartItem['id']} tidak ditemukan.");
                }
                $pId = $product->id;
                if (!isset($aggregatedItems[$pId])) {
                    $aggregatedItems[$pId] = [
                        'product' => $product,
                        'qty' => 0.0,
                    ];
                }
                $aggregatedItems[$pId]['qty'] += floatval($cartItem['qty']);
            }

            foreach ($aggregatedItems as $agg) {
                $product = $agg['product'];
                $qtyRequested = $agg['qty'];
                
                $availableStock = $onlineLocation 
                    ? $product->getStockAtLocation($onlineLocation->id) 
                    : $product->getTotalStock();

                if ($availableStock < $qtyRequested) {
                    throw new \Exception('Stok produk "' . $product->name . '" tidak mencukupi! Tersedia: ' . $availableStock . ' ' . $product->price_unit . ', diminta: ' . $qtyRequested . ' ' . $product->price_unit);
                }
            }

            return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
                // 1. Re-validasi ongkir di server-side untuk mencegah manipulasi harga dari client
                $rajaOngkir = app(\App\Services\RajaOngkirService::class);
                if ($rajaOngkir->isConfigured()) {
                    $freeShippingIdsJson = \App\Models\Setting::get('free_shipping_product_ids', '[]');
                    $freeShippingIds     = json_decode($freeShippingIdsJson, true) ?: [];

                    $totalWeightGrams = 0;
                    foreach ($request->items as $cartItem) {
                        $product = Product::find($cartItem['id']);
                        if ($product) {
                            // Abaikan berat jika produk gratis ongkir
                            if (in_array($product->id, $freeShippingIds)) {
                                continue;
                            }
                            $qty = floatval($cartItem['qty']);
                            if ($product->price_unit === 'gram') {
                                $totalWeightGrams += $qty;
                            } else {
                                $extractedWeight = $this->extractWeightFromName($product->name);
                                if ($extractedWeight && $extractedWeight > 0) {
                                    $totalWeightGrams += $extractedWeight * $qty;
                                } elseif ($product->weight_grams > 0) {
                                    $totalWeightGrams += $product->weight_grams * $qty;
                                } else {
                                    $totalWeightGrams += 1000 * $qty;
                                }
                            }
                        }
                    }

                    if ($totalWeightGrams > 0) {
                        $serverCosts = $rajaOngkir->getCost(
                            (int) $request->destination_city_id,
                            (int) $totalWeightGrams,
                            $request->shipping_courier
                        );

                        // Cari layanan yang sesuai dan validasi harga
                        $matchedService = null;
                        foreach ($serverCosts as $c) {
                            if (strtoupper($c['service']) === strtoupper($request->shipping_service)) {
                                $matchedService = $c;
                                break;
                            }
                        }

                        if ($matchedService && abs($matchedService['cost'] - (int)$request->shipping_cost) > 5000) {
                            throw new \Exception("Biaya ongkir tidak valid. Silakan refresh dan pilih ulang ekspedisi.");
                        }
                    } else {
                        // Semua barang gratis ongkir -> ongkir harus 0
                        if ((int) $request->shipping_cost !== 0) {
                            throw new \Exception("Biaya ongkir untuk produk ini adalah Gratis Ongkir (Rp 0).");
                        }
                    }
                }

                // 2. Buat record Order utama
                $order = \App\Models\Order::create([
                    'order_code'           => \App\Models\Order::generateOrderCode(),
                    'customer_name'        => $request->name,
                    'customer_phone'       => $request->phone,
                    'customer_email'       => $request->email,
                    'shipping_address'     => $request->address,
                    'shipping_notes'       => $request->notes,
                    // Shipping data
                    'destination_city_id'   => $request->destination_city_id,
                    'destination_city_name' => $request->destination_city_name,
                    'shipping_courier'      => strtoupper($request->shipping_courier),
                    'shipping_service'      => strtoupper($request->shipping_service),
                    'shipping_service_name' => $request->shipping_service_name,
                    'shipping_cost'         => (int) $request->shipping_cost,
                    'shipping_etd'          => $request->shipping_etd,
                    // Totals sementara
                    'subtotal_amount'      => 0,
                    'total_amount'         => 0,
                    'payment_status'       => 'pending',
                ]);

                // 3. Simpan item pesanan & hitung subtotal di server-side (anti-manipulasi harga)
                $subtotalAmount = 0;
                foreach ($request->items as $cartItem) {
                    $product = Product::find($cartItem['id']);
                    if (!$product) {
                        throw new \Exception("Produk dengan ID {$cartItem['id']} tidak ditemukan.");
                    }

                    $qty = floatval($cartItem['qty']);
                    $price    = $product->getPriceForQuantity($qty);
                    $subtotal = $price * $qty;
                    $subtotalAmount += $subtotal;

                    \App\Models\OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $product->id,
                        'qty'        => $qty,
                        'price'      => $price,
                        'subtotal'   => $subtotal,
                    ]);
                }

                // 4. Update total = subtotal produk + ongkir - diskon + biaya transaksi DOKU (per channel)
                $shippingCost = (int) $request->shipping_cost;

                // ── Hitung diskon kupon ──
                $couponDiscount  = 0;
                $couponCodeUsed  = null;
                if (!empty($request->coupon_code)) {
                    $coupon = \App\Models\Coupon::where('code', strtoupper(trim($request->coupon_code)))->first();
                    if ($coupon && $coupon->isValid((int)$subtotalAmount)) {
                        $couponDiscount = $coupon->calculateDiscount((int)$subtotalAmount, $shippingCost);
                        $couponCodeUsed = $coupon->code;
                        // Increment used_count
                        $coupon->increment('used_count');
                    }
                }

                // ── Hitung diskon referral ──
                $referralDiscount  = 0;
                $referralCodeUsed  = null;
                if (!empty($request->referral_code)) {
                    $referral = \App\Models\ReferralCode::where('code', strtoupper(trim($request->referral_code)))->first();
                    if ($referral && $referral->isValid((int)$subtotalAmount)) {
                        $referralDiscount = $referral->calculateDiscount((int)$subtotalAmount);
                        $referralCodeUsed = $referral->code;
                        // Increment used_count
                        $referral->increment('used_count');
                    }
                }

                // ── Hitung biaya transaksi DOKU sesuai channel yang dipilih pembeli ──
                $feeEnabled     = \App\Models\Setting::get('payment_fee_enabled', '1');
                $channel        = $request->payment_channel; // QRIS | VIRTUAL_ACCOUNT | EMONEY | RETAIL
                $baseForFee     = $subtotalAmount + $shippingCost - $couponDiscount - $referralDiscount;
                $baseForFee     = max(0, $baseForFee);

                $paymentFee = 0;
                if ($feeEnabled === '1') {
                    $paymentFee = match ($channel) {
                        'QRIS'            => (int) ceil($baseForFee * 0.007),   // 0,7%
                        'VIRTUAL_ACCOUNT' => 4000,                               // Rp 4.000 flat
                        'EMONEY'          => (int) ceil($baseForFee * 0.015),   // 1,5%
                        'RETAIL'          => 5000,                               // Rp 5.000 flat
                        default           => (int) \App\Models\Setting::get('payment_fee_value', '4000'),
                    };
                }

                $totalAmount = $subtotalAmount + $shippingCost - $couponDiscount - $referralDiscount + $paymentFee;
                $totalAmount = max(1000, $totalAmount); // minimum Rp 1.000
                $order->update([
                    'subtotal_amount'   => (int) $subtotalAmount,
                    'coupon_code'       => $couponCodeUsed,
                    'coupon_discount'   => $couponDiscount,
                    'referral_code'     => $referralCodeUsed,
                    'referral_discount' => $referralDiscount,
                    'payment_fee'       => $paymentFee,
                    'payment_channel'   => $channel,
                    'total_amount'      => (int) $totalAmount,
                ]);

                // 5. Buat sesi pembayaran DOKU (jika diaktifkan)
                $paymentUrl = null;
                if (config('doku.enabled', true)) {
                    $dokuResult = \App\Services\DokuService::createPayment($order, $channel);

                    if (!$dokuResult) {
                        throw new \Exception("Gagal menghubungi layanan pembayaran DOKU. Silakan coba lagi.");
                    }

                    $paymentUrl = $dokuResult['url'];
                    // Simpan URL pembayaran di kolom snap_token (repurposed)
                    $order->update(['snap_token' => $paymentUrl]);
                }

                // Kirim notifikasi ke admin & owner
                $admins = \App\Models\User::whereIn('role', ['admin', 'owner'])->get();
                foreach ($admins as $admin) {
                    $admin->notify(new \App\Notifications\OrderNotification($order, 'created'));
                }

                // Store order code in session so only this browser session can view the success page
                session(['last_order_code' => $order->order_code]);

                return response()->json([
                    'status'      => 'success',
                    'payment_url' => $paymentUrl,
                    'order_id'    => $order->id,
                    'order_code'  => $order->order_code,
                ]);
            });
        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /shop/order/success/{order} — Halaman status sukses pesanan
     * Security: Only accessible from the browser session that placed the order.
     */
    public function orderSuccess(\App\Models\Order $order)
    {
        // Validate that this session owns the order — prevents order peeking by ID guessing
        $lastOrderCode = session('last_order_code');
        if (empty($lastOrderCode) || $lastOrderCode !== $order->order_code) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        $order->load('orderItems.product');
        return view('shop.success', compact('order'));
    }

    /**
     * Ekstrak berat (gram) dari nama produk.
     */
    private function extractWeightFromName(string $name): ?int
    {
        $str = strtolower($name);
        
        // Cek pola: angka diikuti "kg"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*kg/', $str, $matches)) {
            $value = str_replace(',', '.', $matches[1]);
            return (int) round(floatval($value) * 1000);
        }
        
        // Cek pola: angka diikuti "gr" atau "gram"
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*gr(?:am)?/', $str, $matches)) {
            $value = str_replace(',', '.', $matches[1]);
            return (int) round(floatval($value));
        }
        
        // Cek pola: angka diikuti "g" (standalone, not followed by letter)
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*g(?!\w)/', $str, $matches)) {
            $value = str_replace(',', '.', $matches[1]);
            return (int) round(floatval($value));
        }
        
        return null;
    }
}
