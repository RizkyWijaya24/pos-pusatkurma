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
        // Hanya select kolom yang dibutuhkan di Admin Dashboard (fix Product::all())
        $products = Product::select('id', 'sku', 'name', 'category', 'selling_price',
                                    'price_unit', 'image_path', 'stock')->get();

        $cashiers = User::where('role', 'kasir')->get()->map(function ($user) {
            return [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'branch'     => $user->branch ?? 'Pusat Cianjur',
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

        return view('admin.dashboard', compact('products', 'cashiers', 'categories'));
    }

    public function kasir(Request $request)
    {
        $category = $request->input('category');
        $kasir    = auth()->user();
        $myLocation = \App\Models\StockLocation::findByBranchName($kasir->branch ?? '');
        $locationId = $myLocation ? $myLocation->id : null;

        $query = Product::with(['productStocks']);

        // Proteksi Strict Mode Database cPanel:
        // Jika parameter kategori diisi dan nilainya bukan "Semua"/"all"/"empty", lakukan filter WHERE
        if ($category && !in_array(strtolower(trim($category)), ['semua', 'all', ''])) {
            $query->where('category', $category);
        }

        // Ambil data produk secara standar dari database
        $productsFromDb = $query->get();

        // STRICT SECURITY: Mapping dilakukan di level PHP untuk memastikan cost_price 
        // sama sekali tidak dikirimkan ke client-side/kasir view demi keamanan data modal.
        $products = $productsFromDb->map(function ($product) use ($locationId) {
            $stock = 0.0;
            if ($locationId) {
                $ps = $product->productStocks->firstWhere('location_id', $locationId);
                $stock = $ps ? (float) $ps->stock : 0.0;
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
    private function excelStyles(): string
    {
        return implode('', [
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
        $filename = $filePrefix . $branchSuffix . '_' . $now->format('Y-m-d') . '.xls';
        $printDate = $now->translatedFormat('d F Y - H:i');
        $printedBy = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';

        return response()->streamDownload(function () use ($bestSellers, $activeBranch, $periodLabel, $printDate, $printedBy) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<style>' . $this->excelStyles() . '</style></head><body>';

            // Metadata Table
            echo '<table style="margin-bottom:16px;">';
            echo '<tr><td colspan="6" class="title" style="height:45px;">LAPORAN PRODUK BEST SELLER (TERLARIS)</td></tr>';
            echo '<tr><td colspan="6" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
            echo '<tr><td colspan="6" class="spacer"></td></tr>';
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

            echo '</tbody></table></body></html>';
        }, $filename, [
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

            return response()->streamDownload(function () use ($todayTransactions, $activeBranch, $printDate, $printedBy, $now, $start, $label) {
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>' . $this->excelStyles() . '</style></head><body>';

                // Metadata
                echo '<table style="margin-bottom:16px;">';
                echo '<tr><td colspan="9" class="title" style="height:45px;">LAPORAN PENJUALAN HARIAN POS</td></tr>';
                echo '<tr><td colspan="9" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
                echo '<tr><td colspan="9" class="spacer"></td></tr>';
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

                $totalOmset  = 0;
                $totalProfit = 0;
                $idx = 1;

                foreach ($todayTransactions as $trx) {
                    $omset  = (int)$trx->total_price;
                    $profit = (int)($trx->total_price - $trx->total_cost);
                    $totalOmset  += $omset;
                    $totalProfit += $profit;
                    $rowClass = $idx % 2 === 0 ? ' class="stripe"' : '';

                    echo '<tr' . $rowClass . '>';
                    echo '<td class="center">' . $idx++ . '</td>';
                    echo '<td class="center">' . htmlspecialchars($trx->created_at->translatedFormat('H:i')) . '</td>';
                    echo '<td class="center bold text">' . htmlspecialchars($trx->transaction_code) . '</td>';
                    echo '<td>' . htmlspecialchars($trx->cashier->name ?? 'N/A') . '</td>';
                    echo '<td class="center">' . htmlspecialchars($trx->branch ?? 'Pusat Cianjur') . '</td>';
                    echo '<td class="text">' . htmlspecialchars($trx->items_summary) . '</td>';
                    echo '<td class="center">' . htmlspecialchars($trx->payment_method) . '</td>';
                    echo '<td class="currency">' . $this->rupiah($omset) . '</td>';
                    echo '<td class="currency">' . $this->rupiah($profit) . '</td>';
                    echo '</tr>';
                }

                $count     = count($todayTransactions);
                $avgOmset  = $count > 0 ? (int)round($totalOmset / $count) : 0;
                $avgProfit = $count > 0 ? (int)round($totalProfit / $count) : 0;

                echo '<tr><td colspan="9" class="spacer"></td></tr>';

                echo '<tr class="total-row">';
                echo '<td colspan="7" class="center bold" style="font-size:11pt;">GRAND TOTAL (' . $count . ' Transaksi)</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalOmset) . '</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalProfit) . '</td>';
                echo '</tr>';

                echo '<tr class="avg-row">';
                echo '<td colspan="7" class="center bold">RATA-RATA PER TRANSAKSI</td>';
                echo '<td class="currency">' . $this->rupiah($avgOmset) . '</td>';
                echo '<td class="currency">' . $this->rupiah($avgProfit) . '</td>';
                echo '</tr>';

                echo '</tbody></table></body></html>';
            }, $filename, [
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

            return response()->streamDownload(function () use ($days, $startOfWeek, $endOfWeek, $activeBranch, $printDate, $printedBy) {
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>' . $this->excelStyles() . '</style></head><body>';

                // Metadata
                echo '<table style="margin-bottom:16px;">';
                echo '<tr><td colspan="7" class="title" style="height:45px;">LAPORAN PENJUALAN MINGGUAN</td></tr>';
                echo '<tr><td colspan="7" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
                echo '<tr><td colspan="7" class="spacer"></td></tr>';
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

                $totalOmset  = 0;
                $totalProfit = 0;
                $totalCount  = 0;
                $idx = 1;

                foreach ($days as $dateStr => $day) {
                    $omset  = $day['omset'];
                    $profit = $day['profit'];
                    $totalOmset  += $omset;
                    $totalProfit += $profit;
                    $totalCount  += $day['count'];
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

                $overallMarginPct = $totalOmset > 0 ? round(($totalProfit / $totalOmset) * 100, 1) . '%' : '-';
                $avgOmset  = count($days) > 0 ? (int)round($totalOmset  / count($days)) : 0;
                $avgProfit = count($days) > 0 ? (int)round($totalProfit / count($days)) : 0;

                echo '<tr><td colspan="7" class="spacer"></td></tr>';

                echo '<tr class="total-row">';
                echo '<td colspan="3" class="center bold" style="font-size:11pt;">GRAND TOTAL</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalOmset) . '</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalProfit) . '</td>';
                echo '<td class="percent center bold">' . $overallMarginPct . '</td>';
                echo '<td class="center bold">' . $totalCount . ' Trx</td>';
                echo '</tr>';

                echo '<tr class="avg-row">';
                echo '<td colspan="3" class="center bold">RATA-RATA HARIAN</td>';
                echo '<td class="currency">' . $this->rupiah($avgOmset) . '</td>';
                echo '<td class="currency">' . $this->rupiah($avgProfit) . '</td>';
                echo '<td colspan="2" class="center">-</td>';
                echo '</tr>';

                echo '</tbody></table></body></html>';
            }, $filename, [
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

            return response()->streamDownload(function () use ($weeksBreakdown, $startOfMonth, $activeBranch, $printDate, $printedBy) {
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>' . $this->excelStyles() . '</style></head><body>';

                // Metadata
                echo '<table style="margin-bottom:16px;">';
                echo '<tr><td colspan="7" class="title" style="height:45px;">LAPORAN PENJUALAN BULANAN</td></tr>';
                echo '<tr><td colspan="7" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
                echo '<tr><td colspan="7" class="spacer"></td></tr>';
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

                $totalOmset  = 0;
                $totalProfit = 0;
                $totalCount  = 0;
                $idx = 1;

                foreach ($weeksBreakdown as $wNum => $w) {
                    $omset  = $w['omset'];
                    $profit = $w['profit'];
                    $totalOmset  += $omset;
                    $totalProfit += $profit;
                    $totalCount  += $w['count'];
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

                $overallMarginPct = $totalOmset > 0 ? round(($totalProfit / $totalOmset) * 100, 1) . '%' : '-';
                $avgOmset  = count($weeksBreakdown) > 0 ? (int)round($totalOmset  / count($weeksBreakdown)) : 0;
                $avgProfit = count($weeksBreakdown) > 0 ? (int)round($totalProfit / count($weeksBreakdown)) : 0;

                echo '<tr><td colspan="7" class="spacer"></td></tr>';

                echo '<tr class="total-row">';
                echo '<td colspan="3" class="center bold" style="font-size:11pt;">GRAND TOTAL</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalOmset) . '</td>';
                echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalProfit) . '</td>';
                echo '<td class="percent center bold">' . $overallMarginPct . '</td>';
                echo '<td class="center bold">' . $totalCount . ' Trx</td>';
                echo '</tr>';

                echo '<tr class="avg-row">';
                echo '<td colspan="3" class="center bold">RATA-RATA MINGGUAN</td>';
                echo '<td class="currency">' . $this->rupiah($avgOmset) . '</td>';
                echo '<td class="currency">' . $this->rupiah($avgProfit) . '</td>';
                echo '<td colspan="2" class="center">-</td>';
                echo '</tr>';

                echo '</tbody></table></body></html>';
            }, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }
    }
}
