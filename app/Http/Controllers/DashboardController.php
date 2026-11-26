<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the Admin Dashboard.
     */
    public function admin()
    {
        // Load productStocks agar accessor getStockAttribute mengembalikan total semua cabang
        $products = Product::select('id', 'sku', 'name', 'category', 'cost_price', 'selling_price',
                                    'price_unit', 'image_path', 'stock', 'weight_grams', 'price_tiers', 'is_bundle', 'is_active_in_shop', 'is_active')
                           ->with(['bundleItems', 'productStocks'])
                           ->get();

        $cashiers = User::where('role', 'kasir')->get()->map(function ($user) {
            return [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'branch'     => $user->branch ?? 'Cabang Rumah',
                'lastActive' => 'Aktif Sekarang' // default or dynamic placeholder
            ];
        });

        // Fix N+1: Hitung produk per kategori dalam 1 query GROUP BY
        $productCountsByCategory = Product::selectRaw('category, COUNT(*) as total')
            ->groupBy('category')
            ->pluck('total', 'category');

        $categories = \App\Models\Category::all()->map(function ($cat) use ($productCountsByCategory) {
            $cat->products_count = $productCountsByCategory->get($cat->name, 0);
            return $cat;
        });

        // Ambil semua settings untuk tab Pengaturan
        $settings = \App\Models\Setting::pluck('value', 'key')->all();

        // Auto-heal transaksi wholesale yang tersimpan metode Piutang / Tempo namun statusnya masih lunas akibat installment lama
        \App\Models\Transaction::where('transaction_type', 'wholesale')
            ->where('payment_method', 'Piutang / Tempo')
            ->where('payment_status', 'paid')
            ->whereHas('installments', function ($q) {
                $q->where('note', 'like', '%Lunas%');
            })
            ->each(function ($t) {
                $t->installments()->where('note', 'like', '%Lunas%')->delete();
                $t->update(['payment_status' => 'unpaid']);
            });

        // Ambil riwayat nota partai terbaru
        $wholesaleTransactions = \App\Models\Transaction::where('transaction_type', 'wholesale')
            ->with(['cashier', 'installments'])
            ->latest()
            ->take(10)
            ->get();

        // Ambil data rekap pelanggan nota partai & piutang berjalan mereka (select minimal kolom)
        $wholesaleCustomers = \App\Models\Transaction::where('transaction_type', 'wholesale')
            ->whereNotNull('customer_name')
            ->where('customer_name', '!=', '')
            ->select('id', 'customer_name', 'customer_phone', 'total_price', 'payment_status')
            ->with(['installments:id,transaction_id,amount'])
            ->get()
            ->groupBy(fn($t) => strtolower(trim($t->customer_name)))
            ->map(function ($group) {
                $first = $group->first();
                $unpaidTrx = $group->where('payment_status', 'unpaid');
                $outstandingDebt = $unpaidTrx->sum(function ($t) {
                    return max(0, $t->total_price - $t->installments->sum('amount'));
                });
                $unpaidCount = $unpaidTrx->count();
                $lastPhone = $group->whereNotNull('customer_phone')->where('customer_phone', '!=', '')->first()?->customer_phone;

                return [
                    'name' => $first->customer_name,
                    'phone' => $lastPhone ?? '',
                    'outstanding_debt' => (float) $outstandingDebt,
                    'unpaid_count' => (int) $unpaidCount,
                    'total_orders' => (int) $group->count(),
                ];
            })
            ->values();

        return view('admin.dashboard', compact('products', 'cashiers', 'categories', 'settings', 'wholesaleTransactions', 'wholesaleCustomers'));
    }

    /**
     * Build the active kasir catalog payload using a versioned cache key so any product/stock/category
     * change immediately invalidates stale entries without waiting for TTL expiry.
     */
    private function getKasirCatalogPayload($user, ?string $category = null): array
    {
        $myLocation = \App\Models\StockLocation::findByBranchName($user->branch ?? '');
        $locationId = $myLocation ? $myLocation->id : null;
        $version    = \App\Support\PosCache::productsVersion();

        $cacheCategoryKey = ($category && !in_array(strtolower(trim($category)), ['semua', 'all', '']))
            ? preg_replace('/[^a-zA-Z0-9]/', '', $category)
            : 'all';

        $cacheKey = "kasir_products_v{$version}_loc_" . ($locationId ?? 'all') . "_" . $cacheCategoryKey;

        $products = Cache::remember($cacheKey, 120, function () use ($category, $locationId) {
            $query = Product::where('is_active', true)->with(['productStocks', 'bundleItems.product.productStocks']);

            // Proteksi Strict Mode Database cPanel:
            // Jika parameter kategori diisi dan nilainya bukan "Semua"/"all"/"empty", lakukan filter WHERE
            if ($category && !in_array(strtolower(trim($category)), ['semua', 'all', ''])) {
                $query->where('category', $category);
            }

            $productsFromDb = $query->get();

            return $productsFromDb->map(function ($product) use ($locationId) {
                $stock = 0.0;
                if ($locationId) {
                    if ($product->is_bundle) {
                        if ($product->bundleItems->isEmpty()) {
                            $stock = 0.0;
                        } else {
                            $virtualStocks = $product->bundleItems->map(function ($item) use ($locationId) {
                                $compStock = 0.0;
                                if ($item->product->relationLoaded('productStocks')) {
                                    $ps = $item->product->productStocks->firstWhere('location_id', $locationId);
                                    $compStock = $ps ? (float) $ps->stock : 0.0;
                                } else {
                                    $compStock = (float) $item->product->getStockAtLocation($locationId);
                                }
                                return floor($compStock / max(1, $item->quantity));
                            });
                            $stock = (float) $virtualStocks->min();
                        }
                    } else {
                        $ps = $product->productStocks->firstWhere('location_id', $locationId);
                        $stock = $ps ? (float) $ps->stock : 0.0;
                    }
                } else {
                    $stock = (float) $product->stock; // fallback to legacy stock column
                }

                return [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'category' => $product->category,
                    'price' => (int) $product->selling_price, // map selling_price to price in PHP
                    'price_unit' => $product->price_unit,
                    'image_path' => $product->image_path,
                    'stock' => $stock,
                    'price_tiers' => $product->price_tiers ?? []
                ];
            })->values();
        });

        $categories = Cache::remember('categories_for_kasir', 1800,
            fn() => \App\Models\Category::select('id', 'name')->get()
        );

        return [
            'version'    => $version,
            'products'   => $products,
            'categories' => $categories,
        ];
    }

    /**
     * Lightweight endpoint for Kasir Dashboard to check and fetch catalog updates immediately.
     */
    public function kasirProductsSync(Request $request)
    {
        $clientVersion  = (string) $request->query('version', '');
        $currentVersion = \App\Support\PosCache::productsVersion();

        if ($clientVersion !== '' && $clientVersion === $currentVersion && !$request->boolean('force')) {
            return response()->json([
                'success' => true,
                'changed' => false,
                'version' => $currentVersion,
            ]);
        }

        $catalog = $this->getKasirCatalogPayload(auth()->user(), $request->query('category'));

        return response()->json([
            'success'    => true,
            'changed'    => true,
            'version'    => $catalog['version'],
            'products'   => $catalog['products'],
            'categories' => $catalog['categories'],
        ]);
    }

    public function kasir(Request $request)
    {
        $catalog         = $this->getKasirCatalogPayload(auth()->user(), $request->input('category'));
        $products        = $catalog['products'];
        $categories      = $catalog['categories'];
        $productsVersion = $catalog['version'];


        $todayStart = \Carbon\Carbon::today()->startOfDay();
        $todayEnd   = \Carbon\Carbon::today()->endOfDay();

        // Fetch transactions created today matching the logged-in cashier's ID (optimized whereBetween instead of whereDate for index usage)
        $todayTransactions = \App\Models\Transaction::where('cashier_id', auth()->id())
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->with('installments:id,transaction_id,amount,payment_method,note,created_at')
            ->latest('id')
            ->select('id', 'created_at', 'transaction_code', 'items_summary', 'payment_method', 'payment_status', 'customer_name', 'customer_phone', 'total_price', 'discount', 'branch')
            ->get();

        $todayTransactionsMapped = $todayTransactions->map(function($trx) {
            $bd = $trx->payment_breakdown;

            return [
                'id' => $trx->id,
                'time' => $trx->created_at->format('H:i'),
                'transaction_code' => $trx->transaction_code,
                'items_summary' => $trx->items_summary,
                'payment_method' => $trx->payment_method,
                'payment_status' => $trx->payment_status ?? 'paid',
                'customer_name' => $trx->customer_name,
                'customer_phone' => $trx->customer_phone,
                'total_price' => (int) $trx->total_price,
                'discount' => (int) ($trx->discount ?? 0),
                'paid_amount' => (int) round($trx->paid_amount),
                'remaining_amount' => (int) round($trx->remaining_amount),
                'cash_in_amount'               => (float) ($bd['cash'] ?? 0),
                'digital_receipt_url'          => \Illuminate\Support\Facades\URL::signedRoute('receipt.digital', ['transaction' => $trx->id]),
                'digital_receipt_download_url' => \Illuminate\Support\Facades\URL::signedRoute('receipt.digital', ['transaction' => $trx->id, 'download' => 1]),
            ];
        });

        // Fetch expenses created today by the logged-in cashier (optimized whereBetween)
        $todayExpenses = \App\Models\Expense::where('cashier_id', auth()->id())
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->latest('id')
            ->select('id', 'amount', 'category', 'description', 'created_at')
            ->get();

        $todayExpensesMapped = $todayExpenses->map(function($exp) {
            return [
                'id' => $exp->id,
                'amount' => (int) $exp->amount,
                'category' => $exp->category,
                'description' => $exp->description,
                'time' => $exp->created_at->format('H:i'),
                'date' => $exp->created_at->format('d/m/Y'),
            ];
        });

        // Ambil daftar agen B2B / Tour Leader aktif untuk pilihan komisi afiliasi di kasir
        $affiliates = \App\Models\Affiliate::active()
            ->select('id', 'name', 'code', 'agency_name', 'commission_rate_percentage')
            ->orderBy('name', 'asc')
            ->get();

        // Daftar pesanan DP yang belum lunas (lintas hari) untuk pelunasan cepat di dashboard kasir
        $kasirUser   = auth()->user();
        $kasirBranch = $kasirUser->branch ?? '';
        $unpaidDpMapped = \App\Models\Transaction::where('payment_method', 'DP')
            ->where('payment_status', 'unpaid')
            ->where(function ($q) use ($kasirUser, $kasirBranch) {
                $q->where('cashier_id', $kasirUser->id);
                if ($kasirBranch !== '') {
                    $q->orWhere('branch', $kasirBranch);
                }
            })
            ->with('installments:id,transaction_id,amount,payment_method,note,created_at')
            ->latest('id')
            ->get()
            ->map(function ($trx) {
                return [
                    'id'                           => $trx->id,
                    'transaction_code'             => $trx->transaction_code,
                    'created_at_formatted'         => $trx->created_at->translatedFormat('d M Y - H:i'),
                    'customer_name'                => $trx->customer_name ?? 'Pelanggan Umum',
                    'customer_phone'               => $trx->customer_phone ?? '',
                    'items_summary'                => $trx->items_summary,
                    'total_price'                  => (int) $trx->total_price,
                    'discount'                     => (int) ($trx->discount ?? 0),
                    'paid_amount'                  => (int) round($trx->paid_amount),
                    'remaining_amount'             => (int) round($trx->remaining_amount),
                    'digital_receipt_url'          => \Illuminate\Support\Facades\URL::signedRoute('receipt.digital', ['transaction' => $trx->id]),
                    'digital_receipt_download_url' => \Illuminate\Support\Facades\URL::signedRoute('receipt.digital', ['transaction' => $trx->id, 'download' => 1]),
                    'installments'                 => $trx->installments->map(fn($inst) => [
                        'id'             => $inst->id,
                        'amount'         => (int) $inst->amount,
                        'payment_method' => $inst->payment_method,
                        'note'           => $inst->note,
                        'date'           => $inst->created_at->translatedFormat('d M Y H:i'),
                    ])->values(),
                ];
            })->values();

        // Pelunasan tunai hari ini untuk transaksi DP dari hari sebelumnya
        $extraDpCashToday = (float) \App\Models\InstallmentPayment::whereBetween('installment_payments.created_at', [$todayStart, $todayEnd])
            ->whereIn('payment_method', ['Cash', 'Tunai'])
            ->whereHas('transaction', function ($q) use ($kasirUser, $todayStart) {
                $q->where('cashier_id', $kasirUser->id)
                  ->where('payment_method', 'DP')
                  ->where('created_at', '<', $todayStart)
                  ->where('payment_status', '!=', 'cancelled');
            })
            ->sum('amount');

        return view('kasir.dashboard', compact(
            'products',
            'todayTransactionsMapped',
            'todayExpensesMapped',
            'categories',
            'affiliates',
            'productsVersion',
            'unpaidDpMapped',
            'extraDpCashToday'
        ));
    }

    /**
     * Display the Owner Dashboard.
     */
    public function owner(Request $request)
    {
        // 1. Fetch stock alerts — count dari DB, hanya load data jika dibutuhkan untuk tampilan
        $lowStockCount    = Product::where('stock', '<=', 10)->count();
        $lowStockProducts = Product::where('stock', '<=', 10)
            ->select('id', 'name', 'sku', 'stock', 'category')
            ->orderBy('stock')
            ->get();

        // 2. Resolve Active Date Ranges
        $dateRanges = $this->resolveDateRanges($request);
        $filterType = $dateRanges['filterType'];
        $selectedDate = $dateRanges['selectedDate'];
        $selectedWeek = $dateRanges['selectedWeek'];
        $selectedMonth = $dateRanges['selectedMonth'];
        $start = $dateRanges['start'];
        $end = $dateRanges['end'];
        $comparisonStart = $dateRanges['comparisonStart'];
        $comparisonEnd = $dateRanges['comparisonEnd'];
        $startOfWeek = $dateRanges['startOfWeek'];
        $endOfWeek = $dateRanges['endOfWeek'];
        $startOfMonth = $dateRanges['startOfMonth'];
        $endOfMonth = $dateRanges['endOfMonth'];
        $comparisonLabel = $dateRanges['comparisonLabel'];
        $titleLabel = $dateRanges['titleLabel'];
        $dateObj = $dateRanges['dateObj'];

        // Resolve selected branch & sales channel filters
        $selectedBranch = $request->query('branch');
        $selectedChannel = $this->normalizeChannel($request->query('channel'));
        $isChannelFiltered = $selectedChannel !== 'all';

        // All unique branches for dropdown
        $branches = \DB::table('users')
            ->where('role', 'kasir')
            ->whereNotNull('branch')
            ->where('branch', '<>', '')
            ->select('branch')
            ->union(
                \DB::table('transactions')
                    ->whereNotNull('branch')
                    ->where('branch', '<>', '')
                    ->select('branch')
            )
            ->pluck('branch')
            ->sort()
            ->values();

        // Helper: branch-scoped query (all channels) vs branch + channel-scoped query
        $branchQuery = fn() => $this->makeTransactionQuery($selectedBranch, 'all');
        $baseQuery = fn() => $this->makeTransactionQuery($selectedBranch, $selectedChannel);

        $expenseQuery = fn() => ($selectedBranch && $selectedBranch !== '')
            ? \App\Models\Expense::where('branch', $selectedBranch)
            : \App\Models\Expense::query();

        // 3. Fetch dynamically filtered metrics and their comparative historical data via Single-Pass SQL Aggregation
        $activeStats = $baseQuery()
            ->selectRaw("
                SUM(total_price) as omset,
                SUM(total_price - total_cost) as gross_profit,
                COUNT(id) as count,
                AVG(total_price) as aov,
                SUM(CASE WHEN transaction_type = 'wholesale' THEN total_price ELSE 0 END) as wholesale_omset,
                COUNT(CASE WHEN transaction_type = 'wholesale' THEN 1 END) as wholesale_count,
                SUM(CASE WHEN transaction_type = 'wholesale' THEN (total_price - total_cost) ELSE 0 END) as wholesale_profit,
                SUM(CASE WHEN transaction_type != 'wholesale' OR transaction_type IS NULL THEN total_price ELSE 0 END) as retail_omset,
                COUNT(CASE WHEN transaction_type != 'wholesale' OR transaction_type IS NULL THEN 1 END) as retail_count,
                SUM(CASE WHEN transaction_type != 'wholesale' OR transaction_type IS NULL THEN (total_price - total_cost) ELSE 0 END) as retail_profit,
                SUM(CASE WHEN payment_method = 'Cash' THEN total_price ELSE 0 END) as cash_omset,
                COUNT(CASE WHEN payment_method = 'Cash' THEN 1 END) as cash_count,
                SUM(CASE WHEN payment_method = 'Transfer' THEN total_price ELSE 0 END) as transfer_omset,
                COUNT(CASE WHEN payment_method = 'Transfer' THEN 1 END) as transfer_count,
                SUM(CASE WHEN payment_method = 'QRIS' THEN total_price ELSE 0 END) as qris_omset,
                COUNT(CASE WHEN payment_method = 'QRIS' THEN 1 END) as qris_count,
                SUM(CASE WHEN payment_method = 'Piutang / Tempo' THEN total_price ELSE 0 END) as tempo_omset,
                COUNT(CASE WHEN payment_method = 'Piutang / Tempo' THEN 1 END) as tempo_count
            ")
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', '!=', 'cancelled')
            ->first();

        $comparisonStats = $baseQuery()
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as gross_profit, COUNT(id) as count, AVG(total_price) as aov')
            ->whereBetween('created_at', [$comparisonStart, $comparisonEnd])
            ->where('payment_status', '!=', 'cancelled')
            ->first();

        // Pengeluaran operasional dicatat per cabang (tidak terpisah per saluran).
        // Saat memfilter saluran spesifik (Retail/Wholesale), jangan kurangkan pengeluaran seluruh cabang ke satu saluran.
        $activeExpenses = $isChannelFiltered
            ? 0.0
            : (float) $expenseQuery()->whereBetween('created_at', [$start, $end])->sum('amount');

        $comparisonExpenses = $isChannelFiltered
            ? 0.0
            : (float) $expenseQuery()->whereBetween('created_at', [$comparisonStart, $comparisonEnd])->sum('amount');

        $activeRevenue = (float) ($activeStats->omset ?? 0);
        $activeGrossProfit = (float) ($activeStats->gross_profit ?? 0);
        $activeProfit = $activeGrossProfit - $activeExpenses;
        $activeTransactionsCount = (int) ($activeStats->count ?? 0);
        $activeAov = (float) ($activeStats->aov ?? 0);

        $comparisonRevenue = (float) ($comparisonStats->omset ?? 0);
        $comparisonGrossProfit = (float) ($comparisonStats->gross_profit ?? 0);
        $comparisonProfit = $comparisonGrossProfit - $comparisonExpenses;
        $comparisonTransactionsCount = (int) ($comparisonStats->count ?? 0);
        $comparisonAov = (float) ($comparisonStats->aov ?? 0);

        // Realisasi Kas Masuk (Kas Riil) & Piutang Tempo / DP Berjalan
        // A. Transaksi non-tempo/non-DP yang langsung lunas (retail & wholesale tunai/QRIS/Debit/transfer)
        $directCash = (float) $baseQuery()
            ->where('payment_status', '!=', 'cancelled')
            ->whereNotIn('payment_method', ['Piutang / Tempo', 'DP'])
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->sum('total_price');

        // B. Penerimaan cicilan & DP (wholesale Tempo maupun DP kasir) yang masuk pada rentang filter ini
        $installmentCashQuery = \App\Models\InstallmentPayment::whereBetween('installment_payments.created_at', [$start, $end])
            ->whereHas('transaction', function ($q) use ($selectedBranch, $selectedChannel) {
                $q->whereIn('payment_method', ['Piutang / Tempo', 'DP'])
                  ->where('payment_status', '!=', 'cancelled');
                if ($selectedBranch && $selectedBranch !== '') {
                    $q->where('branch', $selectedBranch);
                }
                $this->applyChannelScope($q, $selectedChannel);
            });
        $installmentCash = (float) $installmentCashQuery->sum('amount');

        $compInstallmentCashQuery = \App\Models\InstallmentPayment::whereBetween('installment_payments.created_at', [$comparisonStart, $comparisonEnd])
            ->whereHas('transaction', function ($q) use ($selectedBranch, $selectedChannel) {
                $q->whereIn('payment_method', ['Piutang / Tempo', 'DP'])
                  ->where('payment_status', '!=', 'cancelled');
                if ($selectedBranch && $selectedBranch !== '') {
                    $q->where('branch', $selectedBranch);
                }
                $this->applyChannelScope($q, $selectedChannel);
            });
        $compInstallmentCash = (float) $compInstallmentCashQuery->sum('amount');
        $realizedCash = $directCash + $installmentCash;

        // Perhitungan perbandingan kas masuk untuk periode lalu
        $compDirectCash = (float) $baseQuery()
            ->where('payment_status', '!=', 'cancelled')
            ->whereNotIn('payment_method', ['Piutang / Tempo', 'DP'])
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$comparisonStart, $comparisonEnd])
            ->sum('total_price');
        $comparisonRealizedCash = $compDirectCash + $compInstallmentCash;

        $cashGrowthPercent = 0;
        if ($comparisonRealizedCash > 0) {
            $cashGrowthPercent = (($realizedCash - $comparisonRealizedCash) / $comparisonRealizedCash) * 100;
        } elseif ($realizedCash > 0) {
            $cashGrowthPercent = 100;
        }

        // C. Piutang tempo & sisa DP yang belum cair (seluruh transaksi unpaid pada channel terpilih)
        $unpaidTempoTrxQuery = $baseQuery()
            ->whereIn('payment_method', ['Piutang / Tempo', 'DP'])
            ->where('payment_status', 'unpaid');
        $unpaidTempoTotal = (float) (clone $unpaidTempoTrxQuery)->sum('total_price');
        $unpaidTempoPaid = (float) \App\Models\InstallmentPayment::whereIn('transaction_id', (clone $unpaidTempoTrxQuery)->select('id'))->sum('amount');
        $outstandingReceivables = max(0, $unpaidTempoTotal - $unpaidTempoPaid);

        // Piutang / sisa DP yang belum cair khusus dari transaksi yang terjadi di periode aktif yang dipilih:
        $activePeriodTempoQuery = $baseQuery()
            ->whereIn('payment_method', ['Piutang / Tempo', 'DP'])
            ->where('payment_status', 'unpaid')
            ->whereBetween('created_at', [$start, $end]);
        $activePeriodTempoTotal = (float) (clone $activePeriodTempoQuery)->sum('total_price');
        $activePeriodTempoPaid = (float) \App\Models\InstallmentPayment::whereIn('transaction_id', (clone $activePeriodTempoQuery)->select('id'))->sum('amount');
        $activePeriodReceivables = max(0, $activePeriodTempoTotal - $activePeriodTempoPaid);

        // Calculate growth percentages
        $revenueGrowthPercent = 0;
        if ($comparisonRevenue > 0) {
            $revenueGrowthPercent = (($activeRevenue - $comparisonRevenue) / $comparisonRevenue) * 100;
        } elseif ($activeRevenue > 0) {
            $revenueGrowthPercent = 100;
        }

        $profitGrowthPercent = 0;
        if ($comparisonProfit > 0) {
            $profitGrowthPercent = (($activeProfit - $comparisonProfit) / $comparisonProfit) * 100;
        } elseif ($activeProfit > 0) {
            $profitGrowthPercent = 100;
        }

        $transactionGrowthPercent = 0;
        if ($comparisonTransactionsCount > 0) {
            $transactionGrowthPercent = (($activeTransactionsCount - $comparisonTransactionsCount) / $comparisonTransactionsCount) * 100;
        } elseif ($activeTransactionsCount > 0) {
            $transactionGrowthPercent = 100;
        }

        $expenseGrowthPercent = 0;
        if ($comparisonExpenses > 0) {
            $expenseGrowthPercent = (($activeExpenses - $comparisonExpenses) / $comparisonExpenses) * 100;
        } elseif ($activeExpenses > 0) {
            $expenseGrowthPercent = 100;
        }

        // Additional Granular Analytical Metrics:
        $grossMarginPercent = $activeRevenue > 0 ? round(($activeGrossProfit / $activeRevenue) * 100, 1) : 0;
        $netMarginPercent = $activeRevenue > 0 ? round(($activeProfit / $activeRevenue) * 100, 1) : 0;
        $oerPercent = $activeRevenue > 0 ? round(($activeExpenses / $activeRevenue) * 100, 1) : 0;
        $cashRealizationPercent = $activeRevenue > 0 ? round(($realizedCash / $activeRevenue) * 100, 1) : 0;

        $aovGrowthPercent = 0;
        if ($comparisonAov > 0) {
            $aovGrowthPercent = (($activeAov - $comparisonAov) / $comparisonAov) * 100;
        } elseif ($activeAov > 0) {
            $aovGrowthPercent = 100;
        }

        // Segmentasi Saluran selalu menampilkan komparasi Retail vs Wholesale untuk cabang & periode terpilih
        $channelSummaryStats = $isChannelFiltered
            ? $branchQuery()
                ->selectRaw("
                    SUM(total_price) as omset,
                    SUM(CASE WHEN transaction_type = 'wholesale' THEN total_price ELSE 0 END) as wholesale_omset,
                    COUNT(CASE WHEN transaction_type = 'wholesale' THEN 1 END) as wholesale_count,
                    SUM(CASE WHEN transaction_type = 'wholesale' THEN (total_price - total_cost) ELSE 0 END) as wholesale_profit,
                    SUM(CASE WHEN transaction_type != 'wholesale' OR transaction_type IS NULL THEN total_price ELSE 0 END) as retail_omset,
                    COUNT(CASE WHEN transaction_type != 'wholesale' OR transaction_type IS NULL THEN 1 END) as retail_count,
                    SUM(CASE WHEN transaction_type != 'wholesale' OR transaction_type IS NULL THEN (total_price - total_cost) ELSE 0 END) as retail_profit
                ")
                ->whereBetween('created_at', [$start, $end])
                ->where('payment_status', '!=', 'cancelled')
                ->first()
            : $activeStats;

        $channelTotalRevenue = (float) ($channelSummaryStats->omset ?? 0);

        $retailStats = [
            'omset' => (float) ($channelSummaryStats->retail_omset ?? 0),
            'count' => (int) ($channelSummaryStats->retail_count ?? 0),
            'profit' => (float) ($channelSummaryStats->retail_profit ?? 0),
            'share_percent' => $channelTotalRevenue > 0 ? round(((float) ($channelSummaryStats->retail_omset ?? 0) / $channelTotalRevenue) * 100, 1) : 0,
        ];

        $wholesaleStats = [
            'omset' => (float) ($channelSummaryStats->wholesale_omset ?? 0),
            'count' => (int) ($channelSummaryStats->wholesale_count ?? 0),
            'profit' => (float) ($channelSummaryStats->wholesale_profit ?? 0),
            'share_percent' => $channelTotalRevenue > 0 ? round(((float) ($channelSummaryStats->wholesale_omset ?? 0) / $channelTotalRevenue) * 100, 1) : 0,
        ];

        $paymentBreakdown = [
            'Cash' => [
                'omset' => (float) ($activeStats->cash_omset ?? 0),
                'count' => (int) ($activeStats->cash_count ?? 0),
                'share_percent' => $activeRevenue > 0 ? round(((float) ($activeStats->cash_omset ?? 0) / $activeRevenue) * 100, 1) : 0,
            ],
            'Transfer' => [
                'omset' => (float) ($activeStats->transfer_omset ?? 0),
                'count' => (int) ($activeStats->transfer_count ?? 0),
                'share_percent' => $activeRevenue > 0 ? round(((float) ($activeStats->transfer_omset ?? 0) / $activeRevenue) * 100, 1) : 0,
            ],
            'QRIS' => [
                'omset' => (float) ($activeStats->qris_omset ?? 0),
                'count' => (int) ($activeStats->qris_count ?? 0),
                'share_percent' => $activeRevenue > 0 ? round(((float) ($activeStats->qris_omset ?? 0) / $activeRevenue) * 100, 1) : 0,
            ],
            'Tempo' => [
                'omset' => (float) ($activeStats->tempo_omset ?? 0),
                'count' => (int) ($activeStats->tempo_count ?? 0),
                'share_percent' => $activeRevenue > 0 ? round(((float) ($activeStats->tempo_omset ?? 0) / $activeRevenue) * 100, 1) : 0,
            ],
        ];

        // Multi-Branch Scorecard (when All Branches are selected)
        $branchScorecard = [];
        if (!$selectedBranch || $selectedBranch === '') {
            $branchAggregates = $this->makeTransactionQuery(null, $selectedChannel)
                ->selectRaw("
                    COALESCE(branch, 'Pusat Cianjur') as branch_name,
                    SUM(total_price) as omset,
                    SUM(total_price - total_cost) as gross_profit,
                    COUNT(id) as count,
                    AVG(total_price) as aov
                ")
                ->whereBetween('created_at', [$start, $end])
                ->where('payment_status', '!=', 'cancelled')
                ->groupBy('branch_name')
                ->orderByDesc('omset')
                ->get();

            $branchExpenses = $isChannelFiltered
                ? collect()
                : \App\Models\Expense::selectRaw("
                        COALESCE(branch, 'Pusat Cianjur') as branch_name,
                        SUM(amount) as total_expense
                    ")
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('branch_name')
                    ->pluck('total_expense', 'branch_name');

            foreach ($branchAggregates as $b) {
                $exp = (float) ($branchExpenses[$b->branch_name] ?? 0);
                $gross = (float) $b->gross_profit;
                $net = $gross - $exp;
                $share = $activeRevenue > 0 ? round(((float) $b->omset / $activeRevenue) * 100, 1) : 0;
                $margin = ((float) $b->omset) > 0 ? round(($net / (float) $b->omset) * 100, 1) : 0;
                $branchScorecard[] = [
                    'name' => $b->branch_name,
                    'omset' => (float) $b->omset,
                    'net_profit' => $net,
                    'margin_percent' => $margin,
                    'count' => (int) $b->count,
                    'aov' => (float) $b->aov,
                    'share_percent' => $share,
                ];
            }
        }

        // Inventory Capital & Health (cached 15 mins to eliminate overhead)
        $inventoryMetrics = Cache::remember('owner_inventory_metrics', 900, function () {
            $stats = Product::selectRaw("
                COUNT(id) as total_skus,
                SUM(CASE WHEN stock <= 10 THEN 1 ELSE 0 END) as low_stock_skus,
                SUM(CASE WHEN stock <= 0 THEN 1 ELSE 0 END) as out_of_stock_skus,
                SUM(stock * cost_price) as total_cost_value,
                SUM(stock * selling_price) as total_retail_value
            ")->first();

            return [
                'total_skus' => (int) ($stats->total_skus ?? 0),
                'low_stock_skus' => (int) ($stats->low_stock_skus ?? 0),
                'out_of_stock_skus' => (int) ($stats->out_of_stock_skus ?? 0),
                'total_cost_value' => (float) ($stats->total_cost_value ?? 0),
                'total_retail_value' => (float) ($stats->total_retail_value ?? 0),
            ];
        });

        // 4. Fetch/Build breakdown table data
        $breakdownData = [];
        if ($filterType === 'harian') {
            $breakdownData = $baseQuery()->with('cashier')
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->get();
        } elseif ($filterType === 'mingguan') {
            $days = [];
            for ($d = $startOfWeek->copy(); $d->lte($endOfWeek); $d->addDay()) {
                $days[$d->format('Y-m-d')] = [
                    'label' => $d->translatedFormat('l'),
                    'sub_label' => $d->translatedFormat('d M Y'),
                    'omset' => 0,
                    'profit' => 0,
                    'count' => 0,
                ];
            }

            // DB-level aggregation (GROUP BY date) — jauh lebih hemat RAM
            $weeklyStats = $baseQuery()
                ->selectRaw('DATE(created_at) as date, SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->groupBy('date')
                ->get()
                ->keyBy('date');
            foreach ($days as $dateStr => &$dayData) {
                if ($weeklyStats->has($dateStr)) {
                    $stat = $weeklyStats->get($dateStr);
                    $dayData['omset']  = (int) $stat->omset;
                    $dayData['profit'] = (int) $stat->profit;
                    $dayData['count']  = (int) $stat->count;
                }
            }
            unset($dayData);
            $breakdownData = array_values($days);
        } else { // bulanan
            $daysInMonth = $startOfMonth->daysInMonth;
            $weeksBreakdown = [
                1 => ['start' => 1, 'end' => 7, 'label' => 'Minggu 1', 'sub_label' => '01 - 07 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0],
                2 => ['start' => 8, 'end' => 14, 'label' => 'Minggu 2', 'sub_label' => '08 - 14 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0],
                3 => ['start' => 15, 'end' => 21, 'label' => 'Minggu 3', 'sub_label' => '15 - 21 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0],
                4 => ['start' => 22, 'end' => 28, 'label' => 'Minggu 4', 'sub_label' => '22 - 28 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0],
            ];
            if ($daysInMonth > 28) {
                $weeksBreakdown[5] = ['start' => 29, 'end' => $daysInMonth, 'label' => 'Minggu 5', 'sub_label' => '29 - ' . $daysInMonth . ' ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0];
            }

            // DB-level aggregation per hari (GROUP BY day) — hemat RAM untuk data bulanan
            $monthlyStats = $baseQuery()
                ->selectRaw('DAY(created_at) as day_num, SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->groupBy('day_num')
                ->get();
            foreach ($monthlyStats as $stat) {
                $dayNum = (int) $stat->day_num;
                foreach ($weeksBreakdown as $wNum => &$w) {
                    if ($dayNum >= $w['start'] && $dayNum <= $w['end']) {
                        $w['omset']  += (int) $stat->omset;
                        $w['profit'] += (int) $stat->profit;
                        $w['count']  += (int) $stat->count;
                        break;
                    }
                }
            }
            unset($w);
            $breakdownData = array_values($weeksBreakdown);
        }

        // 5. Fetch weekly omset breakdown for the general trend graph
        $idDayNames = ['Mon' => 'Senin', 'Tue' => 'Selasa', 'Wed' => 'Rabu', 'Thu' => 'Kamis', 'Fri' => 'Jumat', 'Sat' => 'Sabtu', 'Sun' => 'Minggu'];
        $idShortDayNames = ['Mon' => 'Sen', 'Tue' => 'Sel', 'Wed' => 'Rab', 'Thu' => 'Kam', 'Fri' => 'Jum', 'Sat' => 'Sab', 'Sun' => 'Min'];
        $days = [];
        for ($d = $startOfWeek->copy(); $d->lte($endOfWeek); $d->addDay()) {
            $enD = $d->format('D');
            $days[$d->format('Y-m-d')] = [
                'day_name' => $idShortDayNames[$enD] ?? $d->translatedFormat('D'),
                'full_day_name' => $idDayNames[$enD] ?? $d->translatedFormat('l'),
                'date' => $d->translatedFormat('d M Y'),
                'raw_date' => $d->format('Y-m-d'),
                'omset' => 0,
                'profit' => 0,
                'count' => 0,
                'aov' => 0,
                'is_today' => $d->isToday(),
            ];
        }

        $weeklyOmsetDetail = $baseQuery()
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as total_omset, SUM(total_price - total_cost) as total_profit, COUNT(id) as total_count')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->where('payment_status', '!=', 'cancelled')
            ->groupBy('date')
            ->get();

        foreach ($weeklyOmsetDetail as $stat) {
            if (isset($days[$stat->date])) {
                $days[$stat->date]['omset'] = (int) $stat->total_omset;
                $days[$stat->date]['profit'] = (int) $stat->total_profit;
                $days[$stat->date]['count'] = (int) $stat->total_count;
                $days[$stat->date]['aov'] = $stat->total_count > 0 ? round($stat->total_omset / $stat->total_count) : 0;
            }
        }

        $maxOmset = collect($days)->max('omset');
        foreach ($days as $dateStr => &$day) {
            $day['height_percent'] = $maxOmset > 0 ? round(($day['omset'] / $maxOmset) * 100) : 0;
        }
        unset($day);

        $weeklyTrend = array_values($days);

        // 6. Fetch monthly weekly-breakdown for the general trend graph
        $daysInMonth = $startOfMonth->daysInMonth;
        $weeks = [
            1 => [
                'label' => 'Minggu 1',
                'full_name' => 'Minggu Pertama',
                'date' => '01-07 ' . $startOfMonth->translatedFormat('M'),
                'raw_start' => $startOfMonth->copy()->day(1)->format('Y-m-d'),
                'raw_end' => $startOfMonth->copy()->day(7)->format('Y-m-d'),
                'omset' => 0,
                'profit' => 0,
                'count' => 0,
                'aov' => 0,
                'is_today' => false
            ],
            2 => [
                'label' => 'Minggu 2',
                'full_name' => 'Minggu Kedua',
                'date' => '08-14 ' . $startOfMonth->translatedFormat('M'),
                'raw_start' => $startOfMonth->copy()->day(8)->format('Y-m-d'),
                'raw_end' => $startOfMonth->copy()->day(14)->format('Y-m-d'),
                'omset' => 0,
                'profit' => 0,
                'count' => 0,
                'aov' => 0,
                'is_today' => false
            ],
            3 => [
                'label' => 'Minggu 3',
                'full_name' => 'Minggu Ketiga',
                'date' => '15-21 ' . $startOfMonth->translatedFormat('M'),
                'raw_start' => $startOfMonth->copy()->day(15)->format('Y-m-d'),
                'raw_end' => $startOfMonth->copy()->day(21)->format('Y-m-d'),
                'omset' => 0,
                'profit' => 0,
                'count' => 0,
                'aov' => 0,
                'is_today' => false
            ],
            4 => [
                'label' => 'Minggu 4',
                'full_name' => 'Minggu Keempat',
                'date' => '22-28 ' . $startOfMonth->translatedFormat('M'),
                'raw_start' => $startOfMonth->copy()->day(22)->format('Y-m-d'),
                'raw_end' => $startOfMonth->copy()->day(28)->format('Y-m-d'),
                'omset' => 0,
                'profit' => 0,
                'count' => 0,
                'aov' => 0,
                'is_today' => false
            ],
        ];
        if ($daysInMonth > 28) {
            $weeks[5] = [
                'label' => 'Minggu 5',
                'full_name' => 'Minggu Kelima',
                'date' => '29-' . $daysInMonth . ' ' . $startOfMonth->translatedFormat('M'),
                'raw_start' => $startOfMonth->copy()->day(29)->format('Y-m-d'),
                'raw_end' => $startOfMonth->copy()->day($daysInMonth)->format('Y-m-d'),
                'omset' => 0,
                'profit' => 0,
                'count' => 0,
                'aov' => 0,
                'is_today' => false
            ];
        }

        if ($startOfMonth->isCurrentMonth()) {
            $todayDay = \Carbon\Carbon::now()->day;
            foreach ($weeks as $wNum => &$w) {
                $wStart = ($wNum - 1) * 7 + 1;
                $wEnd = ($wNum === 5) ? $daysInMonth : $wNum * 7;
                if ($todayDay >= $wStart && $todayDay <= $wEnd) {
                    $w['is_today'] = true;
                }
            }
            unset($w);
        }

        $monthlyOmsetDetail = $baseQuery()
            ->selectRaw('DAY(created_at) as day, SUM(total_price) as total_omset, SUM(total_price - total_cost) as total_profit, COUNT(id) as total_count')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->where('payment_status', '!=', 'cancelled')
            ->groupBy('day')
            ->get();

        foreach ($monthlyOmsetDetail as $stat) {
            $day = (int)$stat->day;
            foreach ($weeks as $wNum => &$w) {
                $wStart = ($wNum - 1) * 7 + 1;
                $wEnd = ($wNum === 5) ? $daysInMonth : $wNum * 7;
                if ($day >= $wStart && $day <= $wEnd) {
                    $w['omset'] += (int)$stat->total_omset;
                    $w['profit'] += (int)$stat->total_profit;
                    $w['count'] += (int)$stat->total_count;
                    break;
                }
            }
        }
        unset($w);

        foreach ($weeks as &$w) {
            $w['aov'] = $w['count'] > 0 ? round($w['omset'] / $w['count']) : 0;
        }
        unset($w);

        $maxMonthlyOmset = collect($weeks)->max('omset');
        foreach ($weeks as &$w) {
            $w['height_percent'] = $maxMonthlyOmset > 0 ? round(($w['omset'] / $maxMonthlyOmset) * 100) : 0;
        }
        unset($w);

        $monthlyTrend = array_values($weeks);

        // 6b. Fetch yearly monthly-breakdown (12 months) for the general trend graph
        $yearlyStats = $baseQuery()
            ->selectRaw('MONTH(created_at) as month_num, SUM(total_price) as total_omset, SUM(total_price - total_cost) as total_profit, COUNT(id) as total_count')
            ->whereYear('created_at', $startOfMonth->year)
            ->where('payment_status', '!=', 'cancelled')
            ->groupBy('month_num')
            ->get()
            ->keyBy('month_num');

        $yearlyMonths = [];
        $idMonths = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu', 9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'];
        $idFullMonths = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $currentMonthNum = (\Carbon\Carbon::now()->year === $startOfMonth->year) ? \Carbon\Carbon::now()->month : 0;
        for ($m = 1; $m <= 12; $m++) {
            $mDt = \Carbon\Carbon::create($startOfMonth->year, $m, 1);
            $stat = $yearlyStats->get($m);
            $mOmset = $stat ? (int)$stat->total_omset : 0;
            $mProfit = $stat ? (int)$stat->total_profit : 0;
            $mCount = $stat ? (int)$stat->total_count : 0;
            $mShortName = $idMonths[$m] ?? $mDt->format('M');
            $mFullName = ($idFullMonths[$m] ?? $mDt->format('F')) . ' ' . $startOfMonth->year;
            $yearlyMonths[] = [
                'label' => $mShortName,
                'full_name' => $mFullName,
                'date' => $mShortName . ' ' . $startOfMonth->year,
                'raw_month' => $mDt->format('Y-m'),
                'raw_start' => $mDt->copy()->startOfMonth()->format('Y-m-d'),
                'raw_end' => $mDt->copy()->endOfMonth()->format('Y-m-d'),
                'omset' => $mOmset,
                'profit' => $mProfit,
                'count' => $mCount,
                'aov' => $mCount > 0 ? round($mOmset / $mCount) : 0,
                'is_today' => ($m === $currentMonthNum),
            ];
        }
        $yearlyTrend = $yearlyMonths;

        // 7. Best sellers — Cache 10 menit untuk efisiensi parsing regex string items_summary
        $bestSellerDay = ($filterType === 'harian') ? $dateObj : $start;
        $bsCacheKey = 'owner_bs_' . md5(($selectedBranch ?? 'all') . '_' . $selectedChannel . '_' . $bestSellerDay->format('Y-m-d') . '_' . $startOfWeek->format('Y-m-d') . '_' . $startOfMonth->format('Y-m'));

        $bestSellersData = Cache::remember($bsCacheKey, 600, function () use ($baseQuery, $bestSellerDay, $startOfWeek, $endOfWeek, $startOfMonth, $endOfMonth) {
            $todayTransactions = $baseQuery()
                ->select('items_summary', 'created_at')
                ->whereBetween('created_at', [
                    $bestSellerDay->copy()->startOfDay(),
                    $bestSellerDay->copy()->endOfDay(),
                ])
                ->get();

            $weeklyTransactions = $baseQuery()
                ->select('items_summary', 'created_at')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->get();

            $monthlyTransactions = $baseQuery()
                ->select('items_summary', 'created_at')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->get();

            return [
                'today' => $this->getBestSellers($todayTransactions, 5),
                'weekly' => $this->getBestSellers($weeklyTransactions, 5),
                'monthly' => $this->getBestSellers($monthlyTransactions, 5),
            ];
        });

        $bestSellersToday = $bestSellersData['today'];
        $bestSellersWeekly = $bestSellersData['weekly'];
        $bestSellersMonthly = $bestSellersData['monthly'];

        return view('owner.dashboard', compact(
            'lowStockCount',
            'lowStockProducts',
            'filterType',
            'selectedDate',
            'selectedWeek',
            'selectedMonth',
            'selectedBranch',
            'selectedChannel',
            'isChannelFiltered',
            'branches',
            'activeRevenue',
            'activeProfit',
            'activeGrossProfit',
            'activeTransactionsCount',
            'revenueGrowthPercent',
            'profitGrowthPercent',
            'transactionGrowthPercent',
            'weeklyTrend',
            'monthlyTrend',
            'yearlyTrend',
            'breakdownData',
            'activeExpenses',
            'expenseGrowthPercent',
            'bestSellersToday',
            'bestSellersWeekly',
            'bestSellersMonthly',
            'comparisonLabel',
            'titleLabel',
            'bestSellerDay',
            'startOfWeek',
            'endOfWeek',
            'startOfMonth',
            'endOfMonth',
            'realizedCash',
            'cashGrowthPercent',
            'outstandingReceivables',
            'activePeriodReceivables',
            'grossMarginPercent',
            'netMarginPercent',
            'oerPercent',
            'activeAov',
            'aovGrowthPercent',
            'retailStats',
            'wholesaleStats',
            'paymentBreakdown',
            'cashRealizationPercent',
            'branchScorecard',
            'inventoryMetrics'
        ));
    }

    /**
     * Normalize requested sales channel filter ('all', 'retail', 'wholesale').
     */
    private function normalizeChannel(?string $channel): string
    {
        return in_array($channel, ['retail', 'wholesale'], true) ? $channel : 'all';
    }

    /**
     * Human-readable label for selected sales channel.
     */
    private function channelLabel(string $channel): string
    {
        return match ($channel) {
            'retail' => 'Retail POS (Kasir)',
            'wholesale' => 'Wholesale (Partai)',
            default => 'Semua Saluran',
        };
    }

    /**
     * Apply sales channel filter to a Transaction query.
     */
    private function applyChannelScope($query, string $channel)
    {
        if ($channel === 'wholesale') {
            return $query->where('transaction_type', 'wholesale');
        }
        if ($channel === 'retail') {
            return $query->where(function ($q) {
                $q->where('transaction_type', '!=', 'wholesale')
                  ->orWhereNull('transaction_type');
            });
        }
        return $query;
    }

    /**
     * Build a Transaction query scoped by optional branch and sales channel.
     */
    private function makeTransactionQuery(?string $branch, string $channel = 'all')
    {
        $query = \App\Models\Transaction::query();
        if ($branch && $branch !== '') {
            $query->where('branch', $branch);
        }
        return $this->applyChannelScope($query, $channel);
    }

    /**
     * Resolve date ranges and labels based on request parameters.
     */
    private function resolveDateRanges(Request $request)
    {
        $filterType = $request->query('filter_type', 'harian');
        if (!in_array($filterType, ['harian', 'mingguan', 'bulanan'])) {
            $filterType = 'harian';
        }

        $selectedDate = $request->query('date', \Carbon\Carbon::today()->format('Y-m-d'));
        $selectedWeek = $request->query('week', \Carbon\Carbon::now()->format('Y-\WW'));
        $selectedMonth = $request->query('month', \Carbon\Carbon::now()->format('Y-m'));

        try {
            $dateObj = \Carbon\Carbon::parse($selectedDate)->startOfDay();
        } catch (\Throwable $e) {
            $selectedDate = \Carbon\Carbon::today()->format('Y-m-d');
            $dateObj = \Carbon\Carbon::today()->startOfDay();
        }

        if (preg_match('/^(\d{4})-W(\d{2})$/', $selectedWeek, $matches)) {
            $weekObj = \Carbon\Carbon::now()->setISODate((int)$matches[1], (int)$matches[2])->startOfDay();
        } else {
            $selectedWeek = \Carbon\Carbon::now()->format('Y-\WW');
            $weekObj = \Carbon\Carbon::now()->startOfDay();
        }

        if (preg_match('/^(\d{4})-(\d{2})$/', $selectedMonth, $matches)) {
            $monthObj = \Carbon\Carbon::createFromDate((int)$matches[1], (int)$matches[2], 1)->startOfDay();
        } else {
            $selectedMonth = \Carbon\Carbon::now()->format('Y-m');
            $monthObj = \Carbon\Carbon::now()->startOfDay();
        }

        if ($filterType === 'harian') {
            $start = $dateObj->copy()->startOfDay();
            $end = $dateObj->copy()->endOfDay();
            $comparisonStart = $start->copy()->subDay();
            $comparisonEnd = $end->copy()->subDay();
            
            $startOfWeek = $dateObj->copy()->startOfWeek();
            $endOfWeek = $dateObj->copy()->endOfWeek();
            $startOfMonth = $dateObj->copy()->startOfMonth();
            $endOfMonth = $dateObj->copy()->endOfMonth();

            $comparisonLabel = 'vs hari sebelumnya';
            $titleLabel = 'Harian (' . $dateObj->translatedFormat('d F Y') . ')';
            $filePrefix = 'Laporan_Harian_POS';
        } elseif ($filterType === 'mingguan') {
            $start = $weekObj->copy()->startOfWeek();
            $end = $weekObj->copy()->endOfWeek();
            $comparisonStart = $start->copy()->subWeek();
            $comparisonEnd = $end->copy()->subWeek();

            $startOfWeek = $start->copy();
            $endOfWeek = $end->copy();
            $startOfMonth = $start->copy()->startOfMonth();
            $endOfMonth = $start->copy()->endOfMonth();

            $comparisonLabel = 'vs pekan sebelumnya';
            $titleLabel = 'Mingguan (' . $start->translatedFormat('d M Y') . ' s/d ' . $end->translatedFormat('d M Y') . ')';
            $filePrefix = 'Laporan_Mingguan_POS';
        } else { // bulanan
            $start = $monthObj->copy()->startOfMonth();
            $end = $monthObj->copy()->endOfMonth();
            $comparisonStart = $start->copy()->subMonth();
            $comparisonEnd = $end->copy()->subMonth();

            if ($monthObj->isCurrentMonth()) {
                $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
                $endOfWeek = \Carbon\Carbon::now()->endOfWeek();
            } else {
                $startOfWeek = $start->copy()->startOfWeek();
                $endOfWeek = $start->copy()->endOfWeek();
            }
            $startOfMonth = $start->copy();
            $endOfMonth = $end->copy();

            $comparisonLabel = 'vs bulan sebelumnya';
            $titleLabel = 'Bulanan (' . $start->translatedFormat('F Y') . ')';
            $filePrefix = 'Laporan_Bulanan_POS';
        }

        return [
            'filterType' => $filterType,
            'selectedDate' => $selectedDate,
            'selectedWeek' => $selectedWeek,
            'selectedMonth' => $selectedMonth,
            'start' => $start,
            'end' => $end,
            'comparisonStart' => $comparisonStart,
            'comparisonEnd' => $comparisonEnd,
            'startOfWeek' => $startOfWeek,
            'endOfWeek' => $endOfWeek,
            'startOfMonth' => $startOfMonth,
            'endOfMonth' => $endOfMonth,
            'comparisonLabel' => $comparisonLabel,
            'titleLabel' => $titleLabel,
            'filePrefix' => $filePrefix,
            'dateObj' => $dateObj
        ];
    }

    /**
     * Parse items_summary from transactions and return top selling products.
     * Combines Normalization (Gram -> Kg) and Sorting by Purchase Frequency.
     */
    private function getBestSellers($transactions, $limit = 5)
    {
        $bestSellers = [];
        foreach ($transactions as $trx) {
            if (empty($trx->items_summary)) {
                continue;
            }
            $parts = explode(', ', $trx->items_summary);
            $seenInThisTransaction = [];
            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (empty($trimmed)) {
                    continue;
                }

                // Match: "Nama Produk (2 pcs x 50000)" or "Nama Produk (2 pcs)"
                if (preg_match('/^(.*?)\s*\(\s*(\d+(?:[.,]\d+)?)\s*([a-zA-Z\s]+?)(?:\s*x\s*[\d.,]+)?\s*\)$/i', $trimmed, $matches)) {
                    $name = trim($matches[1]);
                    $qty = floatval(str_replace(',', '.', $matches[2]));
                    $unit = strtolower(trim($matches[3]));
                } elseif (preg_match('/^(.*?)\s*\(.*?\)$/', $trimmed, $matches)) {
                    $name = trim($matches[1]);
                    $qty = 1.0;
                    $unit = 'pcs';
                } else {
                    $name = $trimmed;
                    $qty = 1.0;
                    $unit = 'pcs';
                }

                // Normalize weight unit: gram -> kg
                if ($unit === 'gram' || $unit === 'g') {
                    $qty = $qty / 1000;
                    $unit = 'kg';
                }

                $nameKey = mb_strtolower($name);

                if (!isset($bestSellers[$nameKey])) {
                    $bestSellers[$nameKey] = [
                        'name' => $name,
                        'qty' => 0.0,
                        'unit' => $unit,
                        'count' => 0
                    ];
                }
                
                $bestSellers[$nameKey]['qty'] += $qty;
                if ($unit === 'kg') {
                    $bestSellers[$nameKey]['unit'] = 'kg';
                }
                
                if (!isset($seenInThisTransaction[$nameKey])) {
                    $bestSellers[$nameKey]['count'] += 1;
                    $seenInThisTransaction[$nameKey] = true;
                }
            }
        }

        // Sort descending by transaction frequency (count), secondary by total quantity (qty)
        uasort($bestSellers, function ($a, $b) {
            if ($b['count'] === $a['count']) {
                return $b['qty'] <=> $a['qty'];
            }
            return $b['count'] <=> $a['count'];
        });

        $sliced = array_values(array_slice($bestSellers, 0, $limit));
        
        // Enrich with product image, category, prices, and revenue/profit calculations
        $names = array_column($sliced, 'name');
        $products = \App\Models\Product::whereIn('name', $names)->get()->keyBy(fn($p) => mb_strtolower($p->name));
        
        foreach ($sliced as &$item) {
            $product = $products->get(mb_strtolower($item['name']));
            $item['image_path'] = $product ? $product->image_path : null;
            $item['category'] = $product ? $product->category : 'Umum';
            $item['sku'] = $product ? $product->sku : '-';
            $item['stock'] = $product ? (float) $product->stock : 0;
            $item['price_unit'] = $product ? $product->price_unit : $item['unit'];
            $item['selling_price'] = $product ? (float) $product->selling_price : 0;
            $item['cost_price'] = $product ? (float) $product->cost_price : 0;

            // Normalize gram to kg for revenue & profit
            $qtyMultiplier = 1;
            if ($product && in_array(strtolower($product->price_unit), ['gram', 'g']) && $item['unit'] === 'kg') {
                $qtyMultiplier = 1000;
            } elseif ($product && in_array(strtolower($item['unit']), ['gram', 'g']) && strtolower($product->price_unit) === 'kg') {
                $qtyMultiplier = 0.001;
            }
            $calcQty = $item['qty'] * $qtyMultiplier;
            $item['qty'] = round((float) $item['qty'], 2);
            $item['estimated_revenue'] = $item['selling_price'] > 0 ? round($calcQty * $item['selling_price']) : 0;
            $item['estimated_profit'] = ($item['selling_price'] > 0 && $item['cost_price'] > 0) ? max(0, round($calcQty * ($item['selling_price'] - $item['cost_price']))) : 0;
        }
        unset($item);

        return $sliced;
    }

    /**
     * Format a number as Indonesian Rupiah string for Excel export.
     */
    private function rupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Shared CSS styles for all Excel exports.
     */
    private function excelStyles(bool $isPdf = false): string
    {
        $extraStyles = $isPdf ? '@page { margin: 1.2cm 1.0cm; } table { width: 100%; margin: 0 auto; } th { width: auto !important; font-size: 8.5pt !important; padding: 6px 4px !important; } td { font-size: 8pt !important; padding: 5px 4px !important; } ' : '';
        return implode('', [
            $extraStyles,
            'body{font-family:"Segoe UI",Arial,sans-serif;}',
            'table{border-collapse:collapse;}',
            'th{background-color:#059669;color:#ffffff;font-weight:bold;border:1px solid #047857;padding:10px 14px;font-size:11pt;white-space:nowrap;}',
            'td{border:1px solid #d1fae5;padding:8px 12px;font-size:10pt;vertical-align:middle;}',
            '.title{font-size:16pt;font-weight:bold;color:#064e3b;text-align:center;border:none !important;}',
            '.subtitle{font-size:12pt;font-weight:bold;color:#10b981;text-align:center;border:none !important;}',
            '.meta-label{font-weight:bold;color:#374151;background-color:#f0fdf4;width:180px;border:1px solid #d1fae5;}',
            '.meta-value{color:#1f2937;border:1px solid #d1fae5;}',
            '.text{mso-number-format:"\\@";text-align:left;}',
            '.center{text-align:center;}',
            '.right{text-align:right;}',
            '.bold{font-weight:bold;}',
            '.currency{text-align:right;font-weight:bold;color:#065f46;}',
            '.percent{text-align:right;color:#6b7280;}',
            '.total-row{background-color:#d1fae5;font-weight:bold;color:#064e3b;}',
            '.avg-row{background-color:#ecfdf5;font-weight:bold;color:#065f46;}',
            '.spacer{border:none !important;height:10px;}',
            '.stripe{background-color:#f9fafb;}',
        ]);
    }

    /**
     * Export Best Seller products report as Excel-compatible file.
     */
    public function exportBestSellers(Request $request)
    {
        $activeBranch = $request->query('branch', '');
        $selectedChannel = $this->normalizeChannel($request->query('channel'));
        $channelLabel = $this->channelLabel($selectedChannel);
        $branchSuffix = $activeBranch ? '_' . str_replace(' ', '_', $activeBranch) : '';
        $channelSuffix = $selectedChannel !== 'all' ? '_' . $selectedChannel : '';

        // Helper: base query with optional branch & channel scope
        $baseQuery = fn() => $this->makeTransactionQuery($activeBranch, $selectedChannel);

        $dateRanges = $this->resolveDateRanges($request);
        $filterType = $dateRanges['filterType'];
        $start = $dateRanges['start'];
        $end = $dateRanges['end'];
        $titleLabel = $dateRanges['titleLabel'];
        $filePrefix = 'Laporan_Best_Seller_' . ucfirst($filterType);

        $transactions = $baseQuery()->whereBetween('created_at', [$start, $end])->get();
        $periodLabel = $titleLabel;

        // Increase limit for export so they see more products
        $bestSellers = $this->getBestSellers($transactions, 100);

        $now = \Carbon\Carbon::now();
        $printDate = $now->translatedFormat('d F Y - H:i');
        $printedBy = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';
        $isPdf = $request->query('format') === 'pdf';

        if ($isPdf) {
            @ini_set('memory_limit', '512M');
            @set_time_limit(180);
        }

        // Pre-calculate stats for cards
        $totalItems = count($bestSellers);
        $totalQty = 0;
        foreach ($bestSellers as $item) {
            $totalQty += $item['qty'];
        }

        $renderTemplate = function () use ($bestSellers, $activeBranch, $channelLabel, $periodLabel, $printDate, $printedBy, $isPdf, $totalItems, $totalQty) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<style>' . $this->excelStyles($isPdf) . '</style></head><body>';

            if ($isPdf) {
                echo $this->pdfHeader('LAPORAN PRODUK TERLARIS (BEST SELLER)');
                
                $cards = [
                    [
                        'label' => 'Total Produk Terlaris',
                        'value' => $totalItems . ' Produk',
                        'color' => '#d97706'
                    ],
                    [
                        'label' => 'Total Volume Terjual',
                        'value' => number_format($totalQty, 0, ',', '.') . ' Unit',
                        'color' => '#059669'
                    ]
                ];
                echo $this->pdfCards($cards);
            }

            // Metadata Table
            echo '<table style="margin-bottom:16px; width:100%;">';
            if (!$isPdf) {
                echo '<tr><td colspan="6" class="title" style="height:45px;">LAPORAN PRODUK BEST SELLER (TERLARIS)</td></tr>';
                echo '<tr><td colspan="6" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
                echo '<tr><td colspan="6" class="spacer"></td></tr>';
            }
            echo '<tr><td class="meta-label">Periode</td><td colspan="5" class="meta-value">' . htmlspecialchars($periodLabel) . '</td></tr>';
            echo '<tr><td class="meta-label">Filter Cabang</td><td colspan="5" class="meta-value">' . htmlspecialchars($activeBranch ? $activeBranch : 'Semua Cabang') . '</td></tr>';
            echo '<tr><td class="meta-label">Saluran Penjualan</td><td colspan="5" class="meta-value">' . htmlspecialchars($channelLabel) . '</td></tr>';
            echo '<tr><td class="meta-label">Tanggal Cetak</td><td colspan="5" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
            echo '<tr><td class="meta-label">Dicetak Oleh</td><td colspan="5" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
            echo '<tr><td colspan="6" class="spacer"></td></tr>';
            echo '</table>';

            // Data Table
            echo '<table>';
            echo '<thead><tr>';
            echo '<th style="width:70px;">Peringkat</th>';
            echo '<th style="width:250px;">Nama Produk</th>';
            echo '<th style="width:150px;">Kategori</th>';
            echo '<th style="width:150px; text-align:center;">Jumlah Transaksi</th>';
            echo '<th style="width:150px; text-align:right;">Total Kuantitas Terjual</th>';
            echo '<th style="width:100px; text-align:center;">Satuan</th>';
            echo '</tr></thead><tbody>';

            $idx = 1;
            foreach ($bestSellers as $item) {
                $rowClass = $idx % 2 === 0 ? ' class="stripe"' : '';
                echo '<tr' . $rowClass . '>';
                echo '<td class="center font-bold">#' . $idx++ . '</td>';
                echo '<td class="bold">' . htmlspecialchars($item['name']) . '</td>';
                echo '<td>' . htmlspecialchars($item['category'] ?? 'Umum') . '</td>';
                echo '<td class="center">' . $item['count'] . 'x Transaksi</td>';
                echo '<td class="right bold">' . number_format($item['qty'], 2, ',', '.') . '</td>';
                echo '<td class="center font-bold">' . htmlspecialchars($item['unit']) . '</td>';
                echo '</tr>';
            }

            if (empty($bestSellers)) {
                echo '<tr><td colspan="6" class="center text" style="color:#6b7280;height:60px;">Belum ada data penjualan produk untuk periode ini.</td></tr>';
            }

            echo '</tbody></table>';

            if ($isPdf) {
                echo $this->pdfSignature($printedBy);
                echo $this->pdfFooter();
            }

            echo '</body></html>';
        };

        if ($isPdf) {
            @ini_set('memory_limit', '512M');
            @set_time_limit(180);
            try {
                ob_start();
                $renderTemplate();
                $html = ob_get_clean();

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'portrait');
                return $pdf->download($filePrefix . $branchSuffix . $channelSuffix . '_' . $now->format('Y-m-d') . '.pdf');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Best Seller PDF export failed: ' . $e->getMessage());
                return response('Gagal mengekspor PDF Produk Terlaris: ' . $e->getMessage(), 500);
            }
        }

        $filename = $filePrefix . $branchSuffix . $channelSuffix . '_' . $now->format('Y-m-d') . '.xls';
        return response()->streamDownload($renderTemplate, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Get detailed analytics and transaction breakdown for a specific best seller product.
     */
    public function getBestSellerDetail(Request $request)
    {
        $productName = trim($request->query('name', ''));
        if (empty($productName)) {
            return response()->json(['success' => false, 'message' => 'Nama produk wajib diisi.'], 400);
        }

        $activeBranch = $request->query('branch', '');
        $selectedChannel = $this->normalizeChannel($request->query('channel'));
        $dateRanges = $this->resolveDateRanges($request);
        $filterType = $request->query('period_type', $dateRanges['filterType']);

        if ($filterType === 'harian') {
            $selectedDate = $request->query('date', \Carbon\Carbon::today()->format('Y-m-d'));
            try {
                $start = \Carbon\Carbon::parse($selectedDate)->startOfDay();
            } catch (\Throwable $e) {
                $start = \Carbon\Carbon::today()->startOfDay();
            }
            $end = $start->copy()->endOfDay();
            $periodLabel = 'Harian: ' . $start->translatedFormat('d M Y');
        } elseif ($filterType === 'mingguan') {
            $start = $dateRanges['startOfWeek'];
            $end = $dateRanges['endOfWeek'];
            $periodLabel = 'Mingguan: ' . $start->translatedFormat('d M') . ' - ' . $end->translatedFormat('d M Y');
        } else {
            $start = $dateRanges['startOfMonth'];
            $end = $dateRanges['endOfMonth'];
            $periodLabel = 'Bulanan: ' . $start->translatedFormat('F Y');
        }

        $baseQuery = fn() => $this->makeTransactionQuery($activeBranch, $selectedChannel);

        // 1. Fetch matching transactions in range with created_at index
        $transactions = $baseQuery()
            ->with('cashier')
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', '!=', 'cancelled')
            ->where('items_summary', 'LIKE', '%' . $productName . '%')
            ->orderByDesc('created_at')
            ->get();

        // 2. Fetch product master
        $product = \App\Models\Product::where('name', $productName)->first();

        $totalQty = 0.0;
        $unit = 'pcs';
        $transactionCount = 0;
        $totalRevenue = 0.0;
        $branchBreakdown = [];
        $transactionList = [];

        foreach ($transactions as $trx) {
            if (empty($trx->items_summary)) {
                continue;
            }

            $parts = explode(', ', $trx->items_summary);
            $foundInThisTrx = false;
            $qtyInTrx = 0.0;
            $itemUnit = 'pcs';
            $itemPrice = 0.0;

            foreach ($parts as $part) {
                $trimmed = trim($part);
                if (stripos($trimmed, $productName) === false) {
                    continue;
                }

                if (preg_match('/^(.*?)\s*\(\s*(\d+(?:[.,]\d+)?)\s*([a-zA-Z\s]+?)(?:\s*x\s*([\d.,]+))?\s*\)$/i', $trimmed, $m)) {
                    $matchedName = trim($m[1]);
                    if (strcasecmp($matchedName, $productName) === 0 || stripos($matchedName, $productName) !== false) {
                        $parsedQty = floatval(str_replace(',', '.', $m[2]));
                        $parsedUnit = strtolower(trim($m[3]));
                        $parsedPrice = isset($m[4]) ? floatval(str_replace(['.', ','], ['', '.'], $m[4])) : ($product ? (float) $product->selling_price : 0);

                        if ($parsedUnit === 'gram' || $parsedUnit === 'g') {
                            $parsedQty = $parsedQty / 1000;
                            $parsedUnit = 'kg';
                        }

                        $qtyInTrx += $parsedQty;
                        $itemUnit = $parsedUnit;
                        $itemPrice = $parsedPrice;
                        $foundInThisTrx = true;
                    }
                } elseif (stripos($trimmed, $productName) !== false) {
                    $qtyInTrx += 1.0;
                    $itemPrice = $product ? (float) $product->selling_price : 0;
                    $foundInThisTrx = true;
                }
            }

            if ($foundInThisTrx && $qtyInTrx > 0) {
                $totalQty += $qtyInTrx;
                $unit = $itemUnit;
                $transactionCount++;

                $lineTotal = $itemPrice > 0 ? ($qtyInTrx * $itemPrice) : 0;
                $totalRevenue += $lineTotal;

                $branchName = $trx->branch ?: 'Pusat Cianjur';
                if (!isset($branchBreakdown[$branchName])) {
                    $branchBreakdown[$branchName] = [
                        'branch' => $branchName,
                        'qty' => 0.0,
                        'unit' => $unit,
                        'trx_count' => 0,
                        'revenue' => 0.0,
                    ];
                }
                $branchBreakdown[$branchName]['qty'] += $qtyInTrx;
                $branchBreakdown[$branchName]['trx_count'] += 1;
                $branchBreakdown[$branchName]['revenue'] += $lineTotal;

                if (count($transactionList) < 25) {
                    $transactionList[] = [
                        'id' => $trx->id,
                        'transaction_code' => $trx->transaction_code,
                        'created_at' => $trx->created_at->translatedFormat('d M Y H:i'),
                        'branch' => $branchName,
                        'cashier' => $trx->cashier ? $trx->cashier->name : '-',
                        'qty' => round($qtyInTrx, 2),
                        'unit' => $itemUnit,
                        'line_total' => $lineTotal,
                        'total_price' => (float) $trx->total_price,
                        'payment_method' => $trx->payment_method,
                        'payment_status' => $trx->payment_status,
                    ];
                }
            }
        }

        $totalQty = round($totalQty, 2);
        foreach ($branchBreakdown as &$bb) {
            $bb['qty'] = round($bb['qty'], 2);
        }
        unset($bb);

        if ($totalRevenue == 0 && $product && $product->selling_price > 0) {
            $totalRevenue = $totalQty * (float) $product->selling_price;
            foreach ($branchBreakdown as &$bb) {
                $bb['revenue'] = $bb['qty'] * (float) $product->selling_price;
            }
            unset($bb);
            foreach ($transactionList as &$tl) {
                if ($tl['line_total'] == 0) {
                    $tl['line_total'] = $tl['qty'] * (float) $product->selling_price;
                }
            }
            unset($tl);
        }

        $estimatedProfit = 0.0;
        if ($product && $product->cost_price > 0) {
            $costQty = $totalQty;
            if (in_array(strtolower($product->price_unit), ['gram', 'g']) && $unit === 'kg') {
                $costQty = $totalQty * 1000;
            } elseif (in_array(strtolower($unit), ['gram', 'g']) && strtolower($product->price_unit) === 'kg') {
                $costQty = $totalQty / 1000;
            }
            $totalCost = $costQty * (float) $product->cost_price;
            $estimatedProfit = max(0, $totalRevenue - $totalCost);
        }

        return response()->json([
            'success' => true,
            'period_label' => $periodLabel,
            'product' => [
                'id' => $product ? $product->id : null,
                'name' => $product ? $product->name : $productName,
                'sku' => $product ? $product->sku : '-',
                'category' => $product ? $product->category : 'Umum',
                'stock' => $product ? (float) $product->stock : 0,
                'price_unit' => $product ? $product->price_unit : $unit,
                'selling_price' => $product ? (float) $product->selling_price : 0,
                'cost_price' => $product ? (float) $product->cost_price : 0,
                'image_path' => $product && $product->image_path ? asset('storage/' . $product->image_path) : null,
            ],
            'metrics' => [
                'total_qty' => $totalQty,
                'unit' => $unit,
                'trx_count' => $transactionCount,
                'total_revenue' => $totalRevenue,
                'estimated_profit' => $estimatedProfit,
                'profit_margin' => $totalRevenue > 0 ? round(($estimatedProfit / $totalRevenue) * 100, 1) : 0,
            ],
            'branches' => array_values($branchBreakdown),
            'transactions' => $transactionList,
        ]);
    }

    /**
     * Get full list of best sellers (up to 100 items) for on-demand inspection modal.
     */
    public function getBestSellersAll(Request $request)
    {
        $activeBranch = $request->query('branch', '');
        $selectedChannel = $this->normalizeChannel($request->query('channel'));
        $dateRanges = $this->resolveDateRanges($request);
        $filterType = $request->query('period_type', $dateRanges['filterType']);
        $limit = max(10, min(100, (int) $request->query('limit', 50)));

        if ($filterType === 'harian') {
            $selectedDate = $request->query('date', \Carbon\Carbon::today()->format('Y-m-d'));
            try {
                $start = \Carbon\Carbon::parse($selectedDate)->startOfDay();
            } catch (\Throwable $e) {
                $start = \Carbon\Carbon::today()->startOfDay();
            }
            $end = $start->copy()->endOfDay();
            $periodLabel = 'Harian: ' . $start->translatedFormat('d M Y');
        } elseif ($filterType === 'mingguan') {
            $start = $dateRanges['startOfWeek'];
            $end = $dateRanges['endOfWeek'];
            $periodLabel = 'Mingguan: ' . $start->translatedFormat('d M') . ' - ' . $end->translatedFormat('d M Y');
        } else {
            $start = $dateRanges['startOfMonth'];
            $end = $dateRanges['endOfMonth'];
            $periodLabel = 'Bulanan: ' . $start->translatedFormat('F Y');
        }

        $baseQuery = fn() => $this->makeTransactionQuery($activeBranch, $selectedChannel);

        $transactions = $baseQuery()->select('items_summary', 'created_at')
            ->whereBetween('created_at', [$start, $end])
            ->where('payment_status', '!=', 'cancelled')
            ->get();

        $bestSellers = $this->getBestSellers($transactions, $limit);

        return response()->json([
            'success' => true,
            'period_label' => $periodLabel,
            'period_type' => $filterType,
            'channel' => $selectedChannel,
            'channel_label' => $this->channelLabel($selectedChannel),
            'limit' => $limit,
            'total_items' => count($bestSellers),
            'items' => $bestSellers,
        ]);
    }

    /**
     * Export dynamic financial report as Excel-compatible file.
     */
    public function exportOwnerReport(Request $request)
    {
        $activeBranch = $request->query('branch', '');
        $selectedChannel = $this->normalizeChannel($request->query('channel'));
        $channelLabel = $this->channelLabel($selectedChannel);
        $branchSuffix = $activeBranch ? '_' . str_replace(' ', '_', $activeBranch) : '';
        $channelSuffix = $selectedChannel !== 'all' ? '_' . $selectedChannel : '';

        $now = \Carbon\Carbon::now();
        $printDate = $now->translatedFormat('d F Y - H:i');
        $printedBy = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';
        $isPdf = $request->query('format') === 'pdf';

        if ($isPdf) {
            @ini_set('memory_limit', '512M');
            @set_time_limit(180);
        }

        $dateRanges = $this->resolveDateRanges($request);
        $filterType = $dateRanges['filterType'];
        $start = $dateRanges['start'];
        $end = $dateRanges['end'];
        $startOfWeek = $dateRanges['startOfWeek'];
        $endOfWeek = $dateRanges['endOfWeek'];
        $startOfMonth = $dateRanges['startOfMonth'];
        $endOfMonth = $dateRanges['endOfMonth'];
        $titleLabel = $dateRanges['titleLabel'];
        $filePrefix = $dateRanges['filePrefix'];

        $baseQuery = fn() => $this->makeTransactionQuery($activeBranch, $selectedChannel);

        if ($filterType === 'harian') {
            $label = 'Harian';
            $filename = $filePrefix . $branchSuffix . $channelSuffix . '_' . $now->format('Y-m-d') . '.xls';
            $todayTransactions = $baseQuery()->with('cashier')
                ->whereBetween('created_at', [$start, $end])
                ->latest()
                ->get();

            // Pre-calculate stats
            $totalTransactions = count($todayTransactions);
            $totalOmset = 0;
            $totalProfit = 0;
            foreach ($todayTransactions as $trx) {
                $totalOmset += (int)$trx->total_price;
                $totalProfit += (int)($trx->total_price - $trx->total_cost);
            }
            $avgOmset = $totalTransactions > 0 ? (int)round($totalOmset / $totalTransactions) : 0;

            $renderTemplate = function () use ($todayTransactions, $activeBranch, $channelLabel, $printDate, $printedBy, $now, $start, $label, $isPdf, $totalTransactions, $totalOmset, $totalProfit, $avgOmset) {
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>' . $this->excelStyles($isPdf) . '</style></head><body>';

                if ($isPdf) {
                    echo $this->pdfHeader('LAPORAN PENJUALAN HARIAN POS');
                    
                    $cards = [
                        ['label' => 'Total Omset', 'value' => $this->rupiah($totalOmset), 'color' => '#059669'],
                        ['label' => 'Profit Bersih', 'value' => $this->rupiah($totalProfit), 'color' => '#2563eb'],
                        ['label' => 'Total Transaksi', 'value' => $totalTransactions . ' Trx', 'color' => '#d97706'],
                        ['label' => 'Rata-rata / Trx', 'value' => $this->rupiah($avgOmset), 'color' => '#475569']
                    ];
                    echo $this->pdfCards($cards);
                }

                // Metadata
                echo '<table style="margin-bottom:16px; width:100%;">';
                if (!$isPdf) {
                    echo '<tr><td colspan="9" class="title" style="height:45px;">LAPORAN PENJUALAN HARIAN POS</td></tr>';
                    echo '<tr><td colspan="9" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
                    echo '<tr><td colspan="9" class="spacer"></td></tr>';
                }
                echo '<tr><td class="meta-label">Filter Waktu</td><td colspan="8" class="meta-value">' . $label . ' (' . $start->translatedFormat('d F Y') . ')</td></tr>';
                echo '<tr><td class="meta-label">Filter Cabang</td><td colspan="8" class="meta-value">' . htmlspecialchars($activeBranch ? $activeBranch : 'Semua Cabang') . '</td></tr>';
                echo '<tr><td class="meta-label">Saluran Penjualan</td><td colspan="8" class="meta-value">' . htmlspecialchars($channelLabel) . '</td></tr>';
                echo '<tr><td class="meta-label">Tanggal Cetak</td><td colspan="8" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
                echo '<tr><td class="meta-label">Dicetak Oleh</td><td colspan="8" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
                echo '<tr><td colspan="9" class="spacer"></td></tr>';
                echo '</table>';

                // Table
                echo '<table>';
                echo '<thead><tr>';
                echo '<th style="width:45px;">No</th>';
                echo '<th style="width:90px;">Waktu</th>';
                echo '<th style="width:150px;">Kode Transaksi</th>';
                echo '<th style="width:140px;">Kasir</th>';
                echo '<th style="width:130px;">Cabang</th>';
                echo '<th style="width:340px;">Ringkasan Item</th>';
                echo '<th style="width:120px;">Metode Bayar</th>';
                echo '<th style="width:200px;">Total Tagihan (Omset)</th>';
                echo '<th style="width:200px;">Profit Bersih</th>';
                echo '</tr></thead><tbody>';

                $totalOmsetCalculated  = 0;
                $totalProfitCalculated = 0;
                $idx = 1;

                foreach ($todayTransactions as $trx) {
                    $omset  = (int)$trx->total_price;
                    $profit = (int)($trx->total_price - $trx->total_cost);
                    $totalOmsetCalculated  += $omset;
                    $totalProfitCalculated += $profit;
                    $rowClass = $idx % 2 === 0 ? ' class="stripe"' : '';

                    echo '<tr' . $rowClass . '>';
                    echo '<td class="center">' . $idx++ . '</td>';
                    echo '<td class="center">' . htmlspecialchars($trx->created_at->translatedFormat('H:i')) . '</td>';
                    echo '<td class="center bold text">' . htmlspecialchars($trx->transaction_code) . '</td>';
                    echo '<td>' . htmlspecialchars($trx->cashier->name ?? 'N/A') . '</td>';
                    echo '<td class="center">' . htmlspecialchars($trx->branch ?? 'Cabang Rumah') . '</td>';
                    echo '<td class="text">' . htmlspecialchars($trx->items_summary) . '</td>';
                    echo '<td class="center">' . htmlspecialchars($trx->payment_method) . '</td>';
                    echo '<td class="currency">' . $this->rupiah($omset) . '</td>';
                    echo '<td class="currency">' . $this->rupiah($profit) . '</td>';
                    echo '</tr>';
                }

                $count     = count($todayTransactions);
                $avgOmsetCalculated  = $count > 0 ? (int)round($totalOmsetCalculated / $count) : 0;
                $avgProfitCalculated = $count > 0 ? (int)round($totalProfitCalculated / $count) : 0;

                echo '<tr><td colspan="9" class="spacer"></td></tr>';

                echo '<tr class="total-row">';
                echo '<td colspan="7" class="center bold" style="font-size:11pt;">GRAND TOTAL (' . $count . ' Transaksi)</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalOmsetCalculated) . '</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalProfitCalculated) . '</td>';
                echo '</tr>';

                echo '<tr class="avg-row">';
                echo '<td colspan="7" class="center bold">RATA-RATA PER TRANSAKSI</td>';
                echo '<td class="currency">' . $this->rupiah($avgOmsetCalculated) . '</td>';
                echo '<td class="currency">' . $this->rupiah($avgProfitCalculated) . '</td>';
                echo '</tr>';

                echo '</tbody></table>';

                if ($isPdf) {
                    echo $this->pdfSignature($printedBy);
                    echo $this->pdfFooter();
                }

                echo '</body></html>';
            };

            if ($isPdf) {
                try {
                    ob_start();
                    $renderTemplate();
                    $html = ob_get_clean();

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                    $pdf->setPaper('a4', 'landscape');
                    return $pdf->download(str_replace('.xls', '.pdf', $filename));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Owner Report PDF (Harian) failed: ' . $e->getMessage());
                    return response('Gagal mengekspor PDF Harian: ' . $e->getMessage(), 500);
                }
            }

            return response()->streamDownload($renderTemplate, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } elseif ($filterType === 'mingguan') {
            $filename = $filePrefix . $branchSuffix . $channelSuffix . '_' . $now->format('Y-m-d') . '.xls';
            
            $days = [];
            for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
                $days[$date->format('Y-m-d')] = [
                    'label' => $date->translatedFormat('l'),
                    'sub_label' => $date->translatedFormat('d M Y'),
                    'omset' => 0,
                    'profit' => 0,
                    'count' => 0,
                ];
            }

            $query = $baseQuery();

            $weeklyTrxs = $query->whereBetween('created_at', [$startOfWeek, $endOfWeek])->get();
            foreach ($weeklyTrxs as $trx) {
                $dateStr = $trx->created_at->format('Y-m-d');
                if (isset($days[$dateStr])) {
                    $days[$dateStr]['omset'] += (int)$trx->total_price;
                    $days[$dateStr]['profit'] += (int)($trx->total_price - $trx->total_cost);
                    $days[$dateStr]['count']++;
                }
            }

            // Pre-calculate stats
            $totalOmset = 0;
            $totalProfit = 0;
            $totalCount = 0;
            foreach ($days as $day) {
                $totalOmset += $day['omset'];
                $totalProfit += $day['profit'];
                $totalCount += $day['count'];
            }

            $renderTemplate = function () use ($days, $startOfWeek, $endOfWeek, $activeBranch, $channelLabel, $printDate, $printedBy, $isPdf, $totalOmset, $totalProfit, $totalCount) {
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>' . $this->excelStyles($isPdf) . '</style></head><body>';

                if ($isPdf) {
                    echo $this->pdfHeader('LAPORAN PENJUALAN MINGGUAN');
                    
                    $cards = [
                        ['label' => 'Total Omset', 'value' => $this->rupiah($totalOmset), 'color' => '#059669'],
                        ['label' => 'Profit Bersih', 'value' => $this->rupiah($totalProfit), 'color' => '#2563eb'],
                        ['label' => 'Total Transaksi', 'value' => $totalCount . ' Trx', 'color' => '#d97706']
                    ];
                    echo $this->pdfCards($cards);
                }

                // Metadata
                echo '<table style="margin-bottom:16px; width:100%;">';
                if (!$isPdf) {
                    echo '<tr><td colspan="7" class="title" style="height:45px;">LAPORAN PENJUALAN MINGGUAN</td></tr>';
                    echo '<tr><td colspan="7" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
                    echo '<tr><td colspan="7" class="spacer"></td></tr>';
                }
                echo '<tr><td class="meta-label">Rentang Waktu</td><td colspan="6" class="meta-value">' . htmlspecialchars($startOfWeek->translatedFormat('d M Y') . ' s/d ' . $endOfWeek->translatedFormat('d M Y')) . '</td></tr>';
                echo '<tr><td class="meta-label">Filter Cabang</td><td colspan="6" class="meta-value">' . htmlspecialchars($activeBranch ? $activeBranch : 'Semua Cabang') . '</td></tr>';
                echo '<tr><td class="meta-label">Saluran Penjualan</td><td colspan="6" class="meta-value">' . htmlspecialchars($channelLabel) . '</td></tr>';
                echo '<tr><td class="meta-label">Tanggal Cetak</td><td colspan="6" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
                echo '<tr><td class="meta-label">Dicetak Oleh</td><td colspan="6" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
                echo '<tr><td colspan="7" class="spacer"></td></tr>';
                echo '</table>';

                // Table
                echo '<table>';
                echo '<thead><tr>';
                echo '<th style="width:45px;">No</th>';
                echo '<th style="width:190px;">Periode Tanggal</th>';
                echo '<th style="width:130px;">Hari</th>';
                echo '<th style="width:210px;">Total Omset</th>';
                echo '<th style="width:210px;">Profit Bersih</th>';
                echo '<th style="width:160px;">Margin</th>';
                echo '<th style="width:160px;">Jml Transaksi</th>';
                echo '</tr></thead><tbody>';

                $totalOmsetCalculated  = 0;
                $totalProfitCalculated = 0;
                $totalCountCalculated  = 0;
                $idx = 1;

                foreach ($days as $dateStr => $day) {
                    $omset  = $day['omset'];
                    $profit = $day['profit'];
                    $totalOmsetCalculated  += $omset;
                    $totalProfitCalculated += $profit;
                    $totalCountCalculated  += $day['count'];
                    $rowClass = $idx % 2 === 0 ? ' class="stripe"' : '';

                    $marginPct = $omset > 0 ? round(($profit / $omset) * 100, 1) . '%' : '-';

                    echo '<tr' . $rowClass . '>';
                    echo '<td class="center">' . $idx++ . '</td>';
                    echo '<td class="center">' . htmlspecialchars($day['sub_label']) . '</td>';
                    echo '<td class="center">' . htmlspecialchars($day['label']) . '</td>';
                    echo '<td class="currency">' . $this->rupiah($omset) . '</td>';
                    echo '<td class="currency">' . $this->rupiah($profit) . '</td>';
                    echo '<td class="percent center">' . $marginPct . '</td>';
                    echo '<td class="center">' . $day['count'] . ' Trx</td>';
                    echo '</tr>';
                }

                $overallMarginPct = $totalOmsetCalculated > 0 ? round(($totalProfitCalculated / $totalOmsetCalculated) * 100, 1) . '%' : '-';
                $avgOmset  = count($days) > 0 ? (int)round($totalOmsetCalculated  / count($days)) : 0;
                $avgProfit = count($days) > 0 ? (int)round($totalProfitCalculated / count($days)) : 0;

                echo '<tr><td colspan="7" class="spacer"></td></tr>';

                echo '<tr class="total-row">';
                echo '<td colspan="3" class="center bold" style="font-size:11pt;">GRAND TOTAL</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalOmsetCalculated) . '</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalProfitCalculated) . '</td>';
                echo '<td class="percent center bold">' . $overallMarginPct . '</td>';
                echo '<td class="center bold">' . $totalCountCalculated . ' Trx</td>';
                echo '</tr>';

                echo '<tr class="avg-row">';
                echo '<td colspan="3" class="center bold">RATA-RATA HARIAN</td>';
                echo '<td class="currency">' . $this->rupiah($avgOmset) . '</td>';
                echo '<td class="currency">' . $this->rupiah($avgProfit) . '</td>';
                echo '<td colspan="2" class="center">-</td>';
                echo '</tr>';

                echo '</tbody></table>';

                if ($isPdf) {
                    echo $this->pdfSignature($printedBy);
                    echo $this->pdfFooter();
                }

                echo '</body></html>';
            };

            if ($isPdf) {
                try {
                    ob_start();
                    $renderTemplate();
                    $html = ob_get_clean();

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                    $pdf->setPaper('a4', 'portrait');
                    return $pdf->download(str_replace('.xls', '.pdf', $filename));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Owner Report PDF (Mingguan) failed: ' . $e->getMessage());
                    return response('Gagal mengekspor PDF Mingguan: ' . $e->getMessage(), 500);
                }
            }

            return response()->streamDownload($renderTemplate, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } else { // bulanan
            $filename = $filePrefix . $branchSuffix . $channelSuffix . '_' . $now->format('Y-m-d') . '.xls';
            $daysInMonth = $startOfMonth->daysInMonth;

            $weeksBreakdown = [
                1 => ['start' => 1, 'end' => 7, 'label' => 'Minggu 1', 'sub_label' => '01 - 07 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0],
                2 => ['start' => 8, 'end' => 14, 'label' => 'Minggu 2', 'sub_label' => '08 - 14 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0],
                3 => ['start' => 15, 'end' => 21, 'label' => 'Minggu 3', 'sub_label' => '15 - 21 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0],
                4 => ['start' => 22, 'end' => 28, 'label' => 'Minggu 4', 'sub_label' => '22 - 28 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0],
            ];
            if ($daysInMonth > 28) {
                $weeksBreakdown[5] = ['start' => 29, 'end' => $daysInMonth, 'label' => 'Minggu 5', 'sub_label' => '29 - ' . $daysInMonth . ' ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0];
            }

            $query = $baseQuery();

            $monthlyTrxs = $query->whereBetween('created_at', [$startOfMonth, $endOfMonth])->get();
            foreach ($monthlyTrxs as $trx) {
                $day = $trx->created_at->day;
                foreach ($weeksBreakdown as $wNum => &$w) {
                    if ($day >= $w['start'] && $day <= $w['end']) {
                        $w['omset'] += (int)$trx->total_price;
                        $w['profit'] += (int)($trx->total_price - $trx->total_cost);
                        $w['count']++;
                        break;
                    }
                }
            }
            unset($w);

            // Pre-calculate stats
            $totalOmset = 0;
            $totalProfit = 0;
            $totalCount = 0;
            foreach ($weeksBreakdown as $w) {
                $totalOmset += $w['omset'];
                $totalProfit += $w['profit'];
                $totalCount += $w['count'];
            }

            $renderTemplate = function () use ($weeksBreakdown, $startOfMonth, $activeBranch, $channelLabel, $printDate, $printedBy, $isPdf, $totalOmset, $totalProfit, $totalCount) {
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>' . $this->excelStyles($isPdf) . '</style></head><body>';

                if ($isPdf) {
                    echo $this->pdfHeader('LAPORAN PENJUALAN BULANAN');
                    
                    $cards = [
                        ['label' => 'Total Omset', 'value' => $this->rupiah($totalOmset), 'color' => '#059669'],
                        ['label' => 'Profit Bersih', 'value' => $this->rupiah($totalProfit), 'color' => '#2563eb'],
                        ['label' => 'Total Transaksi', 'value' => $totalCount . ' Trx', 'color' => '#d97706']
                    ];
                    echo $this->pdfCards($cards);
                }

                // Metadata
                echo '<table style="margin-bottom:16px; width:100%;">';
                if (!$isPdf) {
                    echo '<tr><td colspan="7" class="title" style="height:45px;">LAPORAN PENJUALAN BULANAN</td></tr>';
                    echo '<tr><td colspan="7" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
                    echo '<tr><td colspan="7" class="spacer"></td></tr>';
                }
                echo '<tr><td class="meta-label">Rentang Waktu</td><td colspan="6" class="meta-value">' . htmlspecialchars($startOfMonth->translatedFormat('F Y')) . '</td></tr>';
                echo '<tr><td class="meta-label">Filter Cabang</td><td colspan="6" class="meta-value">' . htmlspecialchars($activeBranch ? $activeBranch : 'Semua Cabang') . '</td></tr>';
                echo '<tr><td class="meta-label">Saluran Penjualan</td><td colspan="6" class="meta-value">' . htmlspecialchars($channelLabel) . '</td></tr>';
                echo '<tr><td class="meta-label">Tanggal Cetak</td><td colspan="6" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
                echo '<tr><td class="meta-label">Dicetak Oleh</td><td colspan="6" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
                echo '<tr><td colspan="7" class="spacer"></td></tr>';
                echo '</table>';

                // Table
                echo '<table>';
                echo '<thead><tr>';
                echo '<th style="width:45px;">No</th>';
                echo '<th style="width:160px;">Periode</th>';
                echo '<th style="width:220px;">Rentang Tanggal</th>';
                echo '<th style="width:210px;">Total Omset</th>';
                echo '<th style="width:210px;">Profit Bersih</th>';
                echo '<th style="width:160px;">Margin</th>';
                echo '<th style="width:160px;">Jml Transaksi</th>';
                echo '</tr></thead><tbody>';

                $totalOmsetCalculated  = 0;
                $totalProfitCalculated = 0;
                $totalCountCalculated  = 0;
                $idx = 1;

                foreach ($weeksBreakdown as $wNum => $w) {
                    $omset  = $w['omset'];
                    $profit = $w['profit'];
                    $totalOmsetCalculated  += $omset;
                    $totalProfitCalculated += $profit;
                    $totalCountCalculated  += $w['count'];
                    $rowClass = $idx % 2 === 0 ? ' class="stripe"' : '';

                    $marginPct = $omset > 0 ? round(($profit / $omset) * 100, 1) . '%' : '-';

                    echo '<tr' . $rowClass . '>';
                    echo '<td class="center">' . $idx++ . '</td>';
                    echo '<td class="center">' . htmlspecialchars($w['label']) . '</td>';
                    echo '<td class="center">' . htmlspecialchars($w['sub_label']) . '</td>';
                    echo '<td class="currency">' . $this->rupiah($omset) . '</td>';
                    echo '<td class="currency">' . $this->rupiah($profit) . '</td>';
                    echo '<td class="percent center">' . $marginPct . '</td>';
                    echo '<td class="center">' . $w['count'] . ' Trx</td>';
                    echo '</tr>';
                }

                $overallMarginPct = $totalOmsetCalculated > 0 ? round(($totalProfitCalculated / $totalOmsetCalculated) * 100, 1) . '%' : '-';
                $avgOmset  = count($weeksBreakdown) > 0 ? (int)round($totalOmsetCalculated  / count($weeksBreakdown)) : 0;
                $avgProfit = count($weeksBreakdown) > 0 ? (int)round($totalProfitCalculated / count($weeksBreakdown)) : 0;

                echo '<tr><td colspan="7" class="spacer"></td></tr>';

                echo '<tr class="total-row">';
                echo '<td colspan="3" class="center bold" style="font-size:11pt;">GRAND TOTAL</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalOmsetCalculated) . '</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalProfitCalculated) . '</td>';
                echo '<td class="percent center bold">' . $overallMarginPct . '</td>';
                echo '<td class="center bold">' . $totalCountCalculated . ' Trx</td>';
                echo '</tr>';

                echo '<tr class="avg-row">';
                echo '<td colspan="3" class="center bold">RATA-RATA MINGGUAN</td>';
                echo '<td class="currency">' . $this->rupiah($avgOmset) . '</td>';
                echo '<td class="currency">' . $this->rupiah($avgProfit) . '</td>';
                echo '<td colspan="2" class="center">-</td>';
                echo '</tr>';

                echo '</tbody></table>';

                if ($isPdf) {
                    echo $this->pdfSignature($printedBy);
                    echo $this->pdfFooter();
                }

                echo '</body></html>';
            };

            if ($isPdf) {
                try {
                    ob_start();
                    $renderTemplate();
                    $html = ob_get_clean();

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                    $pdf->setPaper('a4', 'portrait');
                    return $pdf->download(str_replace('.xls', '.pdf', $filename));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Owner Report PDF (Bulanan) failed: ' . $e->getMessage());
                    return response('Gagal mengekspor PDF Bulanan: ' . $e->getMessage(), 500);
                }
            }

            return response()->streamDownload($renderTemplate, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }
    }

    /**
     * Private helper methods for formal PDF layout.
     */
    private function pdfHeader(string $title): string
    {
        $logoPath = public_path('images/logo.png');
        $logoHtml = '';
        if (file_exists($logoPath) && extension_loaded('gd')) {
            try {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoHtml = '<img src="data:image/png;base64,' . $logoData . '" style="height: 50px; max-width: 100px; display: block;">';
            } catch (\Throwable $e) {
                $logoHtml = '<div style="width:50px;height:50px;background-color:#059669;border-radius:8px;text-align:center;line-height:50px;font-size:16pt;font-weight:bold;color:#ffffff;display:inline-block;">PK</div>';
            }
        } else {
            $logoHtml = '<div style="width:50px;height:50px;background-color:#059669;border-radius:8px;text-align:center;line-height:50px;font-size:16pt;font-weight:bold;color:#ffffff;display:inline-block;">PK</div>';
        }

        return '
        <table style="width: 100%; border: none !important; margin-bottom: 8px; background: transparent;">
            <tr style="border: none !important; background: transparent;">
                <td style="width: 60px; border: none !important; text-align: left; vertical-align: middle; background: transparent; padding: 0;">
                    ' . $logoHtml . '
                </td>
                <td style="border: none !important; text-align: left; vertical-align: middle; background: transparent; padding-left: 10px;">
                    <div style="font-size: 14pt; font-weight: bold; color: #064e3b; letter-spacing: 0.5px; line-height: 1.2;">PUSAT KURMA PREMIUM</div>
                    <div style="font-size: 8.5pt; color: #4b5563; margin-top: 1px;">Jl. Raya Cianjur - Bandung No. 12, Cianjur, Jawa Barat</div>
                    <div style="font-size: 7.5pt; color: #6b7280; margin-top: 1px;">Telp: 0812-3456-7890 | Email: info@pusatkurma.com</div>
                </td>
                <td style="border: none !important; text-align: right; vertical-align: middle; background: transparent; padding: 0;">
                    <div style="font-size: 10pt; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 0.5px; max-width: 250px; line-height: 1.2;">' . htmlspecialchars($title) . '</div>
                    <div style="font-size: 7.5pt; color: #6b7280; margin-top: 1px;">Laporan Keuangan Resmi</div>
                </td>
            </tr>
        </table>
        <div style="border-top: 2px solid #059669; border-bottom: 1px solid #059669; height: 2px; margin-bottom: 12px; margin-top: 3px;"></div>
        ';
    }

    private function pdfFooter(): string
    {
        return '
        <script type="text/php">
            if ( isset($pdf) ) {
                $font = $fontMetrics->get_font("Arial, Helvetica, sans-serif", "normal");
                $size = 8;
                $pageText = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
                $y = $pdf->get_height() - 25;
                $x = ($pdf->get_width() - $fontMetrics->get_text_width($pageText, $font, $size)) / 2;
                $pdf->text($x, $y, $pageText, $font, $size, array(107/255, 114/255, 128/255));
            }
        </script>
        ';
    }

    private function pdfSignature(string $printedBy): string
    {
        return '
        <table style="width: 100%; margin-top: 20px; border: none !important; background: transparent; page-break-inside: avoid;">
            <tr style="border: none !important; background: transparent;">
                <td style="width: 50%; text-align: center; border: none !important; background: transparent; padding: 0;">
                    <p style="font-size: 8.5pt; margin-bottom: 30px; color: #374151;">Dibuat Oleh,</p>
                    <p style="font-size: 8.5pt; font-weight: bold; text-decoration: underline; color: #111827; margin: 0;">' . htmlspecialchars($printedBy) . '</p>
                    <p style="font-size: 7.5pt; color: #6b7280; margin: 0;">Staff Operasional</p>
                </td>
                <td style="width: 50%; text-align: center; border: none !important; background: transparent; padding: 0;">
                    <p style="font-size: 8.5pt; margin-bottom: 30px; color: #374151;">Disetujui Oleh,</p>
                    <p style="font-size: 8.5pt; font-weight: bold; text-decoration: underline; color: #111827; margin: 0;">....................................</p>
                    <p style="font-size: 7.5pt; color: #6b7280; margin: 0;">Manager / Owner</p>
                </td>
            </tr>
        </table>
        ';
    }

    private function pdfCards(array $cards): string
    {
        $html = '<table style="width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 12px 0; border: none !important; background: transparent; margin-left: -12px; margin-right: -12px;">';
        $html .= '<tr style="border: none !important; background: transparent;">';
        
        $width = round(100 / count($cards)) . '%';
        foreach ($cards as $card) {
            $html .= '<td style="width: ' . $width . '; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 8px; background-color: #f8fafc; text-align: center;">';
            $html .= '<div style="font-size: 7.5pt; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 2px;">' . htmlspecialchars($card['label']) . '</div>';
            $html .= '<div style="font-size: 11pt; color: ' . ($card['color'] ?? '#0f172a') . '; font-weight: 800;">' . htmlspecialchars($card['value']) . '</div>';
            $html .= '</td>';
        }
        
        $html .= '</tr>';
        $html .= '</table>';
        return $html;
    }

    /**
     * Get AI Business Performance Analysis & Suggestions.
     * Supports multiple skill modes via ?skill= query parameter:
     *   general | forecast | branch_comparison | restock | slow_moving | expense_analysis
     */
    public function getPerformanceAnalysis(Request $request)
    {
        try {
            $dateRanges = $this->resolveDateRanges($request);
            $filterType = $dateRanges['filterType'];
            $start = $dateRanges['start'];
            $end = $dateRanges['end'];
            $comparisonStart = $dateRanges['comparisonStart'];
            $comparisonEnd = $dateRanges['comparisonEnd'];
            $comparisonLabel = $dateRanges['comparisonLabel'];
            $titleLabel = $dateRanges['titleLabel'];

            $selectedBranch = $request->query('branch', '');

            // ── Skill parameter: selects which AI analysis to run ──────────
            $skill = $request->query('skill', 'general');
            if (!in_array($skill, ['general', 'forecast', 'branch_comparison', 'restock', 'slow_moving', 'expense_analysis', 'peak_hours', 'product_bundling'])) {
                $skill = 'general';
            }

            $selectedChannel = $this->normalizeChannel($request->query('channel'));
            $isChannelFiltered = $selectedChannel !== 'all';
            $channelText = $this->channelLabel($selectedChannel);

            // Cache key includes skill and channel so each mode has its own cache entry
            $cacheKey = 'owner_ai_analysis_' . md5(json_encode([
                'filter_type' => $filterType,
                'start'       => $start->toDateTimeString(),
                'end'         => $end->toDateTimeString(),
                'branch'      => $selectedBranch,
                'channel'     => $selectedChannel,
                'skill'       => $skill,
            ]));

            $forceRefresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOLEAN);

            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            $analysis = Cache::remember($cacheKey, 3600, function () use (
                $start, $end, $comparisonStart, $comparisonEnd, $selectedBranch, $selectedChannel, $isChannelFiltered, $channelText, $filterType, $titleLabel, $comparisonLabel, $skill
            ) {
                $baseQuery = fn() => $this->makeTransactionQuery($selectedBranch, $selectedChannel);

                $expenseQuery = fn() => ($selectedBranch && $selectedBranch !== '')
                    ? \App\Models\Expense::where('branch', $selectedBranch)
                    : \App\Models\Expense::query();

                $activeStats = $baseQuery()
                    ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as gross_profit, COUNT(id) as count, AVG(total_price) as aov')
                    ->whereBetween('created_at', [$start, $end])
                    ->first();

                $comparisonStats = $baseQuery()
                    ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as gross_profit, COUNT(id) as count, AVG(total_price) as aov')
                    ->whereBetween('created_at', [$comparisonStart, $comparisonEnd])
                    ->first();

                $activeExpenses = $isChannelFiltered
                    ? 0.0
                    : (float) $expenseQuery()->whereBetween('created_at', [$start, $end])->sum('amount');

                $comparisonExpenses = $isChannelFiltered
                    ? 0.0
                    : (float) $expenseQuery()->whereBetween('created_at', [$comparisonStart, $comparisonEnd])->sum('amount');

                $activeRevenue = (float) ($activeStats->omset ?? 0);
                $activeGrossProfit = (float) ($activeStats->gross_profit ?? 0);
                $activeProfit = $activeGrossProfit - $activeExpenses;
                $activeTransactionsCount = (int) ($activeStats->count ?? 0);
                $activeAov = (float) ($activeStats->aov ?? 0);

                $comparisonRevenue = (float) ($comparisonStats->omset ?? 0);
                $comparisonProfit = ((float) ($comparisonStats->gross_profit ?? 0)) - $comparisonExpenses;
                $comparisonTransactionsCount = (int) ($comparisonStats->count ?? 0);

                // Margin & ratios
                $grossMargin = $activeRevenue > 0 ? round(($activeGrossProfit / $activeRevenue) * 100, 1) : 0;
                $netMargin = $activeRevenue > 0 ? round(($activeProfit / $activeRevenue) * 100, 1) : 0;
                $oer = $activeRevenue > 0 ? round(($activeExpenses / $activeRevenue) * 100, 1) : 0;

                // Calculate growth percentages
                $revenueGrowthPercent = 0;
                if ($comparisonRevenue > 0) {
                    $revenueGrowthPercent = (($activeRevenue - $comparisonRevenue) / $comparisonRevenue) * 100;
                } elseif ($activeRevenue > 0) {
                    $revenueGrowthPercent = 100;
                }

                $profitGrowthPercent = 0;
                if ($comparisonProfit > 0) {
                    $profitGrowthPercent = (($activeProfit - $comparisonProfit) / $comparisonProfit) * 100;
                } elseif ($activeProfit > 0) {
                    $profitGrowthPercent = 100;
                }

                $expenseGrowthPercent = 0;
                if ($comparisonExpenses > 0) {
                    $expenseGrowthPercent = (($activeExpenses - $comparisonExpenses) / $comparisonExpenses) * 100;
                } elseif ($activeExpenses > 0) {
                    $expenseGrowthPercent = 100;
                }

                // Get Best Sellers
                $transactions = $baseQuery()->whereBetween('created_at', [$start, $end])->get();
                $bestSellersList = $this->getBestSellers($transactions, 5);
                $bestSellersStr = '';
                foreach ($bestSellersList as $bs) {
                    $bestSellersStr .= "- {$bs['name']}: " . number_format($bs['qty'], 2) . " {$bs['unit']} (dalam {$bs['count']} transaksi)\n";
                }
                if (empty($bestSellersStr)) {
                    $bestSellersStr = "Tidak ada penjualan pada periode ini.\n";
                }

                // Get low stock products
                $lowStockProducts = Product::where('stock', '<=', 10)
                    ->select('name', 'stock')
                    ->take(10)
                    ->get();
                $lowStockStr = '';
                foreach ($lowStockProducts as $lp) {
                    $lowStockStr .= "- {$lp['name']}: sisa stok " . number_format($lp['stock'], 2) . "\n";
                }
                if (empty($lowStockStr)) {
                    $lowStockStr = "Semua produk memiliki stok aman (> 10 pcs).\n";
                }

                // ── Build prompt & gather data based on selected skill ─────
                $branchText  = ($selectedBranch ? "Cabang: {$selectedBranch}" : "Semua Cabang") . " | Saluran: {$channelText}";
                $geminiKey   = config('services.gemini.key');
                $geminiModel = config('services.gemini.model', 'gemini-2.5-flash');

                if (empty($geminiKey)) {
                    throw new \Exception('GEMINI_API_KEY belum dikonfigurasi. Silakan isi GEMINI_API_KEY pada file .env Anda (dapat diperoleh gratis di https://aistudio.google.com/app/apikey).');
                }

                // ────────────────────────────────────────────────────────────
                // SKILL: general — Analisis Umum (default)
                // ────────────────────────────────────────────────────────────
                if ($skill === 'general') {
                    $geminiPrompt = "Anda adalah Konsultan Bisnis Profesional berstandar premium untuk toko retail Pusat Kurma (toko kurma & makanan khas Ramadhan).\n"
                        . "Analisis data performa bisnis berikut:\n"
                        . "--- DATA ---\n"
                        . "- Periode: {$titleLabel} ({$filterType})\n"
                        . "- {$branchText}\n"
                        . "- Omset: Rp " . number_format($activeRevenue, 0, ',', '.') . " (" . number_format($revenueGrowthPercent, 1) . "% {$comparisonLabel})\n"
                        . "- Gross Profit Margin: {$grossMargin}%\n"
                        . "- Profit Bersih: Rp " . number_format($activeProfit, 0, ',', '.') . " (" . number_format($profitGrowthPercent, 1) . "% {$comparisonLabel})\n"
                        . "- Net Profit Margin: {$netMargin}%\n"
                        . "- Pengeluaran: Rp " . number_format($activeExpenses, 0, ',', '.') . " (" . number_format($expenseGrowthPercent, 1) . "% {$comparisonLabel})\n"
                        . "- Rasio Beban Pengeluaran (OER): {$oer}%\n"
                        . "- Jumlah Transaksi: " . number_format($activeTransactionsCount) . " Trx\n"
                        . "- Rata-rata Nilai Belanja (AOV): Rp " . number_format($activeAov, 0, ',', '.') . "\n"
                        . "- 5 Produk Terlaris:\n{$bestSellersStr}"
                        . "- Produk Stok Menipis (<=10):\n{$lowStockStr}\n"
                        . "--------------------\n\n"
                        . "Berikan analisis dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Ringkasan Eksekutif**: Tinjauan tajam kesehatan keuangan toko pada periode ini.\n"
                        . "2. **Analisis Mendalam & Tren**: Bahas pertumbuhan omset, margin kotor vs margin bersih, AOV, dan rasio beban pengeluaran secara kritis.\n"
                        . "3. **Saran & Rekomendasi Bisnis**: Minimal 3 saran aksi konkret dan taktis.\n\n"
                        . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan teks profesional dan format Markdown yang rapi tanpa emoji sama sekali. Tone profesional, optimis namun realistis. Mulai langsung dengan judul Markdown.";

                // ────────────────────────────────────────────────────────────
                // SKILL: forecast — Prediksi / Forecast Penjualan
                // ────────────────────────────────────────────────────────────
                } elseif ($skill === 'forecast') {
                    $sixMonthsAgo = \Carbon\Carbon::now()->subMonths(5)->startOfMonth();
                    $now = \Carbon\Carbon::now();
                    $currentMonthKey = $now->format('Y-m');
                    $currentDay = $now->day;
                    $totalDaysInMonth = $now->daysInMonth;
                    $elapsedFraction = $currentDay / $totalDaysInMonth;

                    $monthlyData = $this->makeTransactionQuery($selectedBranch ?: null, $selectedChannel)
                        ->selectRaw(
                            "DATE_FORMAT(created_at, '%Y-%m') as month_key,
                             DATE_FORMAT(created_at, '%M %Y') as month_label,
                             SUM(total_price) as omset,
                             SUM(total_price - total_cost) as gross_profit,
                             COUNT(id) as trx_count"
                        )
                        ->where('created_at', '>=', $sixMonthsAgo)
                        ->groupBy('month_key', 'month_label')
                        ->orderBy('month_key')
                        ->get();

                    $monthlyExpenses = $isChannelFiltered
                        ? collect()
                        : \App\Models\Expense::selectRaw(
                                "DATE_FORMAT(created_at, '%Y-%m') as month_key, SUM(amount) as total_expense"
                            )
                            ->when($selectedBranch, fn($q) => $q->where('branch', $selectedBranch))
                            ->where('created_at', '>=', $sixMonthsAgo)
                            ->groupBy('month_key')
                            ->pluck('total_expense', 'month_key');

                    $historyStr = '';
                    foreach ($monthlyData as $m) {
                        $expense   = $monthlyExpenses[$m->month_key] ?? 0;
                        $netProfit = ($m->gross_profit ?? 0) - $expense;
                        
                        $isCurrentMonth = ($m->month_key === $currentMonthKey);
                        if ($isCurrentMonth && $elapsedFraction > 0) {
                            $projectedOmset = ($m->omset ?? 0) / $elapsedFraction;
                            $projectedNetProfit = $netProfit / $elapsedFraction;
                            $projectedTrx = ($m->trx_count ?? 0) / $elapsedFraction;

                            $historyStr .= "- {$m->month_label} (Sedang Berjalan - s/d tanggal {$currentDay}/{$totalDaysInMonth}):\n"
                                . "  * Riwayat Aktual: Omset Rp " . number_format($m->omset ?? 0, 0, ',', '.')
                                . " | Net Profit Rp " . number_format($netProfit, 0, ',', '.')
                                . " | " . ($m->trx_count ?? 0) . " Transaksi\n"
                                . "  * Pro-rata Proyeksi Akhir Bulan: Omset Rp " . number_format($projectedOmset, 0, ',', '.')
                                . " | Net Profit Rp " . number_format($projectedNetProfit, 0, ',', '.')
                                . " | ~" . round($projectedTrx) . " Transaksi\n";
                        } else {
                            $historyStr .= "- {$m->month_label}: Omset Rp " . number_format($m->omset ?? 0, 0, ',', '.')
                                . " | Net Profit Rp " . number_format($netProfit, 0, ',', '.')
                                . " | " . ($m->trx_count ?? 0) . " Transaksi\n";
                        }
                    }
                    if (empty(trim($historyStr))) {
                        $historyStr = "Tidak ada data histori penjualan 6 bulan terakhir.\n";
                    }

                    $geminiPrompt = "Anda adalah Analis Bisnis & Forecasting profesional untuk Pusat Kurma (spesialis kurma & makanan premium).\n"
                        . "Berdasarkan data historis penjualan 6 bulan terakhir berikut:\n"
                        . "--- HISTORI 6 BULAN (TERMASUK BULAN BERJALAN DENGAN PROYEKSI AKHIR BULAN) ---\n{$historyStr}"
                        . "- Filter: {$branchText}\n"
                        . "----------------------\n\n"
                        . "Catatan penting untuk bulan yang sedang berjalan: Jangan anggap omset menurun di bulan ini hanya karena angkanya lebih kecil dari bulan sebelumnya. Gunakan angka 'Pro-rata Proyeksi Akhir Bulan' sebagai estimasi performa bulan berjalan ini untuk peramalan/forecasting yang akurat.\n\n"
                        . "Buat laporan prediksi/forecast dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Analisis Tren Historis**: Identifikasi tren, pola musiman, dan konsistensi pertumbuhan.\n"
                        . "2. **Prediksi 3 Bulan ke Depan**: Estimasi omset & profit untuk 3 bulan mendatang (optimis vs konservatif).\n"
                        . "3. **Risiko & Peluang**: Faktor risiko dan peluang yang harus diwaspadai.\n"
                        . "4. **Saran Target & Strategi**: Target omset realistis bulan depan + 2-3 strategi konkret.\n\n"
                        . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan format markdown bersih dan profesional. Gunakan angka spesifik dari histori dan proyeksi. Mulai langsung dengan judul Markdown.";

                // ────────────────────────────────────────────────────────────
                // SKILL: branch_comparison — Perbandingan Antar Cabang
                // ────────────────────────────────────────────────────────────
                } elseif ($skill === 'branch_comparison') {
                    $branchStats = $this->makeTransactionQuery(null, $selectedChannel)
                        ->selectRaw(
                            "COALESCE(branch, 'Cabang Rumah') as branch_name,
                             SUM(total_price) as omset,
                             SUM(total_price - total_cost) as gross_profit,
                             COUNT(id) as trx_count,
                             AVG(total_price) as avg_trx_value"
                        )
                        ->whereBetween('created_at', [$start, $end])
                        ->groupBy('branch_name')
                        ->orderByDesc('omset')
                        ->get();

                    $branchExpenses = $isChannelFiltered
                        ? collect()
                        : \App\Models\Expense::selectRaw(
                                "COALESCE(branch, 'Cabang Rumah') as branch_name, SUM(amount) as total_expense"
                            )
                            ->whereBetween('created_at', [$start, $end])
                            ->groupBy('branch_name')
                            ->pluck('total_expense', 'branch_name');

                    $branchStr  = '';
                    $totalOmset = 0;
                    foreach ($branchStats as $b) {
                        $expense   = $branchExpenses[$b->branch_name] ?? 0;
                        $netProfit = ($b->gross_profit ?? 0) - $expense;
                        $margin    = ($b->omset ?? 0) > 0 ? round(($netProfit / $b->omset) * 100, 1) : 0;
                        $totalOmset += ($b->omset ?? 0);
                        $branchStr .= "- **{$b->branch_name}**: Omset Rp " . number_format($b->omset ?? 0, 0, ',', '.')
                            . " | Net Profit Rp " . number_format($netProfit, 0, ',', '.')
                            . " | Margin {$margin}% | " . ($b->trx_count ?? 0) . " Trx"
                            . " | Rata-rata/Trx Rp " . number_format($b->avg_trx_value ?? 0, 0, ',', '.') . "\n";
                    }
                    if (empty(trim($branchStr))) {
                        $branchStr = "Tidak ada data transaksi untuk periode ini.\n";
                    }

                    $geminiPrompt = "Anda adalah Konsultan Manajemen Multi-Cabang profesional untuk Pusat Kurma.\n"
                        . "Analisis perbandingan performa semua cabang periode {$titleLabel}:\n"
                        . "--- PERFORMA CABANG ---\n{$branchStr}"
                        . "- Total Omset Seluruh Cabang: Rp " . number_format($totalOmset, 0, ',', '.') . "\n"
                        . "----------------------\n\n"
                        . "Buat laporan perbandingan cabang dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Ranking & Performa Cabang**: Ranking dari terbaik ke terburuk + kontribusi persentase.\n"
                        . "2. **Analisis Gap & Kesenjangan**: Apa yang membuat cabang terbaik unggul?\n"
                        . "3. **Rekomendasi per Cabang**: Saran spesifik untuk setiap cabang.\n"
                        . "4. **Strategi Pertumbuhan Jaringan**: Saran strategis untuk meningkatkan performa keseluruhan.\n\n"
                        . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan format markdown bersih dan profesional. Gunakan angka spesifik. Bersikap kritis namun konstruktif. Mulai langsung dengan judul Markdown.";

                // ────────────────────────────────────────────────────────────
                // SKILL: restock — Saran Restok Cerdas
                // ────────────────────────────────────────────────────────────
                } elseif ($skill === 'restock') {
                    $soldTrx     = $baseQuery()->whereBetween('created_at', [$start, $end])->get();
                    $soldSummary = $this->getBestSellers($soldTrx, 100);

                    $allProducts = Product::select('name', 'stock', 'category', 'cost_price', 'selling_price')
                        ->where('is_bundle', false)
                        ->orderBy('stock')
                        ->get();

                    $restockStr = '';
                    $safeStr    = '';
                    foreach ($allProducts as $prod) {
                        $soldQty = 0;
                        foreach ($soldSummary as $s) {
                            if (strtolower($s['name']) === strtolower($prod->name)) {
                                $soldQty = $s['qty'];
                                break;
                            }
                        }
                        $status  = $prod->stock <= 0 ? 'HABIS' : ($prod->stock <= 10 ? 'KRITIS' : ($prod->stock <= 30 ? 'RENDAH' : 'AMAN'));
                        $line    = "- {$prod->name} ({$prod->category}): Stok={$prod->stock} | Terjual={$soldQty} | Status={$status}\n";
                        if ($prod->stock <= 30) {
                            $restockStr .= $line;
                        } else {
                            $safeStr .= $line;
                        }
                    }
                    if (empty(trim($restockStr))) {
                        $restockStr = "Semua produk memiliki stok di atas 30 unit.\n";
                    }

                    $geminiPrompt = "Anda adalah Manajer Inventori & Supply Chain profesional untuk Pusat Kurma.\n"
                        . "Analisis kondisi stok produk untuk periode {$titleLabel}:\n"
                        . "--- PRODUK PERLU PERHATIAN (Stok <= 30) ---\n{$restockStr}"
                        . "--- PRODUK STOK AMAN (Stok > 30) ---\n" . (empty(trim($safeStr)) ? "- Tidak ada\n" : $safeStr)
                        . "- {$branchText}\n"
                        . "--------------------------------------------\n\n"
                        . "Buat laporan saran restok dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Prioritas Restok Mendesak**: Produk HABIS/KRITIS yang harus direstok SEGERA + estimasi jumlah berdasarkan volume penjualan.\n"
                        . "2. **Restok Segera (Minggu Ini)**: Produk stok rendah yang perlu diisi dalam 1 minggu.\n"
                        . "3. **Strategi Manajemen Stok**: Safety stock minimum per kategori + strategi pengadaan efisien.\n"
                        . "4. **Estimasi Kebutuhan Modal Restok**: Perkiraan dana berdasarkan harga pokok produk.\n\n"
                        . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan format markdown bersih dan profesional. Saran sangat konkret, spesifik per produk. Mulai langsung dengan judul Markdown.";

                // ────────────────────────────────────────────────────────────
                // SKILL: slow_moving — Analisis Produk Slow-Moving
                // ────────────────────────────────────────────────────────────
                } elseif ($skill === 'slow_moving') {
                    $soldTrx     = $baseQuery()->whereBetween('created_at', [$start, $end])->get();
                    $soldSummary = $this->getBestSellers($soldTrx, 100);

                    $allProducts = Product::select('name', 'stock', 'category', 'selling_price', 'cost_price')
                        ->where('is_bundle', false)
                        ->where('stock', '>', 0)
                        ->orderBy('stock', 'desc')
                        ->get();

                    $avgSalesCount = count($soldSummary) > 0
                        ? array_sum(array_column($soldSummary, 'count')) / count($soldSummary)
                        : 0;

                    $slowStr = '';
                    $fastStr = '';
                    foreach ($allProducts as $prod) {
                        $found = null;
                        foreach ($soldSummary as $s) {
                            if (strtolower($s['name']) === strtolower($prod->name)) {
                                $found = $s;
                                break;
                            }
                        }
                        $trxCount      = $found ? $found['count'] : 0;
                        $soldQty       = $found ? $found['qty'] : 0;
                        $isSlow        = $trxCount < max(1, $avgSalesCount * 0.3);
                        $potentialProfit = $prod->stock * ($prod->selling_price - $prod->cost_price);
                        $line = "- {$prod->name} ({$prod->category}): Stok={$prod->stock} | Terjual={$soldQty} | {$trxCount}x Trx | Potensi Profit Tertahan=Rp" . number_format($potentialProfit, 0, ',', '.') . "\n";
                        if ($isSlow) {
                            $slowStr .= $line;
                        } else {
                            $fastStr .= $line;
                        }
                    }
                    if (empty(trim($slowStr))) {
                        $slowStr = "Tidak ada produk yang terdeteksi slow-moving pada periode ini.\n";
                    }

                    $geminiPrompt = "Anda adalah Konsultan Retail & Marketing profesional untuk Pusat Kurma.\n"
                        . "Analisis pergerakan produk pada periode {$titleLabel}:\n"
                        . "--- PRODUK SLOW-MOVING (Terjual < 30% rata-rata) ---\n{$slowStr}"
                        . "--- PRODUK FAST-MOVING ---\n" . (empty(trim($fastStr)) ? "- Data tidak tersedia\n" : $fastStr)
                        . "- {$branchText}\n"
                        . "-----------------------------------------------\n\n"
                        . "Buat laporan analisis produk slow-moving dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Identifikasi Produk Bermasalah**: Analisis mengapa produk-produk ini kurang laku.\n"
                        . "2. **Strategi Promosi per Produk**: Saran spesifik per produk (bundling, diskon, positioning ulang, dll).\n"
                        . "3. **Manajemen Stok Slow-Moving**: Cegah overstock, optimalkan modal tertahan.\n"
                        . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan format markdown bersih dan profesional. Saran promosi kreatif namun realistis. Mulai langsung dengan judul Markdown.";

                // ────────────────────────────────────────────────────────────
                // SKILL: peak_hours — Analisis Pola Waktu Terlaris
                // ────────────────────────────────────────────────────────────
                } elseif ($skill === 'peak_hours') {
                    $timeStats = $baseQuery()
                        ->selectRaw("
                            HOUR(created_at) as hour_val,
                            DAYNAME(created_at) as day_name,
                            COUNT(id) as trx_count,
                            SUM(total_price) as omset
                        ")
                        ->whereBetween('created_at', [$start, $end])
                        ->groupBy('hour_val', 'day_name')
                        ->orderByDesc('trx_count')
                        ->get();

                    $peakHoursStr = '';
                    foreach ($timeStats->take(15) as $ts) {
                        $peakHoursStr .= "- Hari {$ts->day_name} jam {$ts->hour_val}:00: {$ts->trx_count} Transaksi (Omset Rp " . number_format($ts->omset, 0, ',', '.') . ")\n";
                    }
                    if (empty(trim($peakHoursStr))) {
                        $peakHoursStr = "Tidak ada data penjualan pada periode ini.\n";
                    }

                    $geminiPrompt = "Anda adalah Konsultan Manajemen Operasional & Penjadwalan retail profesional untuk Pusat Kurma.\n"
                        . "Analisis data kepadatan transaksi berdasarkan jam dan hari berikut:\n"
                        . "--- POLA WAKTU TERLARIS (Top 15 Kepadatan) ---\n{$peakHoursStr}"
                        . "- Filter: {$branchText}\n"
                        . "- Periode: {$titleLabel}\n"
                        . "----------------------------------------------\n\n"
                        . "Buat laporan analisis pola waktu terlaris dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Identifikasi Pola Jam & Hari Puncak**: Analisis kapan waktu tersibuk toko Anda terjadi dan mengapa (pola makan, sepulang kerja, hari libur, dll).\n"
                        . "2. **Rekomendasi Alokasi Staf / Kasir**: Berikan saran konkret mengenai penjadwalan kasir/staf toko agar optimal di jam sibuk dan efisien (hemat biaya) di jam sepi.\n"
                        . "3. **Strategi Promosi Berdasarkan Waktu**: Usulkan strategi promosi bertarget waktu (misal: happy hour discount, flash sale pada jam sepi untuk menarik pelanggan).\n\n"
                        . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan format markdown bersih dan profesional. Berikan analisis yang sangat taktis dan operasional. Mulai langsung dengan judul Markdown.";

                // ────────────────────────────────────────────────────────────
                // SKILL: product_bundling — Rekomendasi Bundling Produk
                // ────────────────────────────────────────────────────────────
                } elseif ($skill === 'product_bundling') {
                    $transactionsForBundling = $baseQuery()
                        ->select('items_summary')
                        ->whereBetween('created_at', [$start, $end])
                        ->whereNotNull('items_summary')
                        ->get();

                    $pairs = [];
                    foreach ($transactionsForBundling as $trx) {
                        if (empty($trx->items_summary)) continue;
                        $parts = array_map('trim', explode(',', $trx->items_summary));
                        $cleanedItems = [];
                        foreach ($parts as $part) {
                            if (empty($part)) continue;
                            if (preg_match('/^(.*?)\s*\(.*?\)$/', $part, $matches)) {
                                $cleanedItems[] = trim($matches[1]);
                            } else {
                                $cleanedItems[] = $part;
                            }
                        }
                        $cleanedItems = array_values(array_unique($cleanedItems));
                        $count = count($cleanedItems);
                        for ($i = 0; $i < $count; $i++) {
                            for ($j = $i + 1; $j < $count; $j++) {
                                $itemA = $cleanedItems[$i];
                                $itemB = $cleanedItems[$j];
                                if (strcasecmp($itemA, $itemB) > 0) {
                                    $temp = $itemA;
                                    $itemA = $itemB;
                                    $itemB = $temp;
                                }
                                $key = $itemA . ' + ' . $itemB;
                                $pairs[$key] = ($pairs[$key] ?? 0) + 1;
                            }
                        }
                    }

                    arsort($pairs);

                    $bundlingStr = '';
                    $idx = 1;
                    foreach (array_slice($pairs, 0, 10, true) as $combination => $freq) {
                        $bundlingStr .= "- #{$idx} {$combination} : Dibeli bersamaan {$freq} kali\n";
                        $idx++;
                    }

                    if (empty(trim($bundlingStr))) {
                        $bundlingStr = "Tidak ditemukan kombinasi produk yang dibeli bersamaan pada periode ini.\n";
                    }

                    $geminiPrompt = "Anda adalah Konsultan Marketing & Merchandising retail profesional untuk Pusat Kurma.\n"
                        . "Analisis data keterkaitan produk yang dibeli bersamaan berikut:\n"
                        . "--- KOMBINASI PRODUK TERLARIS (Top 10 Pasangan) ---\n{$bundlingStr}"
                        . "- Filter: {$branchText}\n"
                        . "- Periode: {$titleLabel}\n"
                        . "--------------------------------------------------\n\n"
                        . "Buat laporan analisis bundling produk (Market Basket Analysis) dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Analisis Asosiasi Produk**: Jelaskan hubungan logis mengapa pelanggan membeli produk-produk tersebut bersamaan.\n"
                        . "2. **Rekomendasi Paket Bundling Cerdas**: Usulkan minimal 3 paket bundling produk (kasih nama paket menarik) lengkap dengan skema harga promosi yang disarankan.\n"
                        . "3. **Rekomendasi Tata Letak Toko (Cross-Merchandising)**: Berikan saran penataan produk di rak toko/display agar produk yang berasosiasi mudah dijangkau pelanggan untuk mendorong dorongan beli (*impulse buying*).\n\n"
                        . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan format markdown bersih dan profesional. Berikan ide-ide promosi kreatif yang sangat menjual untuk retail kurma & produk oleh-oleh premium. Mulai langsung dengan judul Markdown.";

                // ────────────────────────────────────────────────────────────
                // SKILL: expense_analysis — Efisiensi Pengeluaran
                // ────────────────────────────────────────────────────────────
                } else {
                    $expByCategory = \App\Models\Expense::selectRaw(
                            "COALESCE(category, 'Lainnya') as expense_category,
                             SUM(amount) as total, COUNT(id) as count"
                        )
                        ->when($selectedBranch, fn($q) => $q->where('branch', $selectedBranch))
                        ->whereBetween('created_at', [$start, $end])
                        ->groupBy('expense_category')
                        ->orderByDesc('total')
                        ->get();

                    $compExpByCategory = \App\Models\Expense::selectRaw(
                            "COALESCE(category, 'Lainnya') as expense_category, SUM(amount) as total"
                        )
                        ->when($selectedBranch, fn($q) => $q->where('branch', $selectedBranch))
                        ->whereBetween('created_at', [$comparisonStart, $comparisonEnd])
                        ->groupBy('expense_category')
                        ->pluck('total', 'expense_category');

                    $totalRevenue = $baseQuery()
                        ->selectRaw('SUM(total_price) as omset')
                        ->whereBetween('created_at', [$start, $end])
                        ->value('omset') ?? 0;

                    $expenseStr   = '';
                    $totalExpense = 0;
                    foreach ($expByCategory as $exp) {
                        $prev      = $compExpByCategory[$exp->expense_category] ?? 0;
                        $growth    = $prev > 0 ? round((($exp->total - $prev) / $prev) * 100, 1) : ($exp->total > 0 ? 100 : 0);
                        $pct       = $totalRevenue > 0 ? round(($exp->total / $totalRevenue) * 100, 1) : 0;
                        $totalExpense += $exp->total;
                        $growthLabel = $growth >= 0 ? "+{$growth}%" : "{$growth}%";
                        $expenseStr .= "- **{$exp->expense_category}**: Rp " . number_format($exp->total, 0, ',', '.')
                            . " ({$exp->count}x transaksi | {$pct}% dari omset | {$growthLabel} {$comparisonLabel})\n";
                    }
                    if (empty(trim($expenseStr))) {
                        $expenseStr = "Tidak ada data pengeluaran untuk periode ini.\n";
                    }
                    $expenseRatio = $totalRevenue > 0 ? round(($totalExpense / $totalRevenue) * 100, 1) : 0;

                    $geminiPrompt = "Anda adalah CFO / Konsultan Keuangan profesional untuk Pusat Kurma.\n"
                        . "Analisis detail pengeluaran operasional periode {$titleLabel}:\n"
                        . "--- BREAKDOWN PENGELUARAN PER KATEGORI ---\n{$expenseStr}"
                        . "- Total Pengeluaran: Rp " . number_format($totalExpense, 0, ',', '.') . "\n"
                        . "- Total Omset: Rp " . number_format($totalRevenue, 0, ',', '.') . "\n"
                        . "- Rasio Pengeluaran/Omset: {$expenseRatio}%\n"
                        . "- {$branchText}\n"
                        . "-------------------------------------------\n\n"
                        . "Buat laporan analisis efisiensi pengeluaran dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Analisis Komposisi Pengeluaran**: Kategori terbesar dan kewajaran rasio terhadap omset.\n"
                        . "2. **Pengeluaran Abnormal atau Bermasalah**: Kategori dengan pertumbuhan tidak wajar atau rasio terlalu tinggi.\n"
                        . "3. **Peluang Efisiensi**: 3-5 langkah konkret memangkas pengeluaran tanpa mengorbankan operasional.\n"
                        . "4. **Target Rasio Pengeluaran Ideal**: Benchmark rasio sehat untuk toko retail sejenis + cara mencapainya.\n\n"
                        . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan format markdown bersih dan profesional. Bersikap seperti CFO yang kritis dan detail-oriented. Mulai langsung dengan judul Markdown.";
                }

                // ── Call Gemini API with Model Fallback ─────────────────────
                $geminiPayload = [
                    'contents' => [[
                        'parts' => [['text' => $geminiPrompt]],
                    ]],
                    'generationConfig' => [
                        'temperature'     => 0.4,
                        'maxOutputTokens' => 4000,
                    ],
                ];

                $candidateModels = array_values(array_unique(array_filter([
                    $geminiModel,
                    'gemini-2.5-flash',
                    'gemini-3.8-flash',
                ])));

                $geminiResponse = null;
                $geminiHttpCode = 0;
                $lastErrMsg     = '';

                foreach ($candidateModels as $currentModel) {
                    $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$geminiKey}";

                    $ch = curl_init($geminiUrl);
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => json_encode($geminiPayload),
                        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                        CURLOPT_TIMEOUT        => 30,
                        CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_SSL_VERIFYHOST => false,
                    ]);
                    $geminiResponse = curl_exec($ch);
                    $geminiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlErr        = curl_error($ch);
                    curl_close($ch);

                    if ($geminiHttpCode === 200 && $geminiResponse) {
                        break;
                    }

                    $lastErrMsg = $geminiResponse
                        ? (json_decode($geminiResponse, true)['error']['message'] ?? $geminiResponse)
                        : ($curlErr ?: "Koneksi ke model {$currentModel} gagal.");

                    if ($geminiHttpCode === 404 || $geminiHttpCode === 0) {
                        \Illuminate\Support\Facades\Log::warning("[GeminiAI] Model {$currentModel} HTTP {$geminiHttpCode} ({$lastErrMsg}). Mencoba fallback...");
                        continue;
                    }

                    break;
                }

                if ($geminiHttpCode !== 200 || !$geminiResponse) {
                    throw new \Exception("Gemini API error (HTTP {$geminiHttpCode}): " . $lastErrMsg);
                }

                $geminiData = json_decode($geminiResponse, true);
                $text       = trim($geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '');

                if (empty($text)) {
                    throw new \Exception('Gemini mengembalikan respons kosong.');
                }

                return $text;
            });

            return response()->json([
                'success'  => true,
                'analysis' => $analysis,
                'cached'   => !$forceRefresh,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Performance Analysis failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghasilkan analisis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Multi-turn chat follow-up with Gemini AI based on the initial analysis.
     */
    public function performanceChat(Request $request)
    {
        $request->validate([
            'initial_analysis' => 'required|string|max:20000',
            'history'          => 'nullable|array|max:20',
            'history.*.role'   => 'required_with:history|in:user,model',
            'history.*.text'   => 'required_with:history|string|max:4000',
            'message'          => 'required|string|max:1000',
        ]);

        try {
            $initialAnalysis = $request->input('initial_analysis');
            $history         = $request->input('history', []);
            $newMessage      = trim($request->input('message'));

            // Limit history to last 10 messages (5 turns) to avoid excessive token usage
            $history = array_slice($history, -10);

            $geminiKey   = config('services.gemini.key');
            $geminiModel = config('services.gemini.model', 'gemini-2.5-flash');

            if (empty($geminiKey)) {
                throw new \Exception('GEMINI_API_KEY belum dikonfigurasi. Silakan isi GEMINI_API_KEY pada file .env Anda.');
            }

            // System context: the initial analysis report is the first user turn,
            // followed by any existing conversation history, then the new message.
            $systemContext = "Anda adalah Konsultan Bisnis Profesional untuk toko retail Pusat Kurma.\n"
                . "Berikut adalah laporan analisis kinerja bisnis yang telah Anda buat sebelumnya:\n\n"
                . "--- LAPORAN ANALISIS ---\n"
                . $initialAnalysis
                . "\n--- AKHIR LAPORAN ---\n\n"
                . "Berdasarkan laporan di atas, jawablah pertanyaan atau tanggapan dari pemilik toko berikut ini dalam bahasa Indonesia. "
                . "PENTING: Jangan gunakan emoji apapun dalam respons. Gunakan teks profesional dan format Markdown jika perlu. Jawab secara langsung, padat, dan profesional.";

            // Build multi-turn contents array for Gemini API
            $contents = [];

            // Turn 1: The system context as the first user message
            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => $systemContext]],
            ];

            // Turn 1 model acknowledgement (required for multi-turn to work)
            $contents[] = [
                'role'  => 'model',
                'parts' => [['text' => 'Baik, saya telah membaca laporan analisis bisnis tersebut. Silakan tanyakan apa yang ingin Anda ketahui lebih lanjut.']],
            ];

            // Append prior conversation history
            foreach ($history as $turn) {
                $contents[] = [
                    'role'  => $turn['role'],
                    'parts' => [['text' => $turn['text']]],
                ];
            }

            // Append the new user message
            $contents[] = [
                'role'  => 'user',
                'parts' => [['text' => $newMessage]],
            ];

            $geminiPayload = [
                'contents'         => $contents,
                'generationConfig' => [
                    'temperature'     => 0.5,
                    'maxOutputTokens' => 2000,
                ],
            ];

            $candidateModels = array_values(array_unique(array_filter([
                $geminiModel,
                'gemini-2.5-flash',
                'gemini-3.8-flash',
            ])));

            $geminiResponse = null;
            $geminiHttpCode = 0;
            $lastErrMsg     = '';

            foreach ($candidateModels as $currentModel) {
                $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$currentModel}:generateContent?key={$geminiKey}";

                $ch = curl_init($geminiUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode($geminiPayload),
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT        => 30,
                    CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                    CURLOPT_SSL_VERIFYPEER => false,
                    CURLOPT_SSL_VERIFYHOST => false,
                ]);
                $geminiResponse = curl_exec($ch);
                $geminiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $curlErr        = curl_error($ch);
                curl_close($ch);

                if ($geminiHttpCode === 200 && $geminiResponse) {
                    break;
                }

                $lastErrMsg = $geminiResponse
                    ? (json_decode($geminiResponse, true)['error']['message'] ?? $geminiResponse)
                    : ($curlErr ?: "Koneksi ke model {$currentModel} gagal.");

                if ($geminiHttpCode === 404 || $geminiHttpCode === 0) {
                    \Illuminate\Support\Facades\Log::warning("[GeminiAI Chat] Model {$currentModel} HTTP {$geminiHttpCode} ({$lastErrMsg}). Mencoba fallback...");
                    continue;
                }

                break;
            }

            if ($geminiHttpCode !== 200 || !$geminiResponse) {
                throw new \Exception("Gemini API error (HTTP {$geminiHttpCode}): " . $lastErrMsg);
            }

            $geminiData = json_decode($geminiResponse, true);
            $reply = trim($geminiData['candidates'][0]['content']['parts'][0]['text'] ?? '');

            if (empty($reply)) {
                throw new \Exception('Gemini mengembalikan respons kosong.');
            }

            return response()->json([
                'success' => true,
                'reply'   => $reply,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Performance Chat failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendapatkan balasan AI: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export the generated AI analysis report to a beautifully formatted PDF.
     */
    public function exportAiAnalysisPdf(Request $request)
    {
        @ini_set('memory_limit', '512M');
        @set_time_limit(180);
        try {
            $dateRanges = $this->resolveDateRanges($request);
            $filterType = $dateRanges['filterType'];
            $start = $dateRanges['start'];
            $end = $dateRanges['end'];
            $titleLabel = $dateRanges['titleLabel'];

            $selectedBranch = $request->query('branch', '');
            $selectedChannel = $this->normalizeChannel($request->query('channel'));
            $channelLabel = $this->channelLabel($selectedChannel);
            $skill = $request->query('skill', 'general');

            // Find cache key
            $cacheKey = 'owner_ai_analysis_' . md5(json_encode([
                'filter_type' => $filterType,
                'start'       => $start->toDateTimeString(),
                'end'         => $end->toDateTimeString(),
                'branch'      => $selectedBranch,
                'channel'     => $selectedChannel,
                'skill'       => $skill,
            ]));

            // Retrieve analysis from cache
            $analysis = Cache::get($cacheKey);

            if (!$analysis) {
                // If not cached, trigger generation
                $resp = $this->getPerformanceAnalysis($request);
                $analysis = json_decode($resp->getContent(), true)['analysis'] ?? null;
            }

            if (!$analysis) {
                return response('Gagal memuat analisis AI dari cache/generasi.', 404);
            }

            // Map skill names for formal PDF title
            $skillTitles = [
                'general' => 'ANALISIS KINERJA BISNIS UMUM',
                'forecast' => 'ANALISIS PREDIKSI / FORECAST PENJUALAN',
                'branch_comparison' => 'ANALISIS PERBANDINGAN ANTAR CABANG',
                'restock' => 'SARAN RESTOK CERDAS INVENTORI',
                'slow_moving' => 'ANALISIS RETENSI & PRODUK SLOW-MOVING',
                'expense_analysis' => 'ANALISIS EFISIENSI BIAYA & PENGELUARAN',
                'peak_hours' => 'ANALISIS POLA WAKTU TERLARIS & STAFFING',
                'product_bundling' => 'ANALISIS MARKET BASKET & REKOMENDASI BUNDLING',
            ];
            $pdfTitle = $skillTitles[$skill] ?? 'LAPORAN ANALISIS BISNIS AI';

            $now = \Carbon\Carbon::now();
            $printDate = $now->translatedFormat('d F Y - H:i');
            $printedBy = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';

            // Convert markdown style format to HTML
            $analysisHtml = $this->parseMarkdownToHtml($analysis);

            // Create logo html (use base64 if logo exists and GD is enabled, else use standard box)
            $logoPath = public_path('images/logo.png');
            $logoHtml = '';
            if (file_exists($logoPath) && extension_loaded('gd')) {
                try {
                    $logoData = base64_encode(file_get_contents($logoPath));
                    $logoHtml = '<img src="data:image/png;base64,' . $logoData . '" style="height: 50px; max-width: 100px; display: block;">';
                } catch (\Throwable $e) {
                    $logoHtml = '<div style="width:50px;height:50px;background-color:#059669;border-radius:8px;text-align:center;line-height:50px;font-size:16pt;font-weight:bold;color:#ffffff;display:inline-block;">PK</div>';
                }
            } else {
                $logoHtml = '<div style="width:50px;height:50px;background-color:#059669;border-radius:8px;text-align:center;line-height:50px;font-size:16pt;font-weight:bold;color:#ffffff;display:inline-block;">PK</div>';
            }

            // Create pdf html template
            $html = '
            <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
                <style>
                    body { font-family: "Segoe UI", Arial, sans-serif; color: #1f2937; line-height: 1.6; font-size: 10pt; }
                    .header-table { width: 100%; border: none !important; margin-bottom: 20px; }
                    .header-logo { width: 60px; border: none !important; text-align: left; vertical-align: middle; }
                    .header-title-box { border: none !important; text-align: left; vertical-align: middle; padding-left: 10px; }
                    .header-meta { border: none !important; text-align: right; vertical-align: middle; }
                    .divider { border-top: 2px solid #059669; border-bottom: 1px solid #059669; height: 2px; margin-bottom: 20px; }
                    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                    .meta-table td { border: 1px solid #e5e7eb; padding: 8px 12px; }
                    .meta-label { font-weight: bold; background-color: #f9fafb; width: 150px; color: #374151; }
                    .content-box { background-color: #ffffff; padding: 5px; }
                    h3, h4, h5 { color: #064e3b; margin-top: 20px; margin-bottom: 10px; font-weight: bold; }
                    p { margin-bottom: 12px; color: #374151; font-weight: 500; }
                    li { margin-bottom: 6px; color: #374151; font-weight: 500; }
                    strong { color: #111827; font-weight: bold; }
                    .footer-sig { width: 100%; margin-top: 50px; border: none !important; page-break-inside: avoid; }
                    .footer-sig td { border: none !important; text-align: center; width: 50%; }
                </style>
            </head>
            <body>
                <table class="header-table">
                    <tr>
                        <td class="header-logo">' . $logoHtml . '</td>
                        <td class="header-title-box">
                            <div style="font-size: 14pt; font-weight: bold; color: #064e3b;">PUSAT KURMA PREMIUM</div>
                            <div style="font-size: 8.5pt; color: #4b5563; margin-top: 1px;">Jl. Raya Cianjur - Bandung No. 12, Cianjur, Jawa Barat</div>
                        </td>
                        <td class="header-meta">
                            <div style="font-size: 10pt; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 0.5px;">' . htmlspecialchars($pdfTitle) . '</div>
                            <div style="font-size: 7.5pt; color: #6b7280;">Laporan Analisis Sistem Cerdas AI</div>
                        </td>
                    </tr>
                </table>
                <div class="divider"></div>

                <table class="meta-table">
                    <tr>
                        <td class="meta-label">Cabang</td>
                        <td>' . htmlspecialchars($selectedBranch ? $selectedBranch : 'Semua Cabang') . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Saluran Penjualan</td>
                        <td>' . htmlspecialchars($channelLabel) . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Periode Data</td>
                        <td>' . htmlspecialchars($titleLabel) . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Tanggal Unduh</td>
                        <td>' . htmlspecialchars($printDate) . '</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Diunduh Oleh</td>
                        <td>' . htmlspecialchars($printedBy) . '</td>
                    </tr>
                </table>

                <div class="content-box">
                    ' . $analysisHtml . '
                </div>

                <table class="footer-sig">
                    <tr>
                        <td>
                            <p style="margin-bottom: 40px; color: #4b5563;">Dibuat Oleh,</p>
                            <p style="font-weight: bold; text-decoration: underline; color: #111827;">' . htmlspecialchars($printedBy) . '</p>
                            <p style="font-size: 8pt; color: #6b7280; margin-top: 2px;">Sistem Analisis AI Pusat Kurma</p>
                        </td>
                        <td>
                            <p style="margin-bottom: 40px; color: #4b5563;">Disetujui Oleh,</p>
                            <p style="font-weight: bold; text-decoration: underline; color: #111827;">....................................</p>
                            <p style="font-size: 8pt; color: #6b7280; margin-top: 2px;">Manager / Owner</p>
                        </td>
                    </tr>
                </table>
            </body>
            </html>
            ';

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            $filename = 'Laporan_Analisis_AI_' . ucfirst($skill) . '_' . $now->format('YmdHis') . '.pdf';
            return $pdf->download($filename);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('AI Analysis PDF export failed: ' . $e->getMessage());
            return response('Gagal mengekspor PDF: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Helper to convert basic Markdown to styled HTML tags.
     */
    private function parseMarkdownToHtml($md)
    {
        if (!$md) return '';
        
        $html = htmlspecialchars($md);
        
        // Convert headings
        $html = preg_replace('/^### (.*?)$/m', '<h5 style="font-size: 11pt; color: #064e3b; margin-top: 15px; margin-bottom: 5px; font-weight: bold;">$1</h5>', $html);
        $html = preg_replace('/^## (.*?)$/m', '<h4 style="font-size: 12pt; color: #064e3b; margin-top: 18px; margin-bottom: 8px; font-weight: bold; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px;">$1</h4>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h3 style="font-size: 14pt; color: #064e3b; margin-top: 22px; margin-bottom: 10px; font-weight: bold;">$1</h3>', $html);
        
        // Convert bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        
        // Convert bullet points
        $html = preg_replace('/^\-\s+(.*?)$/m', '<li style="margin-left: 15px; font-size: 9.5pt; color: #374151;">$1</li>', $html);
        
        // Convert paragraphs
        $paragraphs = explode("\n\n", $html);
        foreach ($paragraphs as &$p) {
            $p = trim($p);
            if (empty($p)) continue;
            if (strpos($p, '<h') === 0 || strpos($p, '<li') === 0) {
                continue;
            }
            $p = '<p style="margin-bottom: 10px; font-size: 9.5pt; color: #374151;">' . $p . '</p>';
        }
        $html = implode("\n", $paragraphs);
        
        return $html;
    }
}
