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
     */
    public function index()
    {
        $transactions = Transaction::where('cashier_id', auth()->id())
            ->latest()
            ->paginate(15);

        return view('kasir.transactions', compact('transactions'));
    }

    /**
     * Display ALL transactions for admin — with edit/delete capabilities.
     */
    public function adminIndex(Request $request)
    {
        $query = Transaction::with('cashier')->latest();

        // Optional date filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Optional branch filter
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->paginate(20);

        // Fetch paginated expenses with filters
        $expenseQuery = \App\Models\Expense::with('cashier')->latest();
        if ($request->filled('date')) {
            $expenseQuery->whereDate('created_at', $request->date);
        }
        if ($request->filled('branch')) {
            $expenseQuery->where('branch', $request->branch);
        }
        $expenses = $expenseQuery->paginate(20, ['*'], 'expense_page')->withQueryString();

        // Resolve target date (defaults to today)
        $targetDate = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

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
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit')
            ->whereDate('created_at', $today)
            ->first();

        $weeklyOmset   = $weeklyStats->omset ?? 0;
        $weeklyProfit  = $weeklyStats->profit ?? 0;
        $monthlyOmset  = $monthlyStats->omset ?? 0;
        $monthlyProfit = $monthlyStats->profit ?? 0;
        $todayOmset    = $todayStats->omset ?? 0;
        $todayProfit   = $todayStats->profit ?? 0;

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

        // Optional date filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // Optional branch filter
        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->paginate(20);

        // Fetch paginated expenses with filters
        $expenseQuery = \App\Models\Expense::with('cashier')->latest();
        if ($request->filled('date')) {
            $expenseQuery->whereDate('created_at', $request->date);
        }
        if ($request->filled('branch')) {
            $expenseQuery->where('branch', $request->branch);
        }
        $expenses = $expenseQuery->paginate(20, ['*'], 'expense_page')->withQueryString();

        // Resolve target date (defaults to today)
        $targetDate = $request->filled('date') ? Carbon::parse($request->date) : Carbon::today();

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
            ->selectRaw('SUM(total_price) as omset, SUM(total_price - total_cost) as profit')
            ->whereDate('created_at', $today)
            ->first();

        $weeklyOmset   = $weeklyStats->omset ?? 0;
        $weeklyProfit  = $weeklyStats->profit ?? 0;
        $monthlyOmset  = $monthlyStats->omset ?? 0;
        $monthlyProfit = $monthlyStats->profit ?? 0;
        $todayOmset    = $todayStats->omset ?? 0;
        $todayProfit   = $todayStats->profit ?? 0;

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
            'payment_method' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer',
            'items.*.name' => 'required|string',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.price_unit' => 'required|string',
        ]);

        $itemsSummary = collect($request->items)->map(function ($item) {
            return $item['name'] . ' (' . $item['qty'] . ' ' . $item['price_unit'] . ')';
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
            }
        }

        // Automatically inherit cashier's branch
        $cashierBranch = auth()->user()->branch ?? 'Pusat Cianjur';

        $transaction = Transaction::create([
            'cashier_id'       => auth()->id(),
            'transaction_code' => 'TRX-' . Carbon::now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -6)),
            'items_summary'    => $itemsSummary,
            'total_price'      => $request->total_price,
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
     * Export all/filtered transactions as Excel-compatible CSV.
     */
    public function export(Request $request)
    {
        $query = Transaction::with('cashier')->latest();

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        if ($request->filled('branch')) {
            $query->where('branch', $request->branch);
        }

        $transactions = $query->get();
        $now = Carbon::now();

        $branchSuffix = $request->filled('branch') ? '_' . str_replace(' ', '_', $request->branch) : '';
        $filename = 'Riwayat_Transaksi' . $branchSuffix . '_' . ($request->filled('date') ? $request->date : $now->format('Y-m-d')) . '.xls';

        return response()->streamDownload(function () use ($transactions, $request, $now) {
            $printDate = $now->translatedFormat('d F Y - H:i');
            $printedBy = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';
            $filterDate = $request->filled('date') ? Carbon::parse($request->date)->translatedFormat('d F Y') : 'Semua Tanggal';
            $filterBranch = $request->filled('branch') ? $request->branch : 'Semua Cabang';

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

            // Metadata Table
            echo '<table>';
            echo '  <tr><td colspan="9" class="title" style="height: 40px; text-align: center; font-size: 16pt; font-weight: bold;">LAPORAN RIWAYAT TRANSAKSI PENJUALAN</td></tr>';
            echo '  <tr><td colspan="9" class="title" style="height: 25px; text-align: center; font-size: 12pt; font-weight: bold; color: #10b981;">PUSAT KURMA PREMIUM</td></tr>';
            echo '  <tr><td colspan="9" style="height: 15px; border:none;"></td></tr>';
            echo '  <tr><td class="meta-label">Filter Tanggal</td><td colspan="8" class="meta-value">' . htmlspecialchars($filterDate) . '</td></tr>';
            echo '  <tr><td class="meta-label">Filter Cabang</td><td colspan="8" class="meta-value">' . htmlspecialchars($filterBranch) . '</td></tr>';
            echo '  <tr><td class="meta-label">Tanggal Cetak</td><td colspan="8" class="meta-value">' . htmlspecialchars($printDate) . '</td></tr>';
            echo '  <tr><td class="meta-label">Dicetak Oleh</td><td colspan="8" class="meta-value">' . htmlspecialchars($printedBy) . '</td></tr>';
            echo '  <tr><td colspan="9" style="height: 20px; border:none;"></td></tr>';
            echo '</table>';

            // Main Data Table
            echo '<table>';
            echo '  <thead>';
            echo '    <tr>';
            echo '      <th style="width: 50px;">No</th>';
            echo '      <th style="width: 160px;">Tanggal & Waktu</th>';
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

            foreach ($transactions as $trx) {
                $omset = (int)$trx->total_price;
                $profit = (int)($trx->total_price - $trx->total_cost);
                $totalOmset += $omset;
                $totalProfit += $profit;

                echo '    <tr>';
                echo '      <td class="center">' . $idx++ . '</td>';
                echo '      <td class="center">' . htmlspecialchars($trx->created_at->translatedFormat('d M Y - H:i')) . '</td>';
                echo '      <td class="center bold text">' . htmlspecialchars($trx->transaction_code) . '</td>';
                echo '      <td>' . htmlspecialchars($trx->cashier->name ?? 'N/A') . '</td>';
                echo '      <td>' . htmlspecialchars($trx->branch ?? 'Pusat Cianjur') . '</td>';
                echo '      <td class="text">' . htmlspecialchars($trx->items_summary) . '</td>';
                echo '      <td class="center">' . htmlspecialchars($trx->payment_method) . '</td>';
                echo '      <td class="currency">' . $omset . '</td>';
                echo '      <td class="currency">' . $profit . '</td>';
                echo '    </tr>';
            }

            // Calculations
            $count = count($transactions);
            $avgOmset = $count > 0 ? round($totalOmset / $count) : 0;
            $avgProfit = $count > 0 ? round($totalProfit / $count) : 0;

            // Empty row spacer
            echo '    <tr><td colspan="9" style="height: 10px; border:none;"></td></tr>';

            // Grand Total Row
            echo '    <tr class="total-row">';
            echo '      <td colspan="7" class="center bold">GRAND TOTAL</td>';
            echo '      <td class="currency">' . $totalOmset . '</td>';
            echo '      <td class="currency">' . $totalProfit . '</td>';
            echo '    </tr>';

            // Average Row
            echo '    <tr class="total-row" style="background-color: #f0fdf4;">';
            echo '      <td colspan="7" class="center bold">RATA-RATA</td>';
            echo '      <td class="currency">' . $avgOmset . '</td>';
            echo '      <td class="currency">' . $avgProfit . '</td>';
            echo '    </tr>';

            // Summary Info Row
            echo '    <tr>';
            echo '      <td colspan="3" class="bold bg-gray">JUMLAH DATA</td>';
            echo '      <td colspan="6" class="bold">' . $count . ' Transaksi</td>';
            echo '    </tr>';

            echo '  </tbody>';
            echo '</table>';
            echo '</body>';
            echo '</html>';
        }, $filename, [
            'Content-Type'  => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }
}
