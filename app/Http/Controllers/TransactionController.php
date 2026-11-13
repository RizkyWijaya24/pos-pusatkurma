<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\User;
use App\Models\InstallmentPayment;
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

        $todayStart = Carbon::today()->startOfDay();
        $todayEnd   = Carbon::today()->endOfDay();

        // ---- Daily (paginated list) ----
        $transactions = Transaction::where('cashier_id', $cashierId)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->latest('id')
            ->paginate(15);

        // ---- Today totals ----
        $todayTrxList = Transaction::where('cashier_id', $cashierId)
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->get();

        $omsetSum = 0;
        $profitSum = 0;
        $cashSum = 0;
        $qrisSum = 0;
        $debitSum = 0;

        foreach ($todayTrxList as $trx) {
            $omsetSum  += (float) $trx->total_price;
            $profitSum += (float) ($trx->total_price - $trx->total_cost);
            $bd = $trx->payment_breakdown;
            $cashSum  += $bd['cash'];
            $qrisSum  += $bd['qris'];
            $debitSum += $bd['debit'];
        }

        $todayStats = (object) [
            'omset'       => $omsetSum,
            'profit'      => $profitSum,
            'count'       => $todayTrxList->count(),
            'cash_total'  => $cashSum,
            'qris_total'  => $qrisSum,
            'debit_total' => $debitSum,
        ];

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
        $isPdf     = $request->query('format') === 'pdf';

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
            $renderTemplate = function () use ($days, $titlePeriod, $printDate, $printedBy, $cashier, $isKasir, $isPdf) {
                $this->streamKasirWeekly($days, $titlePeriod, $printDate, $printedBy, $cashier->name, $isKasir, $isPdf);
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
                    return $pdf->download(str_replace('.xls', '.pdf', $filename));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Kasir Weekly PDF export failed: ' . $e->getMessage());
                    return response('Gagal mengekspor PDF Mingguan: ' . $e->getMessage(), 500);
                }
            }

            return response()->streamDownload($renderTemplate, $filename, [
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
            $renderTemplate = function () use ($weeks, $titlePeriod, $printDate, $printedBy, $cashier, $isKasir, $isPdf) {
                $this->streamKasirMonthly($weeks, $titlePeriod, $printDate, $printedBy, $cashier->name, $isKasir, $isPdf);
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
                    return $pdf->download(str_replace('.xls', '.pdf', $filename));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Kasir Weekly PDF export failed: ' . $e->getMessage());
                    return response('Gagal mengekspor PDF Mingguan: ' . $e->getMessage(), 500);
                }
            }

            return response()->streamDownload($renderTemplate, $filename, [
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
    private function streamKasirWeekly(array $days, string $period, string $printDate, string $printedBy, string $cashierName, bool $isKasir = false, bool $isPdf = false): void
    {
        $styles = $this->kasirExcelStyles($isPdf);
        $colspan = $isKasir ? 5 : 6;

        $totalOmset  = 0;
        $totalProfit = 0;
        $totalCount  = 0;
        foreach ($days as $day) {
            $totalOmset  += $day['omset'];
            $totalProfit += $day['profit'];
            $totalCount  += $day['count'];
        }

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>' . $styles . '</style></head><body>';
        
        if ($isPdf) {
            echo $this->pdfHeader('LAPORAN PENJUALAN MINGGUAN KASIR');
            
            $cards = [
                ['label' => 'Total Omset', 'value' => $this->rupiah($totalOmset), 'color' => '#059669'],
            ];
            if (!$isKasir) {
                $cards[] = ['label' => 'Profit Bersih', 'value' => $this->rupiah($totalProfit), 'color' => '#2563eb'];
            }
            $cards[] = ['label' => 'Total Transaksi', 'value' => $totalCount . ' Trx', 'color' => '#d97706'];
            
            echo $this->pdfCards($cards);
        }

        echo '<table style="margin-bottom:16px; width:100%;">';
        if (!$isPdf) {
            echo '<tr><td colspan="' . $colspan . '" class="title" style="height:45px;">LAPORAN PENJUALAN MINGGUAN KASIR</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="spacer"></td></tr>';
        }
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

        $idx = 1;
        foreach ($days as $dateStr => $day) {
            $rowClass     = $idx % 2 === 0 ? ' class="stripe"' : '';
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
        echo '</tbody></table>';

        if ($isPdf) {
            echo $this->pdfSignature($printedBy);
            echo $this->pdfFooter();
        }

        echo '</body></html>';
    }

    /**
     * Stream monthly kasir summary as Excel HTML.
     */
    private function streamKasirMonthly(array $weeks, string $period, string $printDate, string $printedBy, string $cashierName, bool $isKasir = false, bool $isPdf = false): void
    {
        $styles = $this->kasirExcelStyles($isPdf);
        $colspan = $isKasir ? 5 : 6;

        $totalOmset  = 0;
        $totalProfit = 0;
        $totalCount  = 0;
        foreach ($weeks as $w) {
            $totalOmset  += $w['omset'];
            $totalProfit += $w['profit'];
            $totalCount  += $w['count'];
        }

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
        echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style>' . $styles . '</style></head><body>';
        
        if ($isPdf) {
            echo $this->pdfHeader('LAPORAN PENJUALAN BULANAN KASIR');
            
            $cards = [
                ['label' => 'Total Omset', 'value' => $this->rupiah($totalOmset), 'color' => '#059669'],
            ];
            if (!$isKasir) {
                $cards[] = ['label' => 'Profit Bersih', 'value' => $this->rupiah($totalProfit), 'color' => '#2563eb'];
            }
            $cards[] = ['label' => 'Total Transaksi', 'value' => $totalCount . ' Trx', 'color' => '#d97706'];
            
            echo $this->pdfCards($cards);
        }

        echo '<table style="margin-bottom:16px; width:100%;">';
        if (!$isPdf) {
            echo '<tr><td colspan="' . $colspan . '" class="title" style="height:45px;">LAPORAN PENJUALAN BULANAN KASIR</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
            echo '<tr><td colspan="' . $colspan . '" class="spacer"></td></tr>';
        }
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

        $idx = 1;
        foreach ($weeks as $wNum => $w) {
            $rowClass     = $idx % 2 === 0 ? ' class="stripe"' : '';
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
        echo '</tbody></table>';

        if ($isPdf) {
            echo $this->pdfSignature($printedBy);
            echo $this->pdfFooter();
        }

        echo '</body></html>';
    }

    /**
     * Excel styles for kasir export.
     */
    private function kasirExcelStyles(bool $isPdf = false): string
    {
        $extraStyles = $isPdf ? '@page { margin: 1.2cm 1.0cm; } table { width: 100%; margin: 0 auto; } th { width: auto !important; font-size: 8.5pt !important; padding: 6px 4px !important; } td { font-size: 8pt !important; padding: 5px 4px !important; } ' : '';
        return implode('', [
            $extraStyles,
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
        $filterType = $request->input('filter_type', 'harian');

        if (!empty($date)) {
            $carbonDate = Carbon::parse($date);
            if ($filterType === 'mingguan') {
                $start = $carbonDate->copy()->startOfWeek();
                $end = $carbonDate->copy()->endOfWeek();
                $query->whereBetween('created_at', [$start, $end]);
            } elseif ($filterType === 'bulanan') {
                $start = $carbonDate->copy()->startOfMonth();
                $end = $carbonDate->copy()->endOfMonth();
                $query->whereBetween('created_at', [$start, $end]);
            } else {
                $query->whereDate('created_at', $date);
            }
        }

        // Optional branch filter
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->paginate(20);

        // Fetch paginated expenses with filters
        $expenseQuery = \App\Models\Expense::with('cashier')->latest();
        if (!empty($date)) {
            $carbonDate = Carbon::parse($date);
            if ($filterType === 'mingguan') {
                $start = $carbonDate->copy()->startOfWeek();
                $end = $carbonDate->copy()->endOfWeek();
                $expenseQuery->whereBetween('created_at', [$start, $end]);
            } elseif ($filterType === 'bulanan') {
                $start = $carbonDate->copy()->startOfMonth();
                $end = $carbonDate->copy()->endOfMonth();
                $expenseQuery->whereBetween('created_at', [$start, $end]);
            } else {
                $expenseQuery->whereDate('created_at', $date);
            }
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

        $periodTrxs = $statsQuery();
        $carbonDate = Carbon::parse($date);
        if ($filterType === 'mingguan') {
            $periodTrxs->whereBetween('created_at', [$carbonDate->copy()->startOfWeek(), $carbonDate->copy()->endOfWeek()]);
        } elseif ($filterType === 'bulanan') {
            $periodTrxs->whereBetween('created_at', [$carbonDate->copy()->startOfMonth(), $carbonDate->copy()->endOfMonth()]);
        } else {
            $periodTrxs->whereDate('created_at', $today);
        }
        $periodTrxList = $periodTrxs->get();

        $todayOmset  = 0;
        $todayProfit = 0;
        $todayCash   = 0;
        $todayQris   = 0;
        $todayDebit  = 0;

        foreach ($periodTrxList as $trx) {
            $todayOmset  += (float) $trx->total_price;
            $todayProfit += (float) ($trx->total_price - $trx->total_cost);
            $bd = $trx->payment_breakdown;
            $todayCash  += $bd['cash'];
            $todayQris  += $bd['qris'];
            $todayDebit += $bd['debit'];
        }

        $weeklyOmset   = (float) ($weeklyStats->omset ?? 0);
        $weeklyProfit  = (float) ($weeklyStats->profit ?? 0);
        $monthlyOmset  = (float) ($monthlyStats->omset ?? 0);
        $monthlyProfit = (float) ($monthlyStats->profit ?? 0);

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
        $filterType = $request->input('filter_type', 'harian');

        if (!empty($date)) {
            $carbonDate = Carbon::parse($date);
            if ($filterType === 'mingguan') {
                $start = $carbonDate->copy()->startOfWeek();
                $end = $carbonDate->copy()->endOfWeek();
                $query->whereBetween('created_at', [$start, $end]);
            } elseif ($filterType === 'bulanan') {
                $start = $carbonDate->copy()->startOfMonth();
                $end = $carbonDate->copy()->endOfMonth();
                $query->whereBetween('created_at', [$start, $end]);
            } else {
                $query->whereDate('created_at', $date);
            }
        }

        // Optional branch filter
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->paginate(20);

        // Fetch paginated expenses with filters
        $expenseQuery = \App\Models\Expense::with('cashier')->latest();
        if (!empty($date)) {
            $carbonDate = Carbon::parse($date);
            if ($filterType === 'mingguan') {
                $start = $carbonDate->copy()->startOfWeek();
                $end = $carbonDate->copy()->endOfWeek();
                $expenseQuery->whereBetween('created_at', [$start, $end]);
            } elseif ($filterType === 'bulanan') {
                $start = $carbonDate->copy()->startOfMonth();
                $end = $carbonDate->copy()->endOfMonth();
                $expenseQuery->whereBetween('created_at', [$start, $end]);
            } else {
                $expenseQuery->whereDate('created_at', $date);
            }
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

        $periodTrxs = $statsQuery();
        $carbonDate = Carbon::parse($date);
        if ($filterType === 'mingguan') {
            $periodTrxs->whereBetween('created_at', [$carbonDate->copy()->startOfWeek(), $carbonDate->copy()->endOfWeek()]);
        } elseif ($filterType === 'bulanan') {
            $periodTrxs->whereBetween('created_at', [$carbonDate->copy()->startOfMonth(), $carbonDate->copy()->endOfMonth()]);
        } else {
            $periodTrxs->whereDate('created_at', $today);
        }
        $periodTrxList = $periodTrxs->get();

        $todayOmset  = 0;
        $todayProfit = 0;
        $todayCash   = 0;
        $todayQris   = 0;
        $todayDebit  = 0;

        foreach ($periodTrxList as $trx) {
            $todayOmset  += (float) $trx->total_price;
            $todayProfit += (float) ($trx->total_price - $trx->total_cost);
            $bd = $trx->payment_breakdown;
            $todayCash  += $bd['cash'];
            $todayQris  += $bd['qris'];
            $todayDebit += $bd['debit'];
        }

        $weeklyOmset   = (float) ($weeklyStats->omset ?? 0);
        $weeklyProfit  = (float) ($weeklyStats->profit ?? 0);
        $monthlyOmset  = (float) ($monthlyStats->omset ?? 0);
        $monthlyProfit = (float) ($monthlyStats->profit ?? 0);

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
     * Stok dikurangi dari cabang kasir (bukan global) via StockService.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'total_price' => 'required|integer|min:0',
            'discount' => 'nullable|integer|min:0',
            'payment_method' => 'required|string',
            'affiliate_id' => 'nullable|exists:affiliates,id',
            'pax_count' => 'nullable|integer|min:0',
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

        // Automatically inherit cashier's branch
        $cashierBranch = auth()->user()->branch ?? 'Cabang Rumah';

        // ── Pre-load semua produk dalam 1 query (fix N+1) ───────────────────
        $itemIds   = collect($request->items)->pluck('id')->filter()->values()->toArray();
        $itemNames = collect($request->items)->pluck('name')->values()->toArray();
        $productsById   = \App\Models\Product::whereIn('id', $itemIds)->get()->keyBy('id');
        $productsByName = \App\Models\Product::whereIn('name', $itemNames)
            ->when(!empty($itemIds), fn($q) => $q->whereNotIn('id', $itemIds))
            ->get()->keyBy('name');

        $resolveProduct = function ($item) use ($productsById, $productsByName) {
            if (!empty($item['id']) && $productsById->has($item['id'])) {
                return $productsById->get($item['id']);
            }
            return $productsByName->get($item['name']);
        };

        // Validate stock for each item (aggregated by product to avoid multi-item bypass)
        $aggregatedItems = [];
        foreach ($request->items as $item) {
            $product = $resolveProduct($item);
            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk "' . $item['name'] . '" tidak ditemukan di database.'
                ], 422);
            }
            if (isset($product->is_active) && !$product->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk "' . $product->name . '" sedang nonaktif dan tidak dapat ditransaksikan.'
                ], 422);
            }
            $pId = $product->id;
            if (!isset($aggregatedItems[$pId])) {
                $aggregatedItems[$pId] = [
                    'product' => $product,
                    'qty' => 0.0,
                ];
            }
            $aggregatedItems[$pId]['qty'] += floatval($item['qty']);
        }

        $branchLocation = \App\Models\StockLocation::findByBranchName($cashierBranch);
        foreach ($aggregatedItems as $agg) {
            $product = $agg['product'];
            $qtyRequested = $agg['qty'];
            
            $availableStock = $branchLocation 
                ? $product->getStockAtLocation($branchLocation->id) 
                : $product->stock;

            if ($availableStock < $qtyRequested) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok produk "' . $product->name . '" tidak mencukupi! Tersedia: ' . $availableStock . ' ' . $product->price_unit . ', diminta: ' . $qtyRequested . ' ' . $product->price_unit
                ], 422);
            }
        }

        // Hitung total cost menggunakan produk yang sudah di-preload
        $totalCost = 0;
        foreach ($request->items as $item) {
            $product = $resolveProduct($item);
            if ($product) {
                $totalCost += round($product->cost_price * floatval($item['qty']));
            }
        }

        // ── Bungkus dalam DB::transaction agar data selalu konsisten ─────────
        $transaction = \Illuminate\Support\Facades\DB::transaction(function () use (
            $request, $itemsSummary, $cashierBranch, $totalCost, $resolveProduct
        ) {
            $trx = Transaction::create([
                'cashier_id'       => auth()->id(),
                'affiliate_id'     => $request->input('affiliate_id'),
                'pax_count'        => $request->input('pax_count', 0),
                'transaction_code' => 'TRX-' . Carbon::now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
                'items_summary'    => $itemsSummary,
                'total_price'      => $request->total_price,
                'discount'         => $request->input('discount', 0),
                'total_cost'       => $totalCost,
                'payment_method'   => $request->payment_method,
                'branch'           => $cashierBranch,
            ]);

            // Hitung & catat potongan komisi dari omset ke komisi_ledger jika berafiliasi
            if ($request->filled('affiliate_id')) {
                app(\App\Services\AffiliateService::class)->recordCommission(
                    $trx,
                    $request->input('affiliate_id'),
                    (int) $request->input('pax_count', 0)
                );
            }

            // Kurangi stok per cabang kasir via StockService
            $stockService   = app(\App\Services\StockService::class);
            $branchLocation = \App\Models\StockLocation::findByBranchName($cashierBranch);

            foreach ($request->items as $item) {
                $product = $resolveProduct($item);

                if ($product) {
                    if ($branchLocation) {
                        // Gunakan StockService untuk kurangi stok cabang & catat log mutasi
                        try {
                            $stockService->deductSaleStock(
                                $product,
                                $branchLocation,
                                floatval($item['qty']),
                                $trx->id,
                                auth()->user()
                            );
                        } catch (\Exception $e) {
                            // Fallback ke global jika error
                            $product->decrement('stock', floatval($item['qty']));
                        }
                    } else {
                        // Fallback: lokasi cabang belum terdaftar di sistem
                        $product->decrement('stock', floatval($item['qty']));
                    }
                }
            }

            return $trx;
        });

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
                // items_sold: dipakai Alpine.js untuk update stok produk real-time tanpa refresh
                'items_sold'       => collect($request->items)
                    ->filter(fn($item) => !empty($item['id']))
                    ->map(fn($item) => [
                        'id'  => (int) $item['id'],
                        'qty' => (float) $item['qty'],
                    ])
                    ->values(),
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
            'payment_method' => 'required|string',
            'payment_status' => 'nullable|string|in:paid,unpaid,cancelled',
        ]);

        $oldStatus = $transaction->payment_status ?? 'paid';
        $newStatus = $validated['payment_status'] ?? $oldStatus;

        $transaction->update($validated);

        // Jika status diubah menjadi cancelled, kembalikan stok
        if ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            app(\App\Services\StockService::class)->restoreTransactionStock(
                $transaction,
                "Pengembalian stok: Status transaksi #{$transaction->transaction_code} diubah menjadi dibatalkan"
            );
            if ($transaction->komisiLedger) {
                $transaction->komisiLedger->delete();
            }
        } elseif ($oldStatus === 'cancelled' && in_array($newStatus, ['paid', 'unpaid'])) {
            // Jika diaktifkan kembali dari cancelled, potong ulang stok
            app(\App\Services\StockService::class)->reDeductTransactionStock(
                $transaction,
                "Potong ulang stok: Transaksi #{$transaction->transaction_code} diaktifkan kembali"
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Transaksi berhasil diperbarui!',
        ]);
    }

    /**
     * Cancel a transaction and return stock (Admin only).
     */
    public function cancel(Transaction $transaction)
    {
        if ($transaction->payment_status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => "Transaksi #{$transaction->transaction_code} sudah berstatus dibatalkan sebelumnya.",
            ], 422);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($transaction) {
            // 1. Kembalikan stok produk ke cabang terkait
            app(\App\Services\StockService::class)->restoreTransactionStock(
                $transaction,
                "Pengembalian stok: Transaksi #{$transaction->transaction_code} dibatalkan oleh admin"
            );

            // 2. Batalkan komisi afiliasi jika ada
            if ($transaction->komisiLedger) {
                $transaction->komisiLedger->delete();
            }

            // 3. Update status pembayaran menjadi cancelled
            $transaction->update(['payment_status' => 'cancelled']);
        });

        return response()->json([
            'success'        => true,
            'message'        => "Transaksi #{$transaction->transaction_code} berhasil dibatalkan dan stok produk telah dikembalikan!",
            'payment_status' => 'cancelled',
        ]);
    }

    /**
     * Delete a transaction (Admin only).
     * Stok dikembalikan secara otomatis.
     */
    public function destroy(Transaction $transaction)
    {
        $code = $transaction->transaction_code;

        // Kembalikan stok produk jika belum dibatalkan/dikembalikan sebelumnya
        app(\App\Services\StockService::class)->restoreTransactionStock(
            $transaction,
            "Pengembalian stok: Transaksi #{$code} dihapus oleh admin"
        );

        if ($transaction->komisiLedger) {
            $transaction->komisiLedger->delete();
        }

        $transaction->delete();

        return response()->json([
            'success' => true,
            'message' => "Transaksi #{$code} berhasil dihapus dan stok barang telah dikembalikan ke inventori!",
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
        $filterType = $request->input('filter_type', 'harian');
        if (!empty($date)) {
            $carbonDate = Carbon::parse($date);
            if ($filterType === 'mingguan') {
                $query->whereBetween('created_at', [$carbonDate->copy()->startOfWeek(), $carbonDate->copy()->endOfWeek()]);
            } elseif ($filterType === 'bulanan') {
                $query->whereBetween('created_at', [$carbonDate->copy()->startOfMonth(), $carbonDate->copy()->endOfMonth()]);
            } else {
                $query->whereDate('created_at', $date);
            }
        }

        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->get();
        $now = Carbon::now();
        $isPdf = $request->query('format') === 'pdf';

        $branchSuffix = $request->filled('branch') ? '_' . str_replace(' ', '_', $request->branch) : '';
        $filename = 'Riwayat_Transaksi' . $branchSuffix . '_' . (!empty($date) ? $date : $now->format('Y-m-d')) . '.xls';

        // Pre-calculate stats
        $totalTransactions = count($transactions);
        $totalOmset = 0;
        $totalProfit = 0;
        foreach ($transactions as $trx) {
            $totalOmset += (int)$trx->total_price;
            $totalProfit += (int)($trx->total_price - $trx->total_cost);
        }
        $avgOmset = $totalTransactions > 0 ? (int)round($totalOmset / $totalTransactions) : 0;

        $renderTemplate = function () use ($transactions, $request, $now, $isPdf, $totalTransactions, $totalOmset, $totalProfit, $avgOmset) {
            $printDate    = $now->translatedFormat('d F Y - H:i');
            $printedBy    = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';
            $filterDate   = 'Hari Ini';
            if ($request->filled('date')) {
                $carbonDate = Carbon::parse($request->date);
                if ($request->input('filter_type') === 'mingguan') {
                    $filterDate = 'Mingguan (' . $carbonDate->copy()->startOfWeek()->translatedFormat('d M') . ' - ' . $carbonDate->copy()->endOfWeek()->translatedFormat('d M Y') . ')';
                } elseif ($request->input('filter_type') === 'bulanan') {
                    $filterDate = 'Bulanan (' . $carbonDate->translatedFormat('F Y') . ')';
                } else {
                    $filterDate = $carbonDate->translatedFormat('d F Y');
                }
            }
            $filterBranch = $request->filled('branch') ? $request->branch : 'Semua Cabang';

            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<style>';
            if ($isPdf) {
                echo '@page { margin: 1.0cm 1.2cm; } table { width: 100%; margin: 0 auto; } th { width: auto !important; } ';
            }
            echo 'body{font-family:"Segoe UI",Arial,sans-serif;color:#1e293b;}';
            echo 'table{border-collapse:collapse;width:100%;}';
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

            if ($isPdf) {
                echo $this->pdfHeader('LAPORAN RIWAYAT TRANSAKSI PENJUALAN');
                
                $cards = [
                    ['label' => 'Total Omset', 'value' => $this->rupiah($totalOmset), 'color' => '#059669'],
                    ['label' => 'Profit Bersih', 'value' => $this->rupiah($totalProfit), 'color' => '#2563eb'],
                    ['label' => 'Total Transaksi', 'value' => $totalTransactions . ' Trx', 'color' => '#d97706'],
                    ['label' => 'Rata-rata / Trx', 'value' => $this->rupiah($avgOmset), 'color' => '#475569']
                ];
                echo $this->pdfCards($cards);
            }

            // ---- Metadata Table ----
            echo '<table style="margin-bottom:16px; width:100%;">';
            if (!$isPdf) {
                echo '<tr><td colspan="9" class="title" style="height:45px;">LAPORAN RIWAYAT TRANSAKSI PENJUALAN</td></tr>';
                echo '<tr><td colspan="9" class="subtitle" style="height:28px;">PUSAT KURMA PREMIUM</td></tr>';
                echo '<tr><td colspan="9" class="spacer" style="height:12px;"></td></tr>';
            }
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

            $totalOmsetCalculated  = 0;
            $totalProfitCalculated = 0;
            $idx = 1;

            foreach ($transactions as $trx) {
                $omset  = (int)$trx->total_price;
                $profit = (int)($trx->total_price - $trx->total_cost);
                $totalOmsetCalculated  += $omset;
                $totalProfitCalculated += $profit;
                $rowBg = $idx % 2 === 0 ? ' style="background-color:#f9fafb;"' : '';

                echo '<tr' . $rowBg . '>';
                echo '<td class="center">' . $idx++ . '</td>';
                echo '<td class="center">' . htmlspecialchars($trx->created_at->translatedFormat('d M Y - H:i')) . '</td>';
                echo '<td class="center bold text">' . htmlspecialchars($trx->transaction_code) . '</td>';
                echo '<td>' . htmlspecialchars($trx->cashier->name ?? 'N/A') . '</td>';
                echo '<td class="center">' . htmlspecialchars($trx->branch ?? 'Cabang Rumah') . '</td>';
                echo '<td class="text">' . htmlspecialchars($trx->items_summary) . '</td>';
                echo '<td class="center">' . htmlspecialchars($trx->payment_method) . '</td>';
                echo '<td class="currency">' . $this->rupiah($omset) . '</td>';
                echo '<td class="currency">' . $this->rupiah($profit) . '</td>';
                echo '</tr>';
            }

            $count     = count($transactions);
            $avgOmsetCalculated  = $count > 0 ? (int)round($totalOmsetCalculated / $count) : 0;
            $avgProfitCalculated = $count > 0 ? (int)round($totalProfitCalculated / $count) : 0;

            echo '<tr><td colspan="9" class="spacer" style="height:8px;"></td></tr>';

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
            @ini_set('memory_limit', '512M');
            @set_time_limit(180);
            try {
                ob_start();
                $renderTemplate();
                $html = ob_get_clean();

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                $pdf->setPaper('a4', 'landscape');
                return $pdf->download(str_replace('.xls', '.pdf', $filename));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Admin/Owner Transaction PDF export failed: ' . $e->getMessage());
                return response('Gagal mengekspor PDF Transaksi: ' . $e->getMessage(), 500);
            }
        }

        return response()->streamDownload($renderTemplate, $filename, [
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
        // Security check: Cashier who made the transaction, admin, or owner can print
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner() && $transaction->cashier_id !== auth()->id()) {
            abort(403, 'Anda tidak memiliki akses untuk mencetak nota ini.');
        }

        // Parse items from items_summary
        $items = [];
        if (!empty($transaction->items_summary)) {
            $parts = explode(', ', $transaction->items_summary);

            // ── Pre-load semua produk yang mungkin dibutuhkan dalam 1 query ──
            $namesToLookup = [];
            foreach ($parts as $part) {
                // Hanya items format lama yang butuh lookup produk (tanpa harga eksplisit)
                if (!preg_match('/^.*?\s*\(\d+(?:\.\d+)?\s*\w+\s*x\s*\d+(?:\.\d+)?\)$/', $part)) {
                    if (preg_match('/^(.*?)\s*\(\d+(?:\.\d+)?\s*\w+\)$/', $part, $m)) {
                        $namesToLookup[] = trim($m[1]);
                    } else {
                        $namesToLookup[] = trim($part);
                    }
                }
            }
            $preloadedProducts = !empty($namesToLookup)
                ? \App\Models\Product::whereIn('name', $namesToLookup)->get()->keyBy('name')
                : collect();

            foreach ($parts as $part) {
                // Try to parse format with explicit price: Name (Qty Unit x Price)
                if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*(\w+)\s*x\s*(\d+(?:\.\d+)?)\)$/', $part, $matches)) {
                    $name      = trim($matches[1]);
                    $qty       = floatval($matches[2]);
                    $unit      = $matches[3];
                    $unitPrice = floatval($matches[4]);

                    if ($unit === 'gram' && $qty >= 1000) {
                        $qty       = $qty / 1000;
                        $unit      = 'kg';
                        $unitPrice = $unitPrice * 1000;
                    }

                    $items[] = [
                        'name'        => $name,
                        'qty'         => $qty,
                        'unit'        => $unit,
                        'unit_price'  => $unitPrice,
                        'total_price' => round(($unitPrice * $qty) / 500) * 500,
                    ];
                } elseif (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*(\w+)\)$/', $part, $matches)) {
                    // Fallback for old transactions: Name (Qty Unit)
                    $name = trim($matches[1]);
                    $qty  = floatval($matches[2]);
                    $unit = $matches[3];

                    // Gunakan produk yang sudah di-preload (bukan query per item)
                    $product   = $preloadedProducts->get($name);
                    $unitPrice = $product ? $product->getPriceForQuantity($qty) : null;

                    if ($unit === 'gram' && $qty >= 1000) {
                        $qty  = $qty / 1000;
                        $unit = 'kg';
                        if ($unitPrice !== null) {
                            $unitPrice = $unitPrice * 1000;
                        }
                    }

                    $items[] = [
                        'name'        => $name,
                        'qty'         => $qty,
                        'unit'        => $unit,
                        'unit_price'  => $unitPrice,
                        'total_price' => $unitPrice ? round(($unitPrice * $qty) / 500) * 500 : null,
                    ];
                } else {
                    $name      = trim($part);
                    $product   = $preloadedProducts->get($name);
                    $unitPrice = $product ? $product->getPriceForQuantity(1) : null;

                    $items[] = [
                        'name'        => $name,
                        'qty'         => 1,
                        'unit'        => 'pcs',
                        'unit_price'  => $unitPrice,
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
            'payment_received' => 'nullable|integer|min:0',
            'skip_stock_deduction' => 'nullable|boolean',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.price_unit' => 'required|string',
            'items.*.selling_price' => 'required|integer|min:0',
            'items.*.cost_price' => 'nullable|integer|min:0',
        ]);

        $skipStock = $request->boolean('skip_stock_deduction');

        // Validate stock for each item (aggregated by product name to avoid multi-item bypass)
        $aggregatedItems = [];
        foreach ($request->items as $item) {
            $product = \App\Models\Product::where('name', $item['name'])->first();
            if (!$product && !$skipStock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk "' . $item['name'] . '" tidak ditemukan di database.'
                ], 422);
            }
            if ($product) {
                $pName = $product->name;
                if (!isset($aggregatedItems[$pName])) {
                    $aggregatedItems[$pName] = [
                        'product' => $product,
                        'qty' => 0.0,
                    ];
                }

                // Konversi satuan jika produk berbasis gram tetapi diorder dalam kg
                $itemUnit = strtolower(trim($item['price_unit'] ?? ''));
                $productUnit = strtolower(trim($product->price_unit ?? ''));
                $convertedQty = floatval($item['qty']);

                if ($productUnit === 'gram' && in_array($itemUnit, ['kg', 'kilogram'])) {
                    $convertedQty *= 1000;
                } elseif (in_array($productUnit, ['kg', 'kilogram']) && $itemUnit === 'gram') {
                    $convertedQty /= 1000;
                }

                $aggregatedItems[$pName]['qty'] += $convertedQty;
            }
        }

        if (!$skipStock) {
            $branchLocation = \App\Models\StockLocation::findByBranchName('Cabang Rumah');
            foreach ($aggregatedItems as $agg) {
                $product = $agg['product'];
                $qtyRequested = $agg['qty'];
                
                $availableStock = $branchLocation 
                    ? $product->getStockAtLocation($branchLocation->id) 
                    : $product->stock;

                if ($availableStock < $qtyRequested) {
                    $availFormatted = ($product->price_unit === 'gram' && $availableStock >= 1000)
                        ? ($availableStock / 1000) . ' kg (' . $availableStock . ' gram)'
                        : $availableStock . ' ' . $product->price_unit;
                    $reqFormatted = ($product->price_unit === 'gram' && $qtyRequested >= 1000)
                        ? ($qtyRequested / 1000) . ' kg (' . $qtyRequested . ' gram)'
                        : $qtyRequested . ' ' . $product->price_unit;

                    return response()->json([
                        'success' => false,
                        'message' => 'Stok produk "' . $product->name . '" tidak mencukupi! Tersedia: ' . $availFormatted . ', diminta: ' . $reqFormatted
                    ], 422);
                }
            }
        }

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

        if ($skipStock) {
            $itemsSummary .= ', [Stok Sudah Didata]';
        }

        $discount = intval($request->input('discount', 0));
        $shippingCost = intval($request->input('shipping_cost', 0));
        $grandTotal = max(0, $totalPrice - $discount + $shippingCost);

        $received = $request->payment_method === 'Piutang / Tempo' 
            ? intval($request->input('payment_received', 0)) 
            : $grandTotal;

        $paymentStatus = $request->payment_method === 'Piutang / Tempo' ? 'unpaid' : 'paid';
        if ($request->payment_method === 'Piutang / Tempo' && $received >= $grandTotal) {
            $paymentStatus = 'paid';
        }

        $trxCodePrefix = $skipStock ? 'PRT-NS-' : 'PRT-';
        $transaction = Transaction::create([
            'cashier_id'       => auth()->id(),
            'affiliate_id'     => $request->input('affiliate_id'),
            'pax_count'        => $request->input('pax_count', 0),
            'transaction_code' => $trxCodePrefix . Carbon::now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
            'transaction_type' => 'wholesale',
            'customer_name'    => $request->customer_name,
            'customer_phone'   => $request->customer_phone,
            'items_summary'    => $itemsSummary,
            'total_price'      => $grandTotal,
            'discount'         => $discount,
            'shipping_cost'    => $shippingCost,
            'total_cost'       => $totalCost,
            'payment_method'   => $request->payment_method,
            'branch'           => 'Cabang Rumah', // Defaults to Pusat
            'payment_status'   => $paymentStatus,
        ]);

        if (!$skipStock) {
            // Kurangi stok cabang & catat log mutasi via StockService
            $stockService = app(\App\Services\StockService::class);
            $wholesaleLocation = \App\Models\StockLocation::where('name', 'Cabang Rumah')->first()
                ?? \App\Models\StockLocation::gudang()->first()
                ?? \App\Models\StockLocation::first();

            // Pre-load seluruh produk terkait dalam 1 query (mencegah N+1 query ke database)
            $itemNames = collect($request->items)->pluck('name')->values()->toArray();
            $productsByName = \App\Models\Product::whereIn('name', $itemNames)->get()->keyBy('name');

            foreach ($request->items as $item) {
                $product = $productsByName->get($item['name']);
                if ($product) {
                    $itemUnit = strtolower(trim($item['price_unit'] ?? ''));
                    $productUnit = strtolower(trim($product->price_unit ?? ''));
                    $deductQty = floatval($item['qty']);

                    if ($productUnit === 'gram' && in_array($itemUnit, ['kg', 'kilogram'])) {
                        $deductQty *= 1000;
                    } elseif (in_array($productUnit, ['kg', 'kilogram']) && $itemUnit === 'gram') {
                        $deductQty /= 1000;
                    }

                    if ($wholesaleLocation) {
                        try {
                            $stockService->deductSaleStock(
                                $product,
                                $wholesaleLocation,
                                $deductQty,
                                $transaction->id,
                                auth()->user() ?? \App\Models\User::first(),
                                "Penjualan Nota Partai #{$transaction->transaction_code}"
                            );
                        } catch (\Exception $e) {
                            $product->decrement('stock', $deductQty);
                        }
                    } else {
                        $product->decrement('stock', $deductQty);
                    }
                }
            }
        }

        if ($request->filled('affiliate_id')) {
            app(\App\Services\AffiliateService::class)->recordCommission(
                $transaction,
                $request->input('affiliate_id'),
                (int) $request->input('pax_count', 0)
            );
        }

        if ($received > 0) {
            $transaction->installments()->create([
                'amount' => $received,
                'payment_method' => $request->payment_method === 'Piutang / Tempo' ? 'Tunai' : $request->payment_method,
                'note' => $request->payment_method === 'Piutang / Tempo' ? 'Uang Muka / DP' : 'Pembayaran Lunas Awal'
            ]);
        }

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
                $trimmed = trim($part);
                if (in_array($trimmed, ['[Stok Sudah Didata]', '[Tanpa Potong Stok]', '[Piutang Manual]'])) {
                    continue;
                }

                // Format: Name (Qty Unit x Price)
                if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z\s]+)\s*x\s*(\d+)\)$/', $part, $matches)) {
                    $name = trim($matches[1]);
                    $qty = floatval($matches[2]);
                    $unit = trim($matches[3]);
                    $price = intval($matches[4]);
                    
                    // Smart conversion untuk nota partai ke pelanggan:
                    // Jika satuan barang gram, konversikan ke kg agar faktur partai tampil profesional
                    $unitLower = strtolower($unit);
                    if (in_array($unitLower, ['gram', 'g', 'gr'])) {
                        $qty = $qty / 1000;
                        $unit = 'kg';
                        $price = $price * 1000;
                    }

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
                    
                    $unitLower = strtolower($unit);
                    if (in_array($unitLower, ['gram', 'g', 'gr'])) {
                        $qty = $qty / 1000;
                        $unit = 'kg';
                    }

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

        // Calculate installment totals
        $totalPaid = $transaction->installments()->sum('amount');
        $remaining = max(0, $transaction->total_price - $totalPaid);
        $settings = \App\Models\Setting::pluck('value', 'key')->all();

        if (request()->query('format') === 'pdf') {
            if (!extension_loaded('gd')) {
                return redirect()->to(url()->current())
                    ->with('error', 'Ekstensi PHP GD belum aktif di server. Menampilkan format cetak web (HTML).');
            }
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.wholesale_receipt_pdf', compact('transaction', 'items', 'subtotal', 'totalPaid', 'remaining', 'settings'));
            $pdf->setPaper('a4', 'portrait');
            return $pdf->stream('Faktur_' . $transaction->transaction_code . '.pdf');
        }

        return view('admin.wholesale_receipt', compact('transaction', 'items', 'subtotal', 'totalPaid', 'remaining', 'settings'));
    }

    /**
     * Private helper methods for formal PDF layout.
     */
    private function pdfHeader(string $title): string
    {
        $logoPath = public_path('images/logo.png');
        $logoHtml = '';
        if (file_exists($logoPath)) {
            try {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoHtml = '<img src="data:image/png;base64,' . $logoData . '" style="height: 50px; max-width: 100px; display: block;">';
            } catch (\Exception $e) {
                $logoHtml = '<div style="width:50px;height:50px;background-color:#059669;border-radius:8px;text-align:center;line-height:50px;font-size:16pt;font-weight:bold;color:#ffffff;">PK</div>';
            }
        } else {
            $logoHtml = '<div style="width:50px;height:50px;background-color:#059669;border-radius:8px;text-align:center;line-height:50px;font-size:16pt;font-weight:bold;color:#ffffff;">PK</div>';
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
     * Mark a transaction as paid (Admin only).
     */
    public function markAsPaid(Transaction $transaction)
    {
        // Security check
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'
            ], 403);
        }

        $transaction->update(['payment_status' => 'paid']);

        // Jika ada sisa piutang belum tercatat di installment, catat pelunasan otomatis agar sinkron
        $paidSoFar = $transaction->installments()->sum('amount');
        $remaining = $transaction->total_price - $paidSoFar;
        if ($remaining > 0) {
            $transaction->installments()->create([
                'amount' => $remaining,
                'payment_method' => 'Tunai',
                'note' => 'Pelunasan langsung via Nota Partai'
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nota partai berhasil ditandai LUNAS!'
        ]);
    }

    /**
     * Get all active wholesale receivables grouped by customer name (Admin/Owner only).
     */
    public function getReceivables()
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'
            ], 403);
        }

        $transactions = Transaction::where('transaction_type', 'wholesale')
            ->where('payment_method', 'Piutang / Tempo')
            ->where('payment_status', 'unpaid')
            ->with('installments')
            ->get();

        // Group by customer_name (case-insensitive and trimmed)
        $grouped = $transactions->groupBy(function ($item) {
            return strtolower(trim($item->customer_name));
        })->map(function ($group) {
            $first = $group->first();
            $totalOriginal = $group->sum('total_price');
            $totalPaid = $group->reduce(function ($carry, $trx) {
                return $carry + $trx->installments->sum('amount');
            }, 0);

            return [
                'customer_name' => $first->customer_name,
                'customer_phone' => $first->customer_phone,
                'total_original' => $totalOriginal,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOriginal - $totalPaid,
                'transactions' => $group->map(function ($trx) {
                    $paid = $trx->installments->sum('amount');
                    return [
                        'id' => $trx->id,
                        'transaction_code' => $trx->transaction_code,
                        'date' => $trx->created_at->format('d/m/Y H:i'),
                        'total_price' => $trx->total_price,
                        'paid_amount' => $paid,
                        'outstanding_amount' => $trx->total_price - $paid,
                        'items_summary' => $trx->items_summary,
                        'parsed_items' => $this->parseItemsSummary($trx->items_summary),
                        'installments' => $trx->installments->map(function ($inst) {
                            return [
                                'id' => $inst->id,
                                'amount' => $inst->amount,
                                'payment_method' => $inst->payment_method,
                                'note' => $inst->note,
                                'date' => $inst->created_at->format('d/m/Y H:i')
                            ];
                        })->values()
                    ];
                })->values()
            ];
        })->values();

        return response()->json($grouped);
    }

    /**
     * Get history of settled receivables and installment payments (Admin/Owner only).
     */
    public function getReceivablesHistory()
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'
            ], 403);
        }

        // 1. Transaksi wholesale berstatus Piutang / Tempo yang sudah lunas (atau memiliki cicilan)
        $historyTransactions = Transaction::where('transaction_type', 'wholesale')
            ->where(function ($q) {
                $q->where('payment_method', 'Piutang / Tempo')
                  ->orWhereHas('installments');
            })
            ->where('payment_status', 'paid')
            ->with(['installments' => function ($q) {
                $q->latest();
            }])
            ->latest()
            ->take(50)
            ->get();

        $history = $historyTransactions->map(function ($trx) {
            $totalPaid = $trx->installments->sum('amount');
            $lastPayment = $trx->installments->first();

            return [
                'id' => $trx->id,
                'transaction_code' => $trx->transaction_code,
                'customer_name' => $trx->customer_name,
                'customer_phone' => $trx->customer_phone,
                'date' => $trx->created_at->format('d/m/Y H:i'),
                'total_price' => (float) $trx->total_price,
                'total_paid' => (float) ($totalPaid > 0 ? $totalPaid : $trx->total_price),
                'is_paid' => true,
                'items_summary' => $trx->items_summary,
                'parsed_items' => $this->parseItemsSummary($trx->items_summary),
                'last_payment_date' => $lastPayment ? $lastPayment->created_at->format('d/m/Y H:i') : $trx->updated_at->format('d/m/Y H:i'),
                'installments_count' => $trx->installments->count(),
                'installments' => $trx->installments->map(function ($inst) {
                    return [
                        'id' => $inst->id,
                        'amount' => (float) $inst->amount,
                        'payment_method' => $inst->payment_method,
                        'note' => $inst->note,
                        'date' => $inst->created_at->format('d/m/Y H:i')
                    ];
                })->values()
            ];
        })->values();

        // 2. Seluruh log riwayat pembayaran cicilan masuk
        $logs = InstallmentPayment::with('transaction')
            ->latest()
            ->take(100)
            ->get()
            ->map(function ($inst) {
                return [
                    'id' => $inst->id,
                    'transaction_id' => $inst->transaction_id,
                    'transaction_code' => $inst->transaction ? $inst->transaction->transaction_code : '-',
                    'customer_name' => $inst->transaction ? $inst->transaction->customer_name : '-',
                    'amount' => (float) $inst->amount,
                    'payment_method' => $inst->payment_method,
                    'note' => $inst->note,
                    'date' => $inst->created_at->format('d/m/Y H:i'),
                    'is_settled' => $inst->transaction ? $inst->transaction->is_paid : true,
                ];
            });

        return response()->json([
            'success' => true,
            'history' => $history,
            'logs' => $logs
        ]);
    }

    /**
     * Store a new installment payment (Admin/Owner only).
     */
    public function storeInstallment(Request $request, Transaction $transaction)
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'
            ], 403);
        }

        $request->validate([
            'amount' => 'required|integer|min:1',
            'payment_method' => 'required|string',
            'note' => 'nullable|string|max:255',
        ]);

        $paidSoFar = $transaction->installments()->sum('amount');
        $remaining = $transaction->total_price - $paidSoFar;

        if ($request->amount > $remaining) {
            return response()->json([
                'success' => false,
                'message' => 'Jumlah pembayaran melebihi sisa tagihan! Sisa tagihan saat ini: Rp ' . number_format($remaining, 0, ',', '.')
            ], 422);
        }

        // Record installment
        $transaction->installments()->create([
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'note' => $request->note
        ]);

        // Check if fully paid
        $totalPaid = $transaction->installments()->sum('amount');
        $isPaid = false;
        if ($totalPaid >= $transaction->total_price) {
            $transaction->update(['payment_status' => 'paid']);
            $isPaid = true;
        }

        return response()->json([
            'success' => true,
            'is_paid' => $isPaid,
            'transaction_id' => $transaction->id,
            'message' => 'Pembayaran cicilan berhasil disimpan!'
        ]);
    }

    /**
     * Parse items_summary string into structured array of items with formatted quantities and prices.
     */
    protected function parseItemsSummary(?string $summary): array
    {
        $items = [];
        if (empty($summary)) {
            return $items;
        }

        $parts = explode(', ', $summary);
        foreach ($parts as $part) {
            $trimmed = trim($part);
            if (in_array($trimmed, ['[Stok Sudah Didata]', '[Tanpa Potong Stok]', '[Piutang Manual]'])) {
                continue;
            }

            if (preg_match('/^(.*?)\s*\((\d+(?:\.\d+)?)\s*([a-zA-Z\s]+)\s*x\s*(\d+)\)$/', $part, $matches)) {
                $name = trim($matches[1]);
                $qty = floatval($matches[2]);
                $unit = trim($matches[3]);
                $price = intval($matches[4]);

                $displayQty = (round($qty, 2) == round($qty) ? (int)$qty : number_format($qty, 2, ',', '.')) . ' ' . $unit;
                if (strtolower($unit) === 'gram' && $qty >= 1000) {
                    $kg = $qty / 1000;
                    $displayQty = (round($kg, 2) == round($kg) ? (int)$kg : number_format($kg, 2, ',', '.')) . ' kg (' . number_format($qty, 0, ',', '.') . ' g)';
                }

                $items[] = [
                    'name' => $name,
                    'qty' => $qty,
                    'unit' => $unit,
                    'display_qty' => $displayQty,
                    'unit_price' => $price,
                    'total_price' => (float) ($qty * $price),
                ];
            } else {
                $items[] = [
                    'name' => trim($part),
                    'qty' => 1,
                    'unit' => 'item',
                    'display_qty' => '1 item',
                    'unit_price' => 0,
                    'total_price' => 0,
                ];
            }
        }

        return $items;
    }

    /**
     * Catat piutang manual (saldo awal / barang sudah keluar sebelumnya tanpa potong stok).
     */
    public function storeManualReceivable(Request $request)
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isOwner()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk melakukan tindakan ini.'
            ], 403);
        }

        $validated = $request->validate([
            'customer_name'    => 'required|string|max:255',
            'customer_phone'   => 'nullable|string|max:20',
            'description'      => 'required|string|max:500',
            'amount'           => 'required|numeric|min:1',
            'down_payment'     => 'nullable|numeric|min:0',
            'transaction_date' => 'nullable|date',
        ]);

        $grandTotal = intval($request->amount);
        $received = intval($request->input('down_payment', 0));
        $paymentStatus = $received >= $grandTotal ? 'paid' : 'unpaid';

        $txDate = $request->filled('transaction_date') 
            ? Carbon::parse($request->transaction_date) 
            : Carbon::now();

        $itemsSummary = trim($request->description) . ' (1 paket x ' . $grandTotal . '), [Stok Sudah Didata]';

        $transaction = Transaction::create([
            'cashier_id'       => auth()->id(),
            'transaction_code' => 'PTM-' . $txDate->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
            'transaction_type' => 'wholesale',
            'customer_name'    => trim($request->customer_name),
            'customer_phone'   => $request->customer_phone,
            'items_summary'    => $itemsSummary,
            'total_price'      => $grandTotal,
            'discount'         => 0,
            'shipping_cost'    => 0,
            'total_cost'       => 0,
            'payment_method'   => 'Piutang / Tempo',
            'branch'           => 'Cabang Rumah',
            'payment_status'   => $paymentStatus,
            'created_at'       => $txDate,
            'updated_at'       => $txDate,
        ]);

        if ($received > 0) {
            $transaction->installments()->create([
                'amount'         => $received,
                'payment_method' => 'Tunai',
                'note'           => 'Uang Muka / DP Awal',
                'created_at'     => $txDate,
            ]);
        }

        return response()->json([
            'success'     => true,
            'message'     => 'Data piutang berhasil dicatat tanpa memotong stok!',
            'transaction' => $transaction
        ]);
    }
}
