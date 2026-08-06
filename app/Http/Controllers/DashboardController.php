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

        // Ambil riwayat nota partai terbaru
        $wholesaleTransactions = \App\Models\Transaction::where('transaction_type', 'wholesale')
            ->with('cashier')
            ->latest()
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('products', 'cashiers', 'categories', 'settings', 'wholesaleTransactions'));
    }

    public function kasir(Request $request)
    {
        $category = $request->input('category');
        $kasir    = auth()->user();
        $myLocation = \App\Models\StockLocation::findByBranchName($kasir->branch ?? '');
        $locationId = $myLocation ? $myLocation->id : null;

        $query = Product::where('is_active', true)->with(['productStocks', 'bundleItems.product.productStocks']);

        // Proteksi Strict Mode Database cPanel:
        // Jika parameter kategori diisi dan nilainya bukan "Semua"/"all"/"empty", lakukan filter WHERE
        if ($category && !in_array(strtolower(trim($category)), ['semua', 'all', ''])) {
            $query->where('category', $category);
        }

        // Ambil data produk secara standar dari database
        $productsFromDb = $query->get();

        $products = $productsFromDb->map(function ($product) use ($locationId) {
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
                            return floor($compStock / $item->quantity);
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
        });


        // Fetch transactions created today matching the logged-in cashier's ID
        $todayTransactions = \App\Models\Transaction::where('cashier_id', auth()->id())
            ->whereDate('created_at', \Carbon\Carbon::today())
            ->latest()
            ->get();

        $todayTransactionsMapped = $todayTransactions->map(function($trx) {
            return [
                'id' => $trx->id,
                'time' => $trx->created_at->format('H:i'),
                'transaction_code' => $trx->transaction_code,
                'items_summary' => $trx->items_summary,
                'payment_method' => $trx->payment_method,
                'total_price' => $trx->total_price,
            ];
        });

        // Fetch expenses created today by the logged-in cashier
        $todayExpenses = \App\Models\Expense::where('cashier_id', auth()->id())
            ->whereDate('created_at', \Carbon\Carbon::today())
            ->latest()
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

        // Cache kategori 30 menit — data jarang berubah
        $categories = Cache::remember('categories_for_kasir', 1800,
            fn() => \App\Models\Category::select('id', 'name')->get()
        );

        return view('kasir.dashboard', compact('products', 'todayTransactionsMapped', 'todayExpensesMapped', 'categories'));
    }

    /**
     * Display the Owner Dashboard.
     */
    public function owner(Request $request)
    {
        // 1. Fetch stock alerts — 1 query (ambil sekaligus, count dari collection)
        $lowStockProducts = Product::where('stock', '<=', 10)->get();
        $lowStockCount    = $lowStockProducts->count();

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

        // Resolve selected branch filter
        $selectedBranch = $request->query('branch');

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

        // Helper: base query with optional branch scope
        $baseQuery = fn() => ($selectedBranch && $selectedBranch !== '')
            ? \App\Models\Transaction::where('branch', $selectedBranch)
            : \App\Models\Transaction::query();

        $expenseQuery = fn() => ($selectedBranch && $selectedBranch !== '')
            ? \App\Models\Expense::where('branch', $selectedBranch)
            : \App\Models\Expense::query();

        // 3. Fetch dynamically filtered metrics and their comparative historical data
        $activeStats = $baseQuery()
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
            ->whereBetween('created_at', [$start, $end])
            ->first();

        $comparisonStats = $baseQuery()
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
            ->whereBetween('created_at', [$comparisonStart, $comparisonEnd])
            ->first();

        $activeExpenses = $expenseQuery()
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $comparisonExpenses = $expenseQuery()
            ->whereBetween('created_at', [$comparisonStart, $comparisonEnd])
            ->sum('amount');

        $activeRevenue = $activeStats->omset ?? 0;
        $activeProfit = ($activeStats->profit ?? 0) - $activeExpenses;
        $activeTransactionsCount = $activeStats->count ?? 0;

        $comparisonRevenue = $comparisonStats->omset ?? 0;
        $comparisonProfit = ($comparisonStats->profit ?? 0) - $comparisonExpenses;
        $comparisonTransactionsCount = $comparisonStats->count ?? 0;

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

            $weeklyTrxs = $baseQuery()->whereBetween('created_at', [$startOfWeek, $endOfWeek])->get();
            foreach ($weeklyTrxs as $trx) {
                $dateStr = $trx->created_at->format('Y-m-d');
                if (isset($days[$dateStr])) {
                    $days[$dateStr]['omset'] += $trx->total_price;
                    $days[$dateStr]['profit'] += ($trx->total_price - $trx->total_cost);
                    $days[$dateStr]['count']++;
                }
            }
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

            $monthlyTrxs = $baseQuery()->whereBetween('created_at', [$startOfMonth, $endOfMonth])->get();
            foreach ($monthlyTrxs as $trx) {
                $day = $trx->created_at->day;
                foreach ($weeksBreakdown as $wNum => &$w) {
                    if ($day >= $w['start'] && $day <= $w['end']) {
                        $w['omset'] += $trx->total_price;
                        $w['profit'] += ($trx->total_price - $trx->total_cost);
                        $w['count']++;
                        break;
                    }
                }
            }
            unset($w);
            $breakdownData = array_values($weeksBreakdown);
        }

        // 5. Fetch weekly omset breakdown for the general trend graph
        $days = [];
        for ($d = $startOfWeek->copy(); $d->lte($endOfWeek); $d->addDay()) {
            $days[$d->format('Y-m-d')] = [
                'day_name' => $d->translatedFormat('D'),
                'full_day_name' => $d->translatedFormat('l'),
                'date' => $d->format('d M'),
                'omset' => 0,
                'is_today' => $d->isToday(),
            ];
        }

        $weeklyOmsetDetail = $baseQuery()
            ->selectRaw('DATE(created_at) as date, SUM(total_price) as total_omset')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->groupBy('date')
            ->get();

        foreach ($weeklyOmsetDetail as $stat) {
            if (isset($days[$stat->date])) {
                $days[$stat->date]['omset'] = (int)$stat->total_omset;
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
            1 => ['label' => 'Minggu 1', 'full_name' => 'Minggu Pertama', 'date' => '01-07', 'omset' => 0, 'is_today' => false],
            2 => ['label' => 'Minggu 2', 'full_name' => 'Minggu Kedua', 'date' => '08-14', 'omset' => 0, 'is_today' => false],
            3 => ['label' => 'Minggu 3', 'full_name' => 'Minggu Ketiga', 'date' => '15-21', 'omset' => 0, 'is_today' => false],
            4 => ['label' => 'Minggu 4', 'full_name' => 'Minggu Keempat', 'date' => '22-28', 'omset' => 0, 'is_today' => false],
        ];
        if ($daysInMonth > 28) {
            $weeks[5] = ['label' => 'Minggu 5', 'full_name' => 'Minggu Kelima', 'date' => '29-' . $daysInMonth, 'omset' => 0, 'is_today' => false];
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
            ->selectRaw('DAY(created_at) as day, SUM(total_price) as total_omset')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('day')
            ->get();

        foreach ($monthlyOmsetDetail as $stat) {
            $day = (int)$stat->day;
            foreach ($weeks as $wNum => &$w) {
                $wStart = ($wNum - 1) * 7 + 1;
                $wEnd = ($wNum === 5) ? $daysInMonth : $wNum * 7;
                if ($day >= $wStart && $day <= $wEnd) {
                    $w['omset'] += (int)$stat->total_omset;
                    break;
                }
            }
        }
        unset($w);

        $maxMonthlyOmset = collect($weeks)->max('omset');
        foreach ($weeks as &$w) {
            $w['height_percent'] = $maxMonthlyOmset > 0 ? round(($w['omset'] / $maxMonthlyOmset) * 100) : 0;
        }
        unset($w);

        $monthlyTrend = array_values($weeks);

        // 7. Fetch transactions untuk best sellers — gunakan 1 query (monthly), filter di memory
        $bestSellerDay = ($filterType === 'harian') ? $dateObj : $start;

        // Ambil semua transaksi bulan ini dalam 1 query, lalu filter di PHP
        $allMonthlyForBestSeller = $baseQuery()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();

        $todayTransactionsForBestSeller   = $allMonthlyForBestSeller->filter(
            fn($t) => $t->created_at->between(
                $bestSellerDay->copy()->startOfDay(),
                $bestSellerDay->copy()->endOfDay()
            )
        );
        $weeklyTransactionsForBestSeller  = $allMonthlyForBestSeller->filter(
            fn($t) => $t->created_at->between($startOfWeek, $endOfWeek)
        );
        $monthlyTransactionsForBestSeller = $allMonthlyForBestSeller;

        $bestSellersToday = $this->getBestSellers($todayTransactionsForBestSeller, 5);
        $bestSellersWeekly = $this->getBestSellers($weeklyTransactionsForBestSeller, 5);
        $bestSellersMonthly = $this->getBestSellers($monthlyTransactionsForBestSeller, 5);

        return view('owner.dashboard', compact(
            'lowStockCount',
            'lowStockProducts',
            'filterType',
            'selectedDate',
            'selectedWeek',
            'selectedMonth',
            'selectedBranch',
            'branches',
            'activeRevenue',
            'activeProfit',
            'activeTransactionsCount',
            'revenueGrowthPercent',
            'profitGrowthPercent',
            'transactionGrowthPercent',
            'weeklyTrend',
            'monthlyTrend',
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
            'endOfMonth'
        ));
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
        } catch (\Exception $e) {
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

            $startOfWeek = $start->copy()->startOfWeek();
            $endOfWeek = $start->copy()->endOfWeek();
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
                if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z]+)\)$/', trim($part), $matches)) {
                    $name = trim($matches[1]);
                    $qty = floatval($matches[2]);
                    $unit = strtolower(trim($matches[3]));
                } else {
                    $name = trim($part);
                    $qty = 1.0;
                    $unit = 'pcs';
                }

                // Normalize weight unit: gram -> kg
                if ($unit === 'gram' || $unit === 'g') {
                    $qty = $qty / 1000;
                    $unit = 'kg';
                }

                if (!isset($bestSellers[$name])) {
                    $bestSellers[$name] = [
                        'name' => $name,
                        'qty' => 0.0,
                        'unit' => $unit,
                        'count' => 0
                    ];
                }
                
                $bestSellers[$name]['qty'] += $qty;
                
                if (!isset($seenInThisTransaction[$name])) {
                    $bestSellers[$name]['count'] += 1;
                    $seenInThisTransaction[$name] = true;
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

        $sliced = array_slice($bestSellers, 0, $limit);
        
        // Enrich with product image and category if exists
        $names = array_keys($sliced);
        $products = \App\Models\Product::whereIn('name', $names)->get()->keyBy('name');
        
        foreach ($sliced as &$item) {
            $product = $products->get($item['name']);
            $item['image_path'] = $product ? $product->image_path : null;
            $item['category'] = $product ? $product->category : null;
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
        $branchSuffix = $activeBranch ? '_' . str_replace(' ', '_', $activeBranch) : '';

        // Helper: base query with optional branch scope
        $baseQuery = fn() => ($activeBranch && $activeBranch !== '')
            ? \App\Models\Transaction::where('branch', $activeBranch)
            : \App\Models\Transaction::query();

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

        // Pre-calculate stats for cards
        $totalItems = count($bestSellers);
        $totalQty = 0;
        foreach ($bestSellers as $item) {
            $totalQty += $item['qty'];
        }

        $renderTemplate = function () use ($bestSellers, $activeBranch, $periodLabel, $printDate, $printedBy, $isPdf, $totalItems, $totalQty) {
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
            ob_start();
            $renderTemplate();
            $html = ob_get_clean();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'portrait');
            return $pdf->download($filePrefix . $branchSuffix . '_' . $now->format('Y-m-d') . '.pdf');
        }

        $filename = $filePrefix . $branchSuffix . '_' . $now->format('Y-m-d') . '.xls';
        return response()->streamDownload($renderTemplate, $filename, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Export dynamic financial report as Excel-compatible file.
     */
    public function exportOwnerReport(Request $request)
    {
        $activeBranch = $request->query('branch', '');
        $branchSuffix = $activeBranch ? '_' . str_replace(' ', '_', $activeBranch) : '';

        $now = \Carbon\Carbon::now();
        $printDate = $now->translatedFormat('d F Y - H:i');
        $printedBy = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';
        $isPdf = $request->query('format') === 'pdf';

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

        $baseQuery = fn() => ($activeBranch && $activeBranch !== '')
            ? \App\Models\Transaction::where('branch', $activeBranch)
            : \App\Models\Transaction::query();

        if ($filterType === 'harian') {
            $label = 'Harian';
            $filename = $filePrefix . $branchSuffix . '_' . $now->format('Y-m-d') . '.xls';
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

            $renderTemplate = function () use ($todayTransactions, $activeBranch, $printDate, $printedBy, $now, $start, $label, $isPdf, $totalTransactions, $totalOmset, $totalProfit, $avgOmset) {
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
                ob_start();
                $renderTemplate();
                $html = ob_get_clean();

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'landscape');
                return $pdf->download(str_replace('.xls', '.pdf', $filename));
            }

            return response()->streamDownload($renderTemplate, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } elseif ($filterType === 'mingguan') {
            $filename = $filePrefix . $branchSuffix . '_' . $now->format('Y-m-d') . '.xls';
            
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

            $query = \App\Models\Transaction::query();
            if ($activeBranch) {
                $query->where('branch', $activeBranch);
            }

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

            $renderTemplate = function () use ($days, $startOfWeek, $endOfWeek, $activeBranch, $printDate, $printedBy, $isPdf, $totalOmset, $totalProfit, $totalCount) {
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
                ob_start();
                $renderTemplate();
                $html = ob_get_clean();

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'portrait');
                return $pdf->download(str_replace('.xls', '.pdf', $filename));
            }

            return response()->streamDownload($renderTemplate, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } else { // bulanan
            $filename = $filePrefix . $branchSuffix . '_' . $now->format('Y-m-d') . '.xls';
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

            $query = \App\Models\Transaction::query();
            if ($activeBranch) {
                $query->where('branch', $activeBranch);
            }

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

            $renderTemplate = function () use ($weeksBreakdown, $startOfMonth, $activeBranch, $printDate, $printedBy, $isPdf, $totalOmset, $totalProfit, $totalCount) {
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
                ob_start();
                $renderTemplate();
                $html = ob_get_clean();

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'portrait');
                return $pdf->download(str_replace('.xls', '.pdf', $filename));
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
            } catch (\Exception $e) {
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

            // Cache key includes skill so each mode has its own cache entry
            $cacheKey = 'owner_ai_analysis_' . md5(json_encode([
                'filter_type' => $filterType,
                'start'       => $start->toDateTimeString(),
                'end'         => $end->toDateTimeString(),
                'branch'      => $selectedBranch,
                'skill'       => $skill,
            ]));

            $forceRefresh = filter_var($request->query('refresh'), FILTER_VALIDATE_BOOLEAN);

            if ($forceRefresh) {
                Cache::forget($cacheKey);
            }

            $analysis = Cache::remember($cacheKey, 3600, function () use (
                $start, $end, $comparisonStart, $comparisonEnd, $selectedBranch, $filterType, $titleLabel, $comparisonLabel, $skill
            ) {
                $baseQuery = fn() => ($selectedBranch && $selectedBranch !== '')
                    ? \App\Models\Transaction::where('branch', $selectedBranch)
                    : \App\Models\Transaction::query();

                $expenseQuery = fn() => ($selectedBranch && $selectedBranch !== '')
                    ? \App\Models\Expense::where('branch', $selectedBranch)
                    : \App\Models\Expense::query();

                $activeStats = $baseQuery()
                    ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                    ->whereBetween('created_at', [$start, $end])
                    ->first();

                $comparisonStats = $baseQuery()
                    ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                    ->whereBetween('created_at', [$comparisonStart, $comparisonEnd])
                    ->first();

                $activeExpenses = $expenseQuery()
                    ->whereBetween('created_at', [$start, $end])
                    ->sum('amount');

                $comparisonExpenses = $expenseQuery()
                    ->whereBetween('created_at', [$comparisonStart, $comparisonEnd])
                    ->sum('amount');

                $activeRevenue = $activeStats->omset ?? 0;
                $activeProfit = ($activeStats->profit ?? 0) - $activeExpenses;
                $activeTransactionsCount = $activeStats->count ?? 0;

                $comparisonRevenue = $comparisonStats->omset ?? 0;
                $comparisonProfit = ($comparisonStats->profit ?? 0) - $comparisonExpenses;
                $comparisonTransactionsCount = $comparisonStats->count ?? 0;

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
                $branchText  = $selectedBranch ? "Cabang: {$selectedBranch}" : "Semua Cabang";
                $geminiKey   = config('services.gemini.key');
                $geminiModel = config('services.gemini.model', 'gemini-3.5-flash');

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
                        . "- Profit Bersih: Rp " . number_format($activeProfit, 0, ',', '.') . " (" . number_format($profitGrowthPercent, 1) . "% {$comparisonLabel})\n"
                        . "- Pengeluaran: Rp " . number_format($activeExpenses, 0, ',', '.') . " (" . number_format($expenseGrowthPercent, 1) . "% {$comparisonLabel})\n"
                        . "- Jumlah Transaksi: " . number_format($activeTransactionsCount) . " Trx\n"
                        . "- 5 Produk Terlaris:\n{$bestSellersStr}"
                        . "- Produk Stok Menipis (<=10):\n{$lowStockStr}\n"
                        . "--------------------\n\n"
                        . "Berikan analisis dalam bahasa Indonesia terformat Markdown. Gunakan struktur:\n"
                        . "1. **Ringkasan Eksekutif**: Tinjauan tajam kesehatan keuangan toko pada periode ini.\n"
                        . "2. **Analisis Mendalam & Tren**: Bahas pertumbuhan omset, profitabilitas, dan pengeluaran secara kritis.\n"
                        . "3. **Saran & Rekomendasi Bisnis**: Minimal 3 saran aksi konkret dan taktis.\n\n"
                        . "Tone profesional, optimis namun realistis. Mulai langsung dengan judul Markdown.";

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

                    $monthlyData = \App\Models\Transaction::selectRaw(
                            "DATE_FORMAT(created_at, '%Y-%m') as month_key,
                             DATE_FORMAT(created_at, '%M %Y') as month_label,
                             SUM(total_price) as omset,
                             SUM(total_price - total_cost) as gross_profit,
                             COUNT(id) as trx_count"
                        )
                        ->when($selectedBranch, fn($q) => $q->where('branch', $selectedBranch))
                        ->where('created_at', '>=', $sixMonthsAgo)
                        ->groupBy('month_key', 'month_label')
                        ->orderBy('month_key')
                        ->get();

                    $monthlyExpenses = \App\Models\Expense::selectRaw(
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
                        . "1. **📈 Analisis Tren Historis**: Identifikasi tren, pola musiman, dan konsistensi pertumbuhan.\n"
                        . "2. **🔮 Prediksi 3 Bulan ke Depan**: Estimasi omset & profit untuk 3 bulan mendatang (optimis vs konservatif).\n"
                        . "3. **⚠️ Risiko & Peluang**: Faktor risiko dan peluang yang harus diwaspadai.\n"
                        . "4. **✅ Saran Target & Strategi**: Target omset realistis bulan depan + 2-3 strategi konkret.\n\n"
                        . "Gunakan angka spesifik dari histori dan proyeksi. Mulai langsung dengan judul Markdown.";

                // ────────────────────────────────────────────────────────────
                // SKILL: branch_comparison — Perbandingan Antar Cabang
                // ────────────────────────────────────────────────────────────
                } elseif ($skill === 'branch_comparison') {
                    $branchStats = \App\Models\Transaction::selectRaw(
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

                    $branchExpenses = \App\Models\Expense::selectRaw(
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
                        . "1. **🏆 Ranking & Performa Cabang**: Ranking dari terbaik ke terburuk + kontribusi persentase.\n"
                        . "2. **📊 Analisis Gap & Kesenjangan**: Apa yang membuat cabang terbaik unggul?\n"
                        . "3. **💡 Rekomendasi per Cabang**: Saran spesifik untuk setiap cabang.\n"
                        . "4. **🎯 Strategi Pertumbuhan Jaringan**: Saran strategis untuk meningkatkan performa keseluruhan.\n\n"
                        . "Gunakan angka spesifik. Bersikap kritis namun konstruktif. Mulai langsung dengan judul Markdown.";

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
                        . "1. **🚨 Prioritas Restok Mendesak**: Produk HABIS/KRITIS yang harus direstok SEGERA + estimasi jumlah berdasarkan volume penjualan.\n"
                        . "2. **⚡ Restok Segera (Minggu Ini)**: Produk stok rendah yang perlu diisi dalam 1 minggu.\n"
                        . "3. **📦 Strategi Manajemen Stok**: Safety stock minimum per kategori + strategi pengadaan efisien.\n"
                        . "4. **💰 Estimasi Kebutuhan Modal Restok**: Perkiraan dana berdasarkan harga pokok produk.\n\n"
                        . "Saran sangat konkret, spesifik per produk. Mulai langsung dengan judul Markdown.";

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
                        . "1. **🐌 Identifikasi Produk Bermasalah**: Analisis mengapa produk-produk ini kurang laku.\n"
                        . "2. **💡 Strategi Promosi per Produk**: Saran spesifik per produk (bundling, diskon, positioning ulang, dll).\n"
                        . "3. **📦 Manajemen Stok Slow-Moving**: Cegah overstock, optimalkan modal tertahan.\n"
                        . "Saran promosi kreatif namun realistis. Mulai langsung dengan judul Markdown.";

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
                        . "1. **🕒 Identifikasi Pola Jam & Hari Puncak**: Analisis kapan waktu tersibuk toko Anda terjadi dan mengapa (pola makan, sepulang kerja, hari libur, dll).\n"
                        . "2. **👥 Rekomendasi Alokasi Staf / Kasir**: Berikan saran konkret mengenai penjadwalan kasir/staf toko agar optimal di jam sibuk dan efisien (hemat biaya) di jam sepi.\n"
                        . "3. **🎯 Strategi Promosi Berdasarkan Waktu**: Usulkan strategi promosi bertarget waktu (misal: happy hour discount, flash sale pada jam sepi untuk menarik pelanggan).\n\n"
                        . "Berikan analisis yang sangat taktis dan operasional. Mulai langsung dengan judul Markdown.";

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
                        . "1. **🛒 Analisis Asosiasi Produk**: Jelaskan hubungan logis mengapa pelanggan membeli produk-produk tersebut bersamaan.\n"
                        . "2. **🎯 Rekomendasi Paket Bundling Cerdas**: Usulkan minimal 3 paket bundling produk (kasih nama paket menarik) lengkap dengan skema harga promosi yang disarankan.\n"
                        . "3. **🏪 Rekomendasi Tata Letak Toko (Cross-Merchandising)**: Berikan saran penataan produk di rak toko/display agar produk yang berasosiasi mudah dijangkau pelanggan untuk mendorong dorongan beli (*impulse buying*).\n\n"
                        . "Berikan ide-ide promosi kreatif yang sangat menjual untuk retail kurma & produk oleh-oleh premium. Mulai langsung dengan judul Markdown.";

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
                        . "1. **💸 Analisis Komposisi Pengeluaran**: Kategori terbesar dan kewajaran rasio terhadap omset.\n"
                        . "2. **📊 Pengeluaran Abnormal atau Bermasalah**: Kategori dengan pertumbuhan tidak wajar atau rasio terlalu tinggi.\n"
                        . "3. **✂️ Peluang Efisiensi**: 3-5 langkah konkret memangkas pengeluaran tanpa mengorbankan operasional.\n"
                        . "4. **🎯 Target Rasio Pengeluaran Ideal**: Benchmark rasio sehat untuk toko retail sejenis + cara mencapainya.\n\n"
                        . "Bersikap seperti CFO yang kritis dan detail-oriented. Mulai langsung dengan judul Markdown.";
                }

                // ── Call Gemini API ──────────────────────────────────────────
                $geminiPayload = [
                    'contents' => [[
                        'parts' => [['text' => $geminiPrompt]],
                    ]],
                    'generationConfig' => [
                        'temperature'     => 0.4,
                        'maxOutputTokens' => 4000,
                    ],
                ];

                $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}";

                $ch = curl_init($geminiUrl);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST           => true,
                    CURLOPT_POSTFIELDS     => json_encode($geminiPayload),
                    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                    CURLOPT_TIMEOUT        => 30,
                ]);
                $geminiResponse = curl_exec($ch);
                $geminiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($geminiHttpCode !== 200 || !$geminiResponse) {
                    $errMsg = $geminiResponse
                        ? (json_decode($geminiResponse, true)['error']['message'] ?? $geminiResponse)
                        : 'Koneksi ke Gemini API gagal.';
                    throw new \Exception("Gemini API error (HTTP {$geminiHttpCode}): " . $errMsg);
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
        } catch (\Exception $e) {
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
            $geminiModel = config('services.gemini.model', 'gemini-3.5-flash');

            // System context: the initial analysis report is the first user turn,
            // followed by any existing conversation history, then the new message.
            $systemContext = "Anda adalah Konsultan Bisnis Profesional untuk toko retail Pusat Kurma.\n"
                . "Berikut adalah laporan analisis kinerja bisnis yang telah Anda buat sebelumnya:\n\n"
                . "--- LAPORAN ANALISIS ---\n"
                . $initialAnalysis
                . "\n--- AKHIR LAPORAN ---\n\n"
                . "Berdasarkan laporan di atas, jawablah pertanyaan atau tanggapan dari pemilik toko berikut ini dalam bahasa Indonesia. "
                . "Gunakan format Markdown jika perlu. Jawab secara langsung, padat, dan profesional.";

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

            $geminiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$geminiModel}:generateContent?key={$geminiKey}";

            $ch = curl_init($geminiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($geminiPayload),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 30,
            ]);
            $geminiResponse = curl_exec($ch);
            $geminiHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($geminiHttpCode !== 200 || !$geminiResponse) {
                $errMsg = $geminiResponse
                    ? (json_decode($geminiResponse, true)['error']['message'] ?? $geminiResponse)
                    : 'Koneksi ke Gemini API gagal.';
                throw new \Exception("Gemini API error (HTTP {$geminiHttpCode}): " . $errMsg);
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
        } catch (\Exception $e) {
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
        try {
            $dateRanges = $this->resolveDateRanges($request);
            $filterType = $dateRanges['filterType'];
            $start = $dateRanges['start'];
            $end = $dateRanges['end'];
            $titleLabel = $dateRanges['titleLabel'];

            $selectedBranch = $request->query('branch', '');
            $skill = $request->query('skill', 'general');

            // Find cache key
            $cacheKey = 'owner_ai_analysis_' . md5(json_encode([
                'filter_type' => $filterType,
                'start'       => $start->toDateTimeString(),
                'end'         => $end->toDateTimeString(),
                'branch'      => $selectedBranch,
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
                } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
