<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Display a listing of the logged-in cashier's transactions (Kasir view).
     * Includes daily list, weekly summary, and monthly summary.
     */
    public function index(Request $request)
    {
        $cashierId = auth()->id();
        $now       = Carbon::now();
        $activeTab = $request->input('tab', 'daily');

        // ---- Daily (paginated list) ----
        $transactions = Transaction::where('cashier_id', $cashierId)
            ->whereDate('created_at', Carbon::today())
            ->latest()
            ->paginate(15);

        // ---- Today totals ----
        $todayStats  = Transaction::where('cashier_id', $cashierId)
            ->whereDate('created_at', Carbon::today())
            ->selectRaw('COALESCE(SUM(total_price),0) as omset, 
                         COALESCE(SUM(total_price - total_cost),0) as profit, 
                         COUNT(*) as count,
                         COALESCE(SUM(CASE WHEN payment_method = \'Cash\' THEN total_price ELSE 0 END),0) as cash_total,
                         COALESCE(SUM(CASE WHEN payment_method = \'QRIS\' THEN total_price ELSE 0 END),0) as qris_total,
                         COALESCE(SUM(CASE WHEN payment_method = \'Debit\' THEN total_price ELSE 0 END),0) as debit_total')
            ->first();

        // ---- Weekly summary (Mon–Sun) ----
        $startOfWeek = $now->copy()->startOfWeek();
        $endOfWeek   = $now->copy()->endOfWeek();
        $weeklyDays  = [];
        for ($d = $startOfWeek->copy(); $d->lte($endOfWeek); $d->addDay()) {
            $weeklyDays[$d->format('Y-m-d')] = [
                'label'     => $d->translatedFormat('l'),
                'sub_label' => $d->translatedFormat('d M Y'),
                'omset'     => 0,
                'profit'    => 0,
                'count'     => 0,
            ];
        }
        $weeklyTrxs = Transaction::where('cashier_id', $cashierId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->get();
        foreach ($weeklyTrxs as $trx) {
            $key = $trx->created_at->format('Y-m-d');
            if (isset($weeklyDays[$key])) {
                $weeklyDays[$key]['omset']  += (int)$trx->total_price;
                $weeklyDays[$key]['profit'] += (int)($trx->total_price - $trx->total_cost);
                $weeklyDays[$key]['count']++;
            }
        }
        $weeklyOmset  = array_sum(array_column($weeklyDays, 'omset'));
        $weeklyProfit = array_sum(array_column($weeklyDays, 'profit'));
        $weeklyCount  = array_sum(array_column($weeklyDays, 'count'));

        // ---- Monthly summary (per week) ----
        $startOfMonth  = $now->copy()->startOfMonth();
        $endOfMonth    = $now->copy()->endOfMonth();
        $daysInMonth   = $now->daysInMonth;
        $monthlyWeeks  = [
            1 => ['label' => 'Minggu 1', 'sub_label' => '01 – 07 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 1,  'end' => 7],
            2 => ['label' => 'Minggu 2', 'sub_label' => '08 – 14 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 8,  'end' => 14],
            3 => ['label' => 'Minggu 3', 'sub_label' => '15 – 21 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 15, 'end' => 21],
            4 => ['label' => 'Minggu 4', 'sub_label' => '22 – 28 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 22, 'end' => 28],
        ];
        if ($daysInMonth > 28) {
            $monthlyWeeks[5] = ['label' => 'Minggu 5', 'sub_label' => '29 – ' . $daysInMonth . ' ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 29, 'end' => $daysInMonth];
        }
        $monthlyTrxs = Transaction::where('cashier_id', $cashierId)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->get();
        foreach ($monthlyTrxs as $trx) {
            $day = $trx->created_at->day;
            foreach ($monthlyWeeks as $wNum => &$w) {
                if ($day >= $w['start'] && $day <= $w['end']) {
                    $w['omset']  += (int)$trx->total_price;
                    $w['profit'] += (int)($trx->total_price - $trx->total_cost);
                    $w['count']++;
                    break;
                }
            }
            unset($w);
        }
        $monthlyOmset  = array_sum(array_column($monthlyWeeks, 'omset'));
        $monthlyProfit = array_sum(array_column($monthlyWeeks, 'profit'));
        $monthlyCount  = array_sum(array_column($monthlyWeeks, 'count'));

        return view('kasir.transactions', compact(
            'transactions',
            'activeTab',
            'now',
            'todayStats',
            'weeklyDays',
            'weeklyOmset',
            'weeklyProfit',
            'weeklyCount',
            'startOfWeek',
            'endOfWeek',
            'monthlyWeeks',
            'monthlyOmset',
            'monthlyProfit',
            'monthlyCount',
            'startOfMonth'
        ));
    }

    /**
     * Export kasir's own transaction summary (weekly or monthly) as Excel.
     */
    public function exportKasir(Request $request)
    {
        $type      = $request->input('type', 'weekly'); // 'weekly' or 'monthly'
        $cashierId = auth()->id();
        $cashier   = auth()->user();
        $now       = Carbon::now();
        $printDate = $now->translatedFormat('d F Y - H:i');
        $printedBy = $cashier->name . ' (Kasir)';

        if ($type === 'weekly') {
            $startOfWeek = $now->copy()->startOfWeek();
            $endOfWeek   = $now->copy()->endOfWeek();
            $days        = [];
            for ($d = $startOfWeek->copy(); $d->lte($endOfWeek); $d->addDay()) {
                $days[$d->format('Y-m-d')] = [
                    'label'     => $d->translatedFormat('l'),
                    'sub_label' => $d->translatedFormat('d M Y'),
                    'omset'     => 0,
                    'profit'    => 0,
                    'count'     => 0,
                ];
            }
            Transaction::where('cashier_id', $cashierId)
                ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
                ->get()
                ->each(function ($trx) use (&$days) {
                    $key = $trx->created_at->format('Y-m-d');
                    if (isset($days[$key])) {
                        $days[$key]['omset']  += (int)$trx->total_price;
                        $days[$key]['profit'] += (int)($trx->total_price - $trx->total_cost);
                        $days[$key]['count']++;
                    }
                });

            $filename    = 'Laporan_Mingguan_Kasir_' . $now->format('Y-m-d') . '.xls';
            $titlePeriod = $startOfWeek->translatedFormat('d M Y') . ' s/d ' . $endOfWeek->translatedFormat('d M Y');

            $isKasir = auth()->user()->isKasir();
            return response()->streamDownload(function () use ($days, $titlePeriod, $printDate, $printedBy, $cashier, $isKasir) {
                $this->streamKasirWeekly($days, $titlePeriod, $printDate, $printedBy, $cashier->name, $isKasir);
            }, $filename, [
                'Content-Type'  => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma'        => 'no-cache',
                'Expires'       => '0',
            ]);

        } else { // monthly
            $startOfMonth = $now->copy()->startOfMonth();
            $endOfMonth   = $now->copy()->endOfMonth();
            $daysInMonth  = $now->daysInMonth;
            $weeks        = [
                1 => ['label' => 'Minggu 1', 'sub_label' => '01 – 07 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 1,  'end' => 7],
                2 => ['label' => 'Minggu 2', 'sub_label' => '08 – 14 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 8,  'end' => 14],
                3 => ['label' => 'Minggu 3', 'sub_label' => '15 – 21 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 15, 'end' => 21],
                4 => ['label' => 'Minggu 4', 'sub_label' => '22 – 28 ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 22, 'end' => 28],
            ];
            if ($daysInMonth > 28) {
                $weeks[5] = ['label' => 'Minggu 5', 'sub_label' => '29 – ' . $daysInMonth . ' ' . $startOfMonth->translatedFormat('F'), 'omset' => 0, 'profit' => 0, 'count' => 0, 'start' => 29, 'end' => $daysInMonth];
            }
            Transaction::where('cashier_id', $cashierId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->get()
                ->each(function ($trx) use (&$weeks) {
                    $day = $trx->created_at->day;
                    foreach ($weeks as $wNum => &$w) {
                        if ($day >= $w['start'] && $day <= $w['end']) {
                            $w['omset']  += (int)$trx->total_price;
                            $w['profit'] += (int)($trx->total_price - $trx->total_cost);
                            $w['count']++;
                            break;
                        }
                    }
                    unset($w);
                });

            $filename    = 'Laporan_Bulanan_Kasir_' . $now->format('Y-m-d') . '.xls';
            $titlePeriod = $startOfMonth->translatedFormat('F Y');

            $isKasir = auth()->user()->isKasir();
            return response()->streamDownload(function () use ($weeks, $titlePeriod, $printDate, $printedBy, $cashier, $isKasir) {
                $this->streamKasirMonthly($weeks, $titlePeriod, $printDate, $printedBy, $cashier->name, $isKasir);
            }, $filename, [
                'Content-Type'  => 'application/vnd.ms-excel; charset=UTF-8',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma'        => 'no-cache',
                'Expires'       => '0',
            ]);
        }
    }

    /**
     * Stream weekly kasir summary as Excel HTML.
     */
    private function streamKasirWeekly(array $days, string $period, string $printDate, string $printedBy, string $cashierName, bool $isKasir = false): void
    {
        $styles = $this->kasirExcelStyles();
        $colspan = $isKasir ? 5 : 6;
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>' . $styles . '</style></head><body>';
        echo '<table style="margin-bottom:16px;"><tr><td colspan="' . $colspan . '" class="title" style="height:45px;">LAPORAN PENJUALAN MINGGUAN KASIR</td></tr>';
        echo '<tr><td colspan="' . $colspan . '" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
        echo '<tr><td colspan="' . $colspan . '" class="spacer"></td></tr>';
        echo '<tr><td class="meta-label">Kasir</td><td colspan="' . ($colspan - 1) . '" class="meta-value">' . htmlspecialchars($cashierName) . '</td></tr>';
        echo '<tr><td class="meta-label">Periode</td><td colspan="' . ($colspan - 1) . '" class="meta-value">' . htmlspecialchars($period) . '</td></tr>';
        echo '<tr><td class="meta-label">Tanggal Cetak</td><td colspan="' . ($colspan - 1) . '" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
        echo '<tr><td class="meta-label">Dicetak Oleh</td><td colspan="' . ($colspan - 1) . '" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
        echo '<tr><td colspan="' . $colspan . '" class="spacer"></td></tr></table>';

        echo '<table><thead><tr>';
        echo '<th style="width:45px;">No</th>';
        echo '<th style="width:190px;">Tanggal</th>';
        echo '<th style="width:130px;">Hari</th>';
        echo '<th style="width:210px;">Total Omset</th>';
        if (!$isKasir) {
            echo '<th style="width:210px;">Profit Bersih</th>';
        }
        echo '<th style="width:130px;">Jml Transaksi</th>';
        echo '</tr></thead><tbody>';

        $totalOmset  = 0;
        $totalProfit = 0;
        $totalCount  = 0;
        $idx = 1;
        foreach ($days as $dateStr => $day) {
            $rowClass     = $idx % 2 === 0 ? ' class="stripe"' : '';
            $totalOmset  += $day['omset'];
            $totalProfit += $day['profit'];
            $totalCount  += $day['count'];
            echo '<tr' . $rowClass . '>';
            echo '<td class="center">' . $idx++ . '</td>';
            echo '<td class="center">' . htmlspecialchars($day['sub_label']) . '</td>';
            echo '<td class="center">' . htmlspecialchars($day['label']) . '</td>';
            echo '<td class="currency">' . $this->rupiah($day['omset']) . '</td>';
            if (!$isKasir) {
                echo '<td class="currency">' . $this->rupiah($day['profit']) . '</td>';
            }
            echo '<td class="center">' . $day['count'] . ' Trx</td>';
            echo '</tr>';
        }
        echo '<tr><td colspan="' . $colspan . '" class="spacer"></td></tr>';
        echo '<tr class="total-row"><td colspan="3" class="center bold" style="font-size:11pt;">GRAND TOTAL</td>';
        echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalOmset) . '</td>';
        if (!$isKasir) {
            echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalProfit) . '</td>';
        }
        echo '<td class="center bold">' . $totalCount . ' Trx</td></tr>';
        echo '</tbody></table></body></html>';
    }

    /**
     * Stream monthly kasir summary as Excel HTML.
     */
    private function streamKasirMonthly(array $weeks, string $period, string $printDate, string $printedBy, string $cashierName, bool $isKasir = false): void
    {
        $styles = $this->kasirExcelStyles();
        $colspan = $isKasir ? 5 : 6;
        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>' . $styles . '</style></head><body>';
        echo '<table style="margin-bottom:16px;"><tr><td colspan="' . $colspan . '" class="title" style="height:45px;">LAPORAN PENJUALAN BULANAN KASIR</td></tr>';
        echo '<tr><td colspan="' . $colspan . '" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
        echo '<tr><td colspan="' . $colspan . '" class="spacer"></td></tr>';
        echo '<tr><td class="meta-label">Kasir</td><td colspan="' . ($colspan - 1) . '" class="meta-value">' . htmlspecialchars($cashierName) . '</td></tr>';
        echo '<tr><td class="meta-label">Periode</td><td colspan="' . ($colspan - 1) . '" class="meta-value">' . htmlspecialchars($period) . '</td></tr>';
        echo '<tr><td class="meta-label">Tanggal Cetak</td><td colspan="' . ($colspan - 1) . '" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
        echo '<tr><td class="meta-label">Dicetak Oleh</td><td colspan="' . ($colspan - 1) . '" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
        echo '<tr><td colspan="' . $colspan . '" class="spacer"></td></tr></table>';

        echo '<table><thead><tr>';
        echo '<th style="width:45px;">No</th>';
        echo '<th style="width:150px;">Periode</th>';
        echo '<th style="width:220px;">Rentang Tanggal</th>';
        echo '<th style="width:210px;">Total Omset</th>';
        if (!$isKasir) {
            echo '<th style="width:210px;">Profit Bersih</th>';
        }
        echo '<th style="width:130px;">Jml Transaksi</th>';
        echo '</tr></thead><tbody>';

        $totalOmset  = 0;
        $totalProfit = 0;
        $totalCount  = 0;
        $idx = 1;
        foreach ($weeks as $wNum => $w) {
            $rowClass     = $idx % 2 === 0 ? ' class="stripe"' : '';
            $totalOmset  += $w['omset'];
            $totalProfit += $w['profit'];
            $totalCount  += $w['count'];
            echo '<tr' . $rowClass . '>';
            echo '<td class="center">' . $idx++ . '</td>';
            echo '<td class="center">' . htmlspecialchars($w['label']) . '</td>';
            echo '<td class="center">' . htmlspecialchars($w['sub_label']) . '</td>';
            echo '<td class="currency">' . $this->rupiah($w['omset']) . '</td>';
            if (!$isKasir) {
                echo '<td class="currency">' . $this->rupiah($w['profit']) . '</td>';
            }
            echo '<td class="center">' . $w['count'] . ' Trx</td>';
            echo '</tr>';
        }
        echo '<tr><td colspan="' . $colspan . '" class="spacer"></td></tr>';
        echo '<tr class="total-row"><td colspan="3" class="center bold" style="font-size:11pt;">GRAND TOTAL</td>';
        echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalOmset) . '</td>';
        if (!$isKasir) {
            echo '<td class="currency" style="font-size:11pt;">' . $this->rupiah($totalProfit) . '</td>';
        }
        echo '<td class="center bold">' . $totalCount . ' Trx</td></tr>';
        echo '</tbody></table></body></html>';
    }

    /**
     * Excel styles for kasir export.
     */
    private function kasirExcelStyles(): string
    {
        return implode('', [
            'body{font-family:"Segoe UI",Arial,sans-serif;}',
            'table{border-collapse:collapse;}',
            'th{background-color:#059669;color:#fff;font-weight:bold;border:1px solid #047857;padding:10px 14px;font-size:11pt;white-space:nowrap;}',
            'td{border:1px solid #d1fae5;padding:8px 12px;font-size:10pt;vertical-align:middle;}',
            '.title{font-size:16pt;font-weight:bold;color:#064e3b;text-align:center;border:none !important;}',
            '.subtitle{font-size:12pt;font-weight:bold;color:#10b981;text-align:center;border:none !important;}',
            '.meta-label{font-weight:bold;color:#374151;background-color:#f0fdf4;width:160px;border:1px solid #d1fae5;}',
            '.meta-value{color:#1f2937;border:1px solid #d1fae5;}',
            '.center{text-align:center;}',
            '.bold{font-weight:bold;}',
            '.currency{text-align:right;font-weight:bold;color:#065f46;}',
            '.total-row{background-color:#d1fae5;font-weight:bold;color:#064e3b;}',
            '.spacer{border:none !important;height:10px;}',
            '.stripe{background-color:#f9fafb;}',
        ]);
    }

    /**
     * Display ALL transactions for admin — with edit/delete capabilities.
     */
    public function adminIndex(Request $request)
    {
        $query = Transaction::with('cashier')->latest();

        // Optional date filter - default to today
        $date = $request->has('date') ? $request->input('date') : Carbon::today()->toDateString();
        if (!empty($date)) {
            $query->whereDate('created_at', $date);
        }

        // Optional branch filter
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->paginate(20);

        // Fetch paginated expenses with filters
        $expenseQuery = \App\Models\Expense::with('cashier')->latest();
        if (!empty($date)) {
            $expenseQuery->whereDate('created_at', $date);
        }
        if ($request->filled('branch')) {
            $expenseQuery->where('branch', $request->branch);
        }
        $expenses = $expenseQuery->paginate(20, ['*'], 'expense_page')->withQueryString();

        // Resolve target date (defaults to today)
        $targetDate = !empty($date) ? Carbon::parse($date) : Carbon::today();

        // Calculate time ranges based on the target date
        $today        = $targetDate->copy()->startOfDay();
        $startOfWeek  = $targetDate->copy()->startOfWeek();
        $endOfWeek    = $targetDate->copy()->endOfWeek();
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth   = $targetDate->copy()->endOfMonth();

        // Apply branch filter to stats if specified
        $statsQuery = fn() => $request->filled('branch')
            ? Transaction::where('branch', $request->branch)
            : Transaction::query();

        $weeklyStats = $statsQuery()
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->first();

        $monthlyStats = $statsQuery()
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->first();

        $todayStats = $statsQuery()
            ->selectRaw('SUM(total_price) as omset, 
                         SUM(total_price - total_cost) as profit,
                         COALESCE(SUM(CASE WHEN payment_method = \'Cash\' THEN total_price ELSE 0 END),0) as cash_total,
                         COALESCE(SUM(CASE WHEN payment_method = \'QRIS\' THEN total_price ELSE 0 END),0) as qris_total,
                         COALESCE(SUM(CASE WHEN payment_method = \'Debit\' THEN total_price ELSE 0 END),0) as debit_total')
            ->whereDate('created_at', $today)
            ->first();

        $weeklyOmset   = $weeklyStats->omset ?? 0;
        $weeklyProfit  = $weeklyStats->profit ?? 0;
        $monthlyOmset  = $monthlyStats->omset ?? 0;
        $monthlyProfit = $monthlyStats->profit ?? 0;
        $todayOmset    = $todayStats->omset ?? 0;
        $todayProfit   = $todayStats->profit ?? 0;
        $todayCash     = $todayStats->cash_total ?? 0;
        $todayQris     = $todayStats->qris_total ?? 0;
        $todayDebit    = $todayStats->debit_total ?? 0;

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
        $activeBranch = $request->input('branch', '');

        return view('admin.transactions', compact(
            'transactions',
            'expenses',
            'weeklyOmset',
            'weeklyProfit',
            'monthlyOmset',
            'monthlyProfit',
            'todayOmset',
            'todayProfit',
            'todayCash',
            'todayQris',
            'todayDebit',
            'targetDate',
            'startOfWeek',
            'endOfWeek',
            'branches',
            'activeBranch'
        ));
    }

    /**
     * Display ALL transactions for owner — read-only.
     */
    public function ownerIndex(Request $request)
    {
        $query = Transaction::with('cashier')->latest();

        // Optional date filter - default to today
        $date = $request->has('date') ? $request->input('date') : Carbon::today()->toDateString();
        if (!empty($date)) {
            $query->whereDate('created_at', $date);
        }

        // Optional branch filter
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->paginate(20);

        // Fetch paginated expenses with filters
        $expenseQuery = \App\Models\Expense::with('cashier')->latest();
        if (!empty($date)) {
            $expenseQuery->whereDate('created_at', $date);
        }
        if ($request->filled('branch')) {
            $expenseQuery->where('branch', $request->branch);
        }
        $expenses = $expenseQuery->paginate(20, ['*'], 'expense_page')->withQueryString();

        // Resolve target date (defaults to today)
        $targetDate = !empty($date) ? Carbon::parse($date) : Carbon::today();

        // Calculate time ranges based on the target date
        $today        = $targetDate->copy()->startOfDay();
        $startOfWeek  = $targetDate->copy()->startOfWeek();
        $endOfWeek    = $targetDate->copy()->endOfWeek();
        $startOfMonth = $targetDate->copy()->startOfMonth();
        $endOfMonth   = $targetDate->copy()->endOfMonth();

        // Apply branch filter to stats if specified
        $statsQuery = fn() => $request->filled('branch')
            ? Transaction::where('branch', $request->branch)
            : Transaction::query();

        $weeklyStats = $statsQuery()
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit')
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->first();

        $monthlyStats = $statsQuery()
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit')
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->first();

        $todayStats = $statsQuery()
            ->selectRaw('SUM(total_price) as omset, 
                         SUM(total_price - total_cost) as profit,
                         COALESCE(SUM(CASE WHEN payment_method = \'Cash\' THEN total_price ELSE 0 END),0) as cash_total,
                         COALESCE(SUM(CASE WHEN payment_method = \'QRIS\' THEN total_price ELSE 0 END),0) as qris_total,
                         COALESCE(SUM(CASE WHEN payment_method = \'Debit\' THEN total_price ELSE 0 END),0) as debit_total')
            ->whereDate('created_at', $today)
            ->first();

        $weeklyOmset   = $weeklyStats->omset ?? 0;
        $weeklyProfit  = $weeklyStats->profit ?? 0;
        $monthlyOmset  = $monthlyStats->omset ?? 0;
        $monthlyProfit = $monthlyStats->profit ?? 0;
        $todayOmset    = $todayStats->omset ?? 0;
        $todayProfit   = $todayStats->profit ?? 0;
        $todayCash     = $todayStats->cash_total ?? 0;
        $todayQris     = $todayStats->qris_total ?? 0;
        $todayDebit    = $todayStats->debit_total ?? 0;

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
        $activeBranch = $request->input('branch', '');

        return view('owner.transactions', compact(
            'transactions',
            'expenses',
            'weeklyOmset',
            'weeklyProfit',
            'monthlyOmset',
            'monthlyProfit',
            'todayOmset',
            'todayProfit',
            'todayCash',
            'todayQris',
            'todayDebit',
            'targetDate',
            'startOfWeek',
            'endOfWeek',
            'branches',
            'activeBranch'
        ));
    }

    /**
     * Store a newly completed transaction in storage.
     * Automatically copies the cashier's branch to the transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'total_price' => 'required|integer|min:0',
            'discount' => 'nullable|integer|min:0',
            'payment_method' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.price_unit' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $itemsSummary = collect($request->items)->map(function ($item) {
            return $item['name'] . ' (' . $item['qty'] . ' ' . $item['price_unit'] . ' x ' . $item['price'] . ')';
        })->join(', ');

        $totalCost = 0;
        foreach ($request->items as $item) {
            $product = null;
            if (isset($item['id'])) {
                $product = \App\Models\Product::find($item['id']);
            } else {
                $product = \App\Models\Product::where('name', $item['name'])->first();
            }

            if ($product) {
                $totalCost += round($product->cost_price * floatval($item['qty']));
                
                // Decrement product stock in the database upon purchase
                $product->decrement('stock', floatval($item['qty']));
            }
        }

        // Automatically inherit cashier's branch
        $cashierBranch = auth()->user()->branch ?? 'Pusat Cianjur';

        $transaction = Transaction::create([
            'cashier_id'       => auth()->id(),
            'transaction_code' => 'TRX-' . Carbon::now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
            'items_summary'    => $itemsSummary,
            'total_price'      => $request->total_price,
            'discount'         => $request->input('discount', 0),
            'total_cost'       => $totalCost,
            'payment_method'   => $request->payment_method,
            'branch'           => $cashierBranch,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dicatat!',
            'transaction' => [
                'id'               => $transaction->id,
                'time'             => $transaction->created_at->format('H:i'),
                'transaction_code' => $transaction->transaction_code,
                'items_summary'    => $transaction->items_summary,
                'payment_method'   => $transaction->payment_method,
                'total_price'      => $transaction->total_price,
                'discount'         => $transaction->discount,
            ]
        ]);
    }

    /**
     * Update a transaction (Admin only).
     */
    public function update(Request $request, Transaction $transaction)
    {
        $validated = $request->validate([
            'items_summary'  => 'required|string|max:1000',
            'total_price'    => 'required|integer|min:0',
            'payment_method' => 'required|string|in:Cash,QRIS,Debit',
        ]);

        $transaction->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diperbarui!',
        ]);
    }

    /**
     * Delete a transaction (Admin only).
     */
    public function destroy(Transaction $transaction)
    {
        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil dihapus!',
        ]);
    }

    /**
     * Format a number as Indonesian Rupiah string.
     */
    private function rupiah(int $amount): string
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    /**
     * Export all/filtered transactions as Excel-compatible file.
     */
    public function export(Request $request)
    {
        $query = Transaction::with('cashier')->latest();

        $date = $request->has('date') ? $request->input('date') : Carbon::today()->toDateString();
        if (!empty($date)) {
            $query->whereDate('created_at', $date);
        }

        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->get();
        $now = Carbon::now();

        $branchSuffix = $request->filled('branch') ? '_' . str_replace(' ', '_', $request->branch) : '';
        $filename = 'Riwayat_Transaksi' . $branchSuffix . '_' . (!empty($date) ? $date : $now->format('Y-m-d')) . '.xls';

        return response()->streamDownload(function () use ($transactions, $request, $now) {
            $printDate    = $now->translatedFormat('d F Y - H:i');
            $printedBy    = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';
            $filterDate   = $request->filled('date') ? Carbon::parse($request->date)->translatedFormat('d F Y') : 'Semua Tanggal';
            $filterBranch = $request->filled('branch') ? $request->branch : 'Semua Cabang';

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<style>';
            echo 'body{font-family:"Segoe UI",Arial,sans-serif;}';
            echo 'table{border-collapse:collapse;}';
            echo 'th{background-color:#059669;color:#ffffff;font-weight:bold;border:1px solid #047857;padding:10px 12px;font-size:11pt;white-space:nowrap;}';
            echo 'td{border:1px solid #d1fae5;padding:8px 10px;font-size:10pt;vertical-align:middle;}';
            echo '.title{font-size:16pt;font-weight:bold;color:#064e3b;text-align:center;border:none !important;}';
            echo '.subtitle{font-size:12pt;font-weight:bold;color:#10b981;text-align:center;border:none !important;}';
            echo '.meta-label{font-weight:bold;color:#374151;background-color:#f0fdf4;width:160px;border:1px solid #d1fae5;}';
            echo '.meta-value{color:#1f2937;border:1px solid #d1fae5;}';
            echo '.text{mso-number-format:"\@";text-align:left;}';
            echo '.center{text-align:center;}';
            echo '.right{text-align:right;}';
            echo '.bold{font-weight:bold;}';
            echo '.currency{text-align:right;font-weight:bold;color:#065f46;}';
            echo '.total-row{background-color:#d1fae5;font-weight:bold;color:#064e3b;}';
            echo '.avg-row{background-color:#ecfdf5;font-weight:bold;color:#065f46;}';
            echo '.count-row{background-color:#f9fafb;}';
            echo '.spacer{border:none !important;}';
            echo '</style></head><body>';

            // ---- Metadata Table ----
            echo '<table style="margin-bottom:16px;">';
            echo '<tr><td colspan="9" class="title" style="height:45px;">LAPORAN RIWAYAT TRANSAKSI PENJUALAN</td></tr>';
            echo '<tr><td colspan="9" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
            echo '<tr><td colspan="9" class="spacer" style="height:12px;"></td></tr>';
            echo '<tr><td class="meta-label">Filter Tanggal</td><td colspan="8" class="meta-value">' . htmlspecialchars($filterDate) . '</td></tr>';
            echo '<tr><td class="meta-label">Filter Cabang</td><td colspan="8" class="meta-value">' . htmlspecialchars($filterBranch) . '</td></tr>';
            echo '<tr><td class="meta-label">Tanggal Cetak</td><td colspan="8" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
            echo '<tr><td class="meta-label">Dicetak Oleh</td><td colspan="8" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
            echo '<tr><td colspan="9" class="spacer" style="height:12px;"></td></tr>';
            echo '</table>';

            // ---- Data Table ----
            echo '<table>';
            echo '<thead><tr>';
            echo '<th style="width:45px;">No</th>';
            echo '<th style="width:170px;">Tanggal &amp; Waktu</th>';
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

            foreach ($transactions as $trx) {
                $omset  = (int)$trx->total_price;
                $profit = (int)($trx->total_price - $trx->total_cost);
                $totalOmset  += $omset;
                $totalProfit += $profit;
                $rowBg = $idx % 2 === 0 ? ' style="background-color:#f9fafb;"' : '';

                echo '<tr' . $rowBg . '>';
                echo '<td class="center">' . $idx++ . '</td>';
                echo '<td class="center">' . htmlspecialchars($trx->created_at->translatedFormat('d M Y - H:i')) . '</td>';
                echo '<td class="center bold text">' . htmlspecialchars($trx->transaction_code) . '</td>';
                echo '<td>' . htmlspecialchars($trx->cashier->name ?? 'N/A') . '</td>';
                echo '<td class="center">' . htmlspecialchars($trx->branch ?? 'Pusat Cianjur') . '</td>';
                echo '<td class="text">' . htmlspecialchars($trx->items_summary) . '</td>';
                echo '<td class="center">' . htmlspecialchars($trx->payment_method) . '</td>';
                echo '<td class="currency">' . $this->rupiah($omset) . '</td>';
                echo '<td class="currency">' . $this->rupiah($profit) . '</td>';
                echo '</tr>';
            }

            $count     = count($transactions);
            $avgOmset  = $count > 0 ? (int)round($totalOmset / $count) : 0;
            $avgProfit = $count > 0 ? (int)round($totalProfit / $count) : 0;

            echo '<tr><td colspan="9" class="spacer" style="height:8px;"></td></tr>';

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
            'Content-Type'  => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    /**
     * Print transaction receipt for Kasir.
     */
    public function print(Transaction $transaction)
    {
        // Security check: Only cashier who made the transaction or admin can print
        if (!auth()->user()->isAdmin() && $transaction->cashier_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak struk ini.');
        }

        // Parse items from items_summary
        $items = [];
        if (!empty($transaction->items_summary)) {
            $parts = explode(', ', $transaction->items_summary);
            foreach ($parts as $part) {
                // Try to parse format with explicit price: Name (Qty Unit x Price)
                if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*(\w+)\s*x\s*(\d+(?:\.\d+)?)\)$/', $part, $matches)) {
                    $name = trim($matches[1]);
                    $qty = floatval($matches[2]);
                    $unit = $matches[3];
                    $unitPrice = floatval($matches[4]);
                    
                    if ($unit === 'gram' && $qty >= 1000) {
                        $qty = $qty / 1000;
                        $unit = 'kg';
                        $unitPrice = $unitPrice * 1000;
                    }
                    
                    $items[] = [
                        'name' => $name,
                        'qty' => $qty,
                        'unit' => $unit,
                        'unit_price' => $unitPrice,
                        'total_price' => round(($unitPrice * $qty) / 500) * 500,
                    ];
                } elseif (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*(\w+)\)$/', $part, $matches)) {
                    // Fallback for old transactions: Name (Qty Unit)
                    $name = trim($matches[1]);
                    $qty = floatval($matches[2]);
                    $unit = $matches[3];
                    
                    // Attempt to fetch current product details for unit price estimation
                    $product = \App\Models\Product::where('name', $name)->first();
                    $unitPrice = $product ? $product->getPriceForQuantity($qty) : null;
                    
                    if ($unit === 'gram' && $qty >= 1000) {
                        $qty = $qty / 1000;
                        $unit = 'kg';
                        if ($unitPrice !== null) {
                            $unitPrice = $unitPrice * 1000;
                        }
                    }
                    
                    $items[] = [
                        'name' => $name,
                        'qty' => $qty,
                        'unit' => $unit,
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice ? round(($unitPrice * $qty) / 500) * 500 : null,
                    ];
                } else {
                    $name = trim($part);
                    $product = \App\Models\Product::where('name', $name)->first();
                    $unitPrice = $product ? $product->getPriceForQuantity(1) : null;
                    
                    $items[] = [
                        'name' => $name,
                        'qty' => 1,
                        'unit' => 'pcs',
                        'unit_price' => $unitPrice,
                        'total_price' => $unitPrice ? round($unitPrice / 500) * 500 : null,
                    ];
                }
            }
        }

        return view('kasir.receipt', compact('transaction', 'items'));
    }

    /**
     * Store a newly created wholesale transaction (Admin only).
     */
    public function storeWholesale(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'payment_method' => 'required|string',
            'discount' => 'nullable|integer|min:0',
            'shipping_cost' => 'nullable|integer|min:0',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.price_unit' => 'required|string',
            'items.*.selling_price' => 'required|integer|min:0',
            'items.*.cost_price' => 'nullable|integer|min:0',
        ]);

        $totalPrice = 0;
        $totalCost = 0;
        
        $itemsSummary = collect($request->items)->map(function ($item) use (&$totalPrice, &$totalCost) {
            $qty = floatval($item['qty']);
            $sellPrice = intval($item['selling_price']);
            $costPrice = isset($item['cost_price']) && $item['cost_price'] !== '' ? intval($item['cost_price']) : 0;
            
            $totalPrice += round($sellPrice * $qty);
            $totalCost += round($costPrice * $qty);
            
            return $item['name'] . ' (' . $qty . ' ' . $item['price_unit'] . ' x ' . $sellPrice . ')';
        })->join(', ');

        $discount = intval($request->input('discount', 0));
        $shippingCost = intval($request->input('shipping_cost', 0));
        $grandTotal = max(0, $totalPrice - $discount + $shippingCost);

        // Deduct stock if the product exists in DB
        foreach ($request->items as $item) {
            $product = \App\Models\Product::where('name', $item['name'])->first();
            if ($product) {
                $product->decrement('stock', floatval($item['qty']));
            }
        }

        $transaction = Transaction::create([
            'cashier_id'       => auth()->id(),
            'transaction_code' => 'PRT-' . Carbon::now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
            'transaction_type' => 'wholesale',
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'items_summary'    => $itemsSummary,
            'total_price'      => $grandTotal,
            'discount'         => $discount,
            'shipping_cost'    => $shippingCost,
            'total_cost'       => $totalCost,
            'payment_method'   => $request->payment_method,
            'branch'           => 'Pusat Cianjur', // Defaults to Pusat
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Nota partai berhasil dicatat!',
            'transaction' => $transaction
        ]);
    }

    /**
     * Print wholesale transaction receipt.
     */
    public function printWholesale(Transaction $transaction)
    {
        // Security check: Only admin or owner can view wholesale invoices
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak nota ini.');
        }

        // Parse items from items_summary
        $items = [];
        if (!empty($transaction->items_summary)) {
            $parts = explode(', ', $transaction->items_summary);
            foreach ($parts as $part) {
                // Format: Name (Qty Unit x Price)
                if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z\s]+)\s*x\s*(\d+)\)$/', $part, $matches)) {
                    $name = trim($matches[1]);
                    $qty = floatval($matches[2]);
                    $unit = trim($matches[3]);
                    $price = intval($matches[4]);
                    
                    $items[] = [
                        'name' => $name,
                        'qty' => $qty,
                        'unit' => $unit,
                        'unit_price' => $price,
                        'total_price' => round($price * $qty),
                    ];
                } else if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z\s]+)\)$/', $part, $matches)) {
                    $name = trim($matches[1]);
                    $qty = floatval($matches[2]);
                    $unit = trim($matches[3]);
                    
                    $items[] = [
                        'name' => $name,
                        'qty' => $qty,
                        'unit' => $unit,
                        'unit_price' => 0,
                        'total_price' => 0,
                    ];
                } else {
                    $items[] = [
                        'name' => trim($part),
                        'qty' => 1,
                        'unit' => 'pcs',
                        'unit_price' => 0,
                        'total_price' => 0,
                    ];
                }
            }
        }

        // Calculate actual subtotal before discount/shipping if items have prices
        $subtotal = collect($items)->sum('total_price');

        return view('admin.wholesale_receipt', compact('transaction', 'items', 'subtotal'));
    }
}
