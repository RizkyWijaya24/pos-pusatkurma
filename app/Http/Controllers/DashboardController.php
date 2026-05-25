<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the Admin Dashboard.
     */
    public function admin()
    {
        $products = Product::all();
        $cashiers = User::where('role', 'kasir')->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'branch' => $user->branch ?? 'Pusat Cianjur',
                'lastActive' => 'Aktif Sekarang' // default or dynamic placeholder
            ];
        });

        return view('admin.dashboard', compact('products', 'cashiers'));
    }

    /**
     * Display the Kasir Dashboard.
     */
    public function kasir()
    {
        // STRICT SECURITY: Exclude cost_price from the columns selected for Cashier view!
        // Also map selling_price as 'price' to ensure seamless integration with cashier frontend
        $products = Product::select([
            'id',
            'sku',
            'name',
            'category',
            'selling_price as price',
            'price_unit',
            'image_path',
            'stock'
        ])->get();

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

        return view('kasir.dashboard', compact('products', 'todayTransactionsMapped', 'todayExpensesMapped'));
    }

    /**
     * Display the Owner Dashboard.
     */
    public function owner(Request $request)
    {
        // 1. Fetch stock alerts (already existing)
        $lowStockCount = Product::where('stock', '<=', 10)->count();
        $lowStockProducts = Product::where('stock', '<=', 10)->get();

        // 2. Resolve Active Filter (?filter=today/weekly/monthly)
        $activeFilter = $request->query('filter', 'today');
        if (!in_array($activeFilter, ['today', 'weekly', 'monthly'])) {
            $activeFilter = 'today';
        }

        // Resolve selected branch filter
        $selectedBranch = $request->query('branch');

        // All unique branches for dropdown — optimized single UNION query
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
        $activeStats = null;
        $comparisonStats = null;
        $activeExpenses = 0;
        $comparisonExpenses = 0;

        if ($activeFilter === 'today') {
            $activeStats = $baseQuery()
                ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->first();

            $comparisonStats = $baseQuery()
                ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                ->whereDate('created_at', \Carbon\Carbon::yesterday())
                ->first();

            $activeExpenses = $expenseQuery()
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->sum('amount');

            $comparisonExpenses = $expenseQuery()
                ->whereDate('created_at', \Carbon\Carbon::yesterday())
                ->sum('amount');
        } elseif ($activeFilter === 'weekly') {
            $now = \Carbon\Carbon::now();
            $startOfWeek = $now->copy()->startOfWeek();
            $endOfWeek   = $now->copy()->endOfWeek();
            $activeStats = $baseQuery()
                ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->first();

            $startOfLastWeek = $now->copy()->subWeek()->startOfWeek();
            $endOfLastWeek   = $now->copy()->subWeek()->endOfWeek();
            $comparisonStats = $baseQuery()
                ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
                ->first();

            $activeExpenses = $expenseQuery()
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->sum('amount');

            $comparisonExpenses = $expenseQuery()
                ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
                ->sum('amount');
        } else { // monthly
            $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
            $endOfMonth = \Carbon\Carbon::now()->endOfMonth();
            $activeStats = $baseQuery()
                ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->first();

            $startOfLastMonth = \Carbon\Carbon::now()->subMonth()->startOfMonth();
            $endOfLastMonth = \Carbon\Carbon::now()->subMonth()->endOfMonth();
            $comparisonStats = $baseQuery()
                ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit, COUNT(id) as count')
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->first();

            $activeExpenses = $expenseQuery()
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $comparisonExpenses = $expenseQuery()
                ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
                ->sum('amount');
        }

        $activeRevenue = $activeStats->omset ?? 0;
        // Total Profit Bersih = (Omset Penjualan - Modal Produk) - Total Pengeluaran Kasir
        $activeProfit = ($activeStats->profit ?? 0) - $activeExpenses;
        $activeTransactionsCount = $activeStats->count ?? 0;

        $comparisonRevenue = $comparisonStats->omset ?? 0;
        $comparisonProfit = ($comparisonStats->profit ?? 0) - $comparisonExpenses;
        $comparisonTransactionsCount = $comparisonStats->count ?? 0;

        // Calculate growth percentages comparing current period to preceding period
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

        // 4. Fetch/Build breakdown table data (with optional branch filter)
        $breakdownData = [];
        if ($activeFilter === 'today') {
            $todayQ = $baseQuery()->with('cashier')
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->latest()
                ->take(10);
            $todayTransactions = $todayQ->get();

            if ($todayTransactions->isEmpty()) {
                $todayTransactions = $baseQuery()->with('cashier')
                    ->latest()
                    ->take(5)
                    ->get();
            }
            $breakdownData = $todayTransactions;
        } elseif ($activeFilter === 'weekly') {
            $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
            $endOfWeek = \Carbon\Carbon::now()->endOfWeek();

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
        } else {
            $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
            $endOfMonth = \Carbon\Carbon::now()->endOfMonth();
            $daysInMonth = \Carbon\Carbon::now()->daysInMonth;

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

        // 5. Fetch weekly omset breakdown for the general trend graph (always all-branch for graph)
        $startOfWeek = \Carbon\Carbon::now()->startOfWeek();
        $endOfWeek = \Carbon\Carbon::now()->endOfWeek();

        $days = [];
        for ($date = $startOfWeek->copy(); $date->lte($endOfWeek); $date->addDay()) {
            $days[$date->format('Y-m-d')] = [
                'day_name' => $date->translatedFormat('D'),
                'full_day_name' => $date->translatedFormat('l'),
                'date' => $date->format('d M'),
                'omset' => 0,
                'is_today' => $date->isToday(),
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

        // 6. Fetch monthly weekly-breakdown for the general trend graph (filtered based on selectedBranch)
        $startOfMonth = \Carbon\Carbon::now()->startOfMonth();
        $endOfMonth = \Carbon\Carbon::now()->endOfMonth();
        $daysInMonth = \Carbon\Carbon::now()->daysInMonth;

        $weeks = [
            1 => ['label' => 'Minggu 1', 'full_name' => 'Minggu Pertama', 'date' => '01-07', 'omset' => 0, 'is_today' => false],
            2 => ['label' => 'Minggu 2', 'full_name' => 'Minggu Kedua', 'date' => '08-14', 'omset' => 0, 'is_today' => false],
            3 => ['label' => 'Minggu 3', 'full_name' => 'Minggu Ketiga', 'date' => '15-21', 'omset' => 0, 'is_today' => false],
            4 => ['label' => 'Minggu 4', 'full_name' => 'Minggu Keempat', 'date' => '22-28', 'omset' => 0, 'is_today' => false],
        ];
        if ($daysInMonth > 28) {
            $weeks[5] = ['label' => 'Minggu 5', 'full_name' => 'Minggu Kelima', 'date' => '29-' . $daysInMonth, 'omset' => 0, 'is_today' => false];
        }

        // Determine which week of the month today is
        $todayDay = \Carbon\Carbon::now()->day;
        foreach ($weeks as $wNum => &$w) {
            $start = ($wNum - 1) * 7 + 1;
            $end = ($wNum === 5) ? $daysInMonth : $wNum * 7;
            if ($todayDay >= $start && $todayDay <= $end) {
                $w['is_today'] = true;
            }
        }
        unset($w);

        $monthlyOmsetDetail = $baseQuery()
            ->selectRaw('DAY(created_at) as day, SUM(total_price) as total_omset')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('day')
            ->get();

        foreach ($monthlyOmsetDetail as $stat) {
            $day = (int)$stat->day;
            foreach ($weeks as $wNum => &$w) {
                $start = ($wNum - 1) * 7 + 1;
                $end = ($wNum === 5) ? $daysInMonth : $wNum * 7;
                if ($day >= $start && $day <= $end) {
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

        return view('owner.dashboard', compact(
            'lowStockCount',
            'lowStockProducts',
            'activeFilter',
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
            'expenseGrowthPercent'
        ));
    }

    /**
     * Export dynamic financial report as Excel-compatible CSV.
     */
    public function exportOwnerReport(Request $request)
    {
        $activeFilter = $request->query('filter', 'today');
        if (!in_array($activeFilter, ['today', 'weekly', 'monthly'])) {
            $activeFilter = 'today';
        }

        $activeBranch = $request->query('branch', '');
        $branchSuffix = $activeBranch ? '_' . str_replace(' ', '_', $activeBranch) : '';

        $now = \Carbon\Carbon::now();
        $printDate = $now->translatedFormat('d F Y - H:i');
        $printedBy = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';

        if ($activeFilter === 'today') {
            $filename = 'Laporan_Harian_POS' . $branchSuffix . '_' . $now->format('Y-m-d') . '.xls';
            
            $query = \App\Models\Transaction::with('cashier')
                ->whereDate('created_at', \Carbon\Carbon::today())
                ->latest();

            if ($activeBranch) {
                $query->where('branch', $activeBranch);
            }

            $todayTransactions = $query->get();

            return response()->streamDownload(function () use ($todayTransactions, $activeBranch, $printDate, $printedBy, $now) {
                echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
                echo '<head>';
                echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>';
                echo '  body { font-family: "Segoe UI", Arial, sans-serif; }';
                echo '  table { border-collapse: collapse; }';
                echo '  th { background-color: #059669; color: #ffffff; font-weight: bold; border: 1px solid #d1d5db; padding: 10px 8px; font-size: 11pt; }';
                echo '  td { border: 1px solid #d1d5db; padding: 8px 6px; font-size: 10pt; vertical-align: middle; }';
                echo '  .title { font-size: 16pt; font-weight: bold; color: #064e3b; text-align: center; }';
                echo '  .meta-label { font-weight: bold; color: #374151; background-color: #f3f4f6; width: 150px; }';
                echo '  .meta-value { color: #4b5563; }';
                echo '  .number { mso-number-format:"\\#,##0"; text-align: right; }';
                echo '  .currency { mso-number-format:"\\0022Rp \\0022\\#,##0"; text-align: right; }';
                echo '  .text { mso-number-format:"\@"; text-align: left; }';
                echo '  .center { text-align: center; }';
                echo '  .bold { font-weight: bold; }';
                echo '  .total-row { background-color: #ecfdf5; font-weight: bold; color: #065f46; }';
                echo '  .bg-gray { background-color: #f9fafb; }';
                echo '</style>';
                echo '</head>';
                echo '<body>';

                // Metadata
                echo '<table>';
                echo '  <tr><td colspan="9" class="title" style="height: 40px; text-align: center; font-size: 16pt; font-weight: bold;">LAPORAN PENJUALAN HARIAN POS</td></tr>';
                echo '  <tr><td colspan="9" class="title" style="height: 25px; text-align: center; font-size: 12pt; font-weight: bold; color: #10b981;">PUSAT KURMA PREMIUM</td></tr>';
                echo '  <tr><td colspan="9" style="height: 15px; border:none;"></td></tr>';
                echo '  <tr><td class="meta-label">Filter Waktu</td><td colspan="8" class="meta-value">Hari Ini (' . $now->translatedFormat('d F Y') . ')</td></tr>';
                echo '  <tr><td class="meta-label">Filter Cabang</td><td colspan="8" class="meta-value">' . htmlspecialchars($activeBranch ? $activeBranch : 'Semua Cabang') . '</td></tr>';
                echo '  <tr><td class="meta-label">Tanggal Cetak</td><td colspan="8" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
                echo '  <tr><td class="meta-label">Dicetak Oleh</td><td colspan="8" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
                echo '  <tr><td colspan="9" style="height: 20px; border:none;"></td></tr>';
                echo '</table>';

                // Table
                echo '<table>';
                echo '  <thead>';
                echo '    <tr>';
                echo '      <th style="width: 50px;">No</th>';
                echo '      <th style="width: 100px;">Waktu</th>';
                echo '      <th style="width: 140px;">Kode Transaksi</th>';
                echo '      <th style="width: 130px;">Kasir</th>';
                echo '      <th style="width: 120px;">Cabang</th>';
                echo '      <th style="width: 320px;">Ringkasan Item</th>';
                echo '      <th style="width: 140px;">Metode Pembayaran</th>';
                echo '      <th style="width: 180px;">Total Tagihan (Omset)</th>';
                echo '      <th style="width: 180px;">Profit Bersih</th>';
                echo '    </tr>';
                echo '  </thead>';
                echo '  <tbody>';

                $totalOmset = 0;
                $totalProfit = 0;
                $idx = 1;

                foreach ($todayTransactions as $trx) {
                    $omset = (int)$trx->total_price;
                    $profit = (int)($trx->total_price - $trx->total_cost);
                    $totalOmset += $omset;
                    $totalProfit += $profit;

                    echo '    <tr>';
                    echo '      <td class="center">' . $idx++ . '</td>';
                    echo '      <td class="center">' . htmlspecialchars($trx->created_at->translatedFormat('H:i')) . '</td>';
                    echo '      <td class="center bold text">' . htmlspecialchars($trx->transaction_code) . '</td>';
                    echo '      <td>' . htmlspecialchars($trx->cashier->name ?? 'N/A') . '</td>';
                    echo '      <td>' . htmlspecialchars($trx->branch ?? 'Pusat Cianjur') . '</td>';
                    echo '      <td class="text">' . htmlspecialchars($trx->items_summary) . '</td>';
                    echo '      <td class="center">' . htmlspecialchars($trx->payment_method) . '</td>';
                    echo '      <td class="currency">' . $omset . '</td>';
                    echo '      <td class="currency">' . $profit . '</td>';
                    echo '    </tr>';
                }

                $count = count($todayTransactions);
                $avgOmset = $count > 0 ? round($totalOmset / $count) : 0;
                $avgProfit = $count > 0 ? round($totalProfit / $count) : 0;

                echo '    <tr><td colspan="9" style="height: 10px; border:none;"></td></tr>';

                echo '    <tr class="total-row">';
                echo '      <td colspan="7" class="center bold">GRAND TOTAL</td>';
                echo '      <td class="currency">' . $totalOmset . '</td>';
                echo '      <td class="currency">' . $totalProfit . '</td>';
                echo '    </tr>';

                echo '    <tr class="total-row" style="background-color: #f0fdf4;">';
                echo '      <td colspan="7" class="center bold">RATA-RATA</td>';
                echo '      <td class="currency">' . $avgOmset . '</td>';
                echo '      <td class="currency">' . $avgProfit . '</td>';
                echo '    </tr>';

                echo '    <tr>';
                echo '      <td colspan="3" class="bold bg-gray">JUMLAH DATA</td>';
                echo '      <td colspan="6" class="bold">' . $count . ' Transaksi</td>';
                echo '    </tr>';

                echo '  </tbody>';
                echo '</table>';
                echo '</body>';
                echo '</html>';
            }, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } elseif ($activeFilter === 'weekly') {
            $filename = 'Laporan_Mingguan_POS' . $branchSuffix . '_' . $now->format('Y-m-d') . '.xls';
            
            $startOfWeek = $now->copy()->startOfWeek();
            $endOfWeek = $now->copy()->endOfWeek();

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
                echo '<head>';
                echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>';
                echo '  body { font-family: "Segoe UI", Arial, sans-serif; }';
                echo '  table { border-collapse: collapse; }';
                echo '  th { background-color: #059669; color: #ffffff; font-weight: bold; border: 1px solid #d1d5db; padding: 10px 8px; font-size: 11pt; }';
                echo '  td { border: 1px solid #d1d5db; padding: 8px 6px; font-size: 10pt; vertical-align: middle; }';
                echo '  .title { font-size: 16pt; font-weight: bold; color: #064e3b; text-align: center; }';
                echo '  .meta-label { font-weight: bold; color: #374151; background-color: #f3f4f6; width: 150px; }';
                echo '  .meta-value { color: #4b5563; }';
                echo '  .number { mso-number-format:"\\#,##0"; text-align: right; }';
                echo '  .currency { mso-number-format:"\\0022Rp \\0022\\#,##0"; text-align: right; }';
                echo '  .percent { mso-number-format:"0%"; text-align: right; }';
                echo '  .center { text-align: center; }';
                echo '  .bold { font-weight: bold; }';
                echo '  .total-row { background-color: #ecfdf5; font-weight: bold; color: #065f46; }';
                echo '  .bg-gray { background-color: #f9fafb; }';
                echo '</style>';
                echo '</head>';
                echo '<body>';

                // Metadata
                echo '<table>';
                echo '  <tr><td colspan="7" class="title" style="height: 40px; text-align: center; font-size: 16pt; font-weight: bold;">LAPORAN PENJUALAN MINGGUAN</td></tr>';
                echo '  <tr><td colspan="7" class="title" style="height: 25px; text-align: center; font-size: 12pt; font-weight: bold; color: #10b981;">PUSAT KURMA PREMIUM</td></tr>';
                echo '  <tr><td colspan="7" style="height: 15px; border:none;"></td></tr>';
                echo '  <tr><td class="meta-label">Rentang Waktu</td><td colspan="6" class="meta-value">' . htmlspecialchars($startOfWeek->translatedFormat('d M Y') . ' s/d ' . $endOfWeek->translatedFormat('d M Y')) . '</td></tr>';
                echo '  <tr><td class="meta-label">Filter Cabang</td><td colspan="6" class="meta-value">' . htmlspecialchars($activeBranch ? $activeBranch : 'Semua Cabang') . '</td></tr>';
                echo '  <tr><td class="meta-label">Tanggal Cetak</td><td colspan="6" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
                echo '  <tr><td class="meta-label">Dicetak Oleh</td><td colspan="6" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
                echo '  <tr><td colspan="7" style="height: 20px; border:none;"></td></tr>';
                echo '</table>';

                // Table
                echo '<table>';
                echo '  <thead>';
                echo '    <tr>';
                echo '      <th style="width: 50px;">No</th>';
                echo '      <th style="width: 180px;">Periode Tanggal</th>';
                echo '      <th style="width: 120px;">Hari</th>';
                echo '      <th style="width: 180px;">Total Omset</th>';
                echo '      <th style="width: 180px;">Profit Bersih</th>';
                echo '      <th style="width: 150px;">Margin Keuntungan</th>';
                echo '      <th style="width: 150px;">Jumlah Transaksi</th>';
                echo '    </tr>';
                echo '  </thead>';
                echo '  <tbody>';

                $totalOmset = 0;
                $totalProfit = 0;
                $totalCount = 0;
                $idx = 1;

                foreach ($days as $dateStr => $day) {
                    $omset = $day['omset'];
                    $profit = $day['profit'];
                    $totalOmset += $omset;
                    $totalProfit += $profit;
                    $totalCount += $day['count'];

                    $margin = $omset > 0 ? ($profit / $omset) : 0;

                    echo '    <tr>';
                    echo '      <td class="center">' . $idx++ . '</td>';
                    echo '      <td class="center">' . htmlspecialchars($day['sub_label']) . '</td>';
                    echo '      <td class="center">' . htmlspecialchars($day['label']) . '</td>';
                    echo '      <td class="currency">' . $omset . '</td>';
                    echo '      <td class="currency">' . $profit . '</td>';
                    echo '      <td class="percent">' . $margin . '</td>';
                    echo '      <td class="center">' . $day['count'] . ' Trx</td>';
                    echo '    </tr>';
                }

                $overallMargin = $totalOmset > 0 ? ($totalProfit / $totalOmset) : 0;
                $avgOmset = count($days) > 0 ? round($totalOmset / count($days)) : 0;
                $avgProfit = count($days) > 0 ? round($totalProfit / count($days)) : 0;

                echo '    <tr><td colspan="7" style="height: 10px; border:none;"></td></tr>';

                echo '    <tr class="total-row">';
                echo '      <td colspan="3" class="center bold">GRAND TOTAL</td>';
                echo '      <td class="currency">' . $totalOmset . '</td>';
                echo '      <td class="currency">' . $totalProfit . '</td>';
                echo '      <td class="percent">' . $overallMargin . '</td>';
                echo '      <td class="center bold">' . $totalCount . ' Trx</td>';
                echo '    </tr>';

                echo '    <tr class="total-row" style="background-color: #f0fdf4;">';
                echo '      <td colspan="3" class="center bold">RATA-RATA HARIAN</td>';
                echo '      <td class="currency">' . $avgOmset . '</td>';
                echo '      <td class="currency">' . $avgProfit . '</td>';
                echo '      <td colspan="2" class="bg-gray"></td>';
                echo '    </tr>';

                echo '  </tbody>';
                echo '</table>';
                echo '</body>';
                echo '</html>';
            }, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);

        } else { // monthly
            $filename = 'Laporan_Bulanan_POS' . $branchSuffix . '_' . $now->format('Y-m-d') . '.xls';

            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth = $now->copy()->endOfMonth();
            $daysInMonth = $now->daysInMonth;

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
                echo '<head>';
                echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
                echo '<style>';
                echo '  body { font-family: "Segoe UI", Arial, sans-serif; }';
                echo '  table { border-collapse: collapse; }';
                echo '  th { background-color: #059669; color: #ffffff; font-weight: bold; border: 1px solid #d1d5db; padding: 10px 8px; font-size: 11pt; }';
                echo '  td { border: 1px solid #d1d5db; padding: 8px 6px; font-size: 10pt; vertical-align: middle; }';
                echo '  .title { font-size: 16pt; font-weight: bold; color: #064e3b; text-align: center; }';
                echo '  .meta-label { font-weight: bold; color: #374151; background-color: #f3f4f6; width: 150px; }';
                echo '  .meta-value { color: #4b5563; }';
                echo '  .number { mso-number-format:"\\#,##0"; text-align: right; }';
                echo '  .currency { mso-number-format:"\\0022Rp \\0022\\#,##0"; text-align: right; }';
                echo '  .percent { mso-number-format:"0%"; text-align: right; }';
                echo '  .center { text-align: center; }';
                echo '  .bold { font-weight: bold; }';
                echo '  .total-row { background-color: #ecfdf5; font-weight: bold; color: #065f46; }';
                echo '  .bg-gray { background-color: #f9fafb; }';
                echo '</style>';
                echo '</head>';
                echo '<body>';

                // Metadata
                echo '<table>';
                echo '  <tr><td colspan="7" class="title" style="height: 40px; text-align: center; font-size: 16pt; font-weight: bold;">LAPORAN PENJUALAN BULANAN</td></tr>';
                echo '  <tr><td colspan="7" class="title" style="height: 25px; text-align: center; font-size: 12pt; font-weight: bold; color: #10b981;">PUSAT KURMA PREMIUM</td></tr>';
                echo '  <tr><td colspan="7" style="height: 15px; border:none;"></td></tr>';
                echo '  <tr><td class="meta-label">Rentang Waktu</td><td colspan="6" class="meta-value">' . htmlspecialchars($startOfMonth->translatedFormat('F Y')) . '</td></tr>';
                echo '  <tr><td class="meta-label">Filter Cabang</td><td colspan="6" class="meta-value">' . htmlspecialchars($activeBranch ? $activeBranch : 'Semua Cabang') . '</td></tr>';
                echo '  <tr><td class="meta-label">Tanggal Cetak</td><td colspan="6" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
                echo '  <tr><td class="meta-label">Dicetak Oleh</td><td colspan="6" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
                echo '  <tr><td colspan="7" style="height: 20px; border:none;"></td></tr>';
                echo '</table>';

                // Table
                echo '<table>';
                echo '  <thead>';
                echo '    <tr>';
                echo '      <th style="width: 50px;">No</th>';
                echo '      <th style="width: 150px;">Periode</th>';
                echo '      <th style="width: 200px;">Rentang Tanggal</th>';
                echo '      <th style="width: 180px;">Total Omset</th>';
                echo '      <th style="width: 180px;">Profit Bersih</th>';
                echo '      <th style="width: 150px;">Margin Keuntungan</th>';
                echo '      <th style="width: 150px;">Jumlah Transaksi</th>';
                echo '    </tr>';
                echo '  </thead>';
                echo '  <tbody>';

                $totalOmset = 0;
                $totalProfit = 0;
                $totalCount = 0;
                $idx = 1;

                foreach ($weeksBreakdown as $wNum => $w) {
                    $omset = $w['omset'];
                    $profit = $w['profit'];
                    $totalOmset += $omset;
                    $totalProfit += $profit;
                    $totalCount += $w['count'];

                    $margin = $omset > 0 ? ($profit / $omset) : 0;

                    echo '    <tr>';
                    echo '      <td class="center">' . $idx++ . '</td>';
                    echo '      <td class="center">' . htmlspecialchars($w['label']) . '</td>';
                    echo '      <td class="center">' . htmlspecialchars($w['sub_label']) . '</td>';
                    echo '      <td class="currency">' . $omset . '</td>';
                    echo '      <td class="currency">' . $profit . '</td>';
                    echo '      <td class="percent">' . $margin . '</td>';
                    echo '      <td class="center">' . $w['count'] . ' Trx</td>';
                    echo '    </tr>';
                }

                $overallMargin = $totalOmset > 0 ? ($totalProfit / $totalOmset) : 0;
                $avgOmset = count($weeksBreakdown) > 0 ? round($totalOmset / count($weeksBreakdown)) : 0;
                $avgProfit = count($weeksBreakdown) > 0 ? round($totalProfit / count($weeksBreakdown)) : 0;

                echo '    <tr><td colspan="7" style="height: 10px; border:none;"></td></tr>';

                echo '    <tr class="total-row">';
                echo '      <td colspan="3" class="center bold">GRAND TOTAL</td>';
                echo '      <td class="currency">' . $totalOmset . '</td>';
                echo '      <td class="currency">' . $totalProfit . '</td>';
                echo '      <td class="percent">' . $overallMargin . '</td>';
                echo '      <td class="center bold">' . $totalCount . ' Trx</td>';
                echo '    </tr>';

                echo '    <tr class="total-row" style="background-color: #f0fdf4;">';
                echo '      <td colspan="3" class="center bold">RATA-RATA MINGGUAN</td>';
                echo '      <td class="currency">' . $avgOmset . '</td>';
                echo '      <td class="currency">' . $avgProfit . '</td>';
                echo '      <td colspan="2" class="bg-gray"></td>';
                echo '    </tr>';

                echo '  </tbody>';
                echo '</table>';
                echo '</body>';
                echo '</html>';
            }, $filename, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        }
    }
}
