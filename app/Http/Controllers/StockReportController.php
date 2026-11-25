<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustmentLog;
use App\Models\StockLocation;
use App\Models\StockTransfer;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StockReportController extends Controller
{
    public function __construct(private StockService $stockService) {}

    /**
     * Laporan stok per lokasi — Matriks produk × cabang (Owner).
     */
    public function index(Request $request)
    {
        $locations = Cache::remember('active_locations_sorted_report', 600,
            fn() => StockLocation::active()->orderByRaw("type = 'gudang' DESC")->orderBy('name')->get()
        );

        $query = Product::with(['productStocks.location'])->orderBy('name');

        // Filter kategori
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter nama produk atau SKU
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%' . $term . '%')
                  ->orWhere('sku', 'like', '%' . $term . '%');
            });
        }

        $products   = $query->paginate(30)->withQueryString();
        $categories = Cache::remember('categories_sorted_name', 1800,
            fn() => \App\Models\Category::orderBy('name')->get()
        );

        // Bangun matrix stok: [product_id][location_id] = stock
        $stockMatrix = [];
        foreach ($products as $product) {
            foreach ($product->productStocks as $ps) {
                $stockMatrix[$product->id][$ps->location_id] = $ps->stock;
            }
        }

        // Jika request via AJAX (Live Search), kembalikan JSON potongan HTML tabel
        if ($request->ajax()) {
            return response()->json([
                'html'  => view('owner.partials.stock-matrix-table', compact('products', 'locations', 'stockMatrix'))->render(),
                'total' => $products->total(),
                'count' => $products->count(),
            ]);
        }

        // Total stok per lokasi (semua produk)
        $locationTotals = ProductStock::selectRaw('location_id, SUM(stock) as total')
            ->groupBy('location_id')
            ->pluck('total', 'location_id');

        // Peringatan stok kritis per lokasi
        $lowStockAlerts = $this->stockService->getLowStockAlerts(10);

        // Jumlah request pending (untuk notifikasi owner)
        $pendingRequests = StockTransfer::pending()->count();

        return view('owner.stock-report', compact(
            'products',
            'locations',
            'stockMatrix',
            'categories',
            'locationTotals',
            'lowStockAlerts',
            'pendingRequests'
        ));
    }

    /**
     * Export laporan stok per lokasi sebagai Excel.
     */
    public function export(Request $request)
    {
        $locations = Cache::remember('active_locations_sorted_report', 600,
            fn() => StockLocation::active()->orderByRaw("type = 'gudang' DESC")->orderBy('name')->get()
        );

        $query = Product::with(['productStocks.location'])->orderBy('name');
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }
        $products = $query->get();

        $now      = now();
        $printBy  = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';
        $isPdf    = $request->query('format') === 'pdf';

        // Pre-calculate totals for summary cards
        $totalProducts = count($products);
        $totalLocations = count($locations);
        $grandTotal = 0;
        foreach ($products as $product) {
            $grandTotal += $product->productStocks->sum('stock');
        }

        $renderTemplate = function () use ($products, $locations, $now, $printBy, $isPdf, $totalProducts, $totalLocations, $grandTotal) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<style>';
            if ($isPdf) {
                echo '@page { margin: 1.0cm 1.2cm; } table { width: 100%; margin: 0 auto; } th { width: auto !important; } ';
            }
            echo 'body{font-family:"Segoe UI",Arial,sans-serif;color:#1e293b;}';
            echo 'table{border-collapse:collapse;width:100%;}';
            echo 'th{background-color:#059669;color:#fff;font-weight:bold;border:1px solid #047857;padding:8px 12px;font-size:10pt;white-space:nowrap;text-align:center;}';
            echo 'td{border:1px solid #d1fae5;padding:6px 10px;font-size:9pt;vertical-align:middle;}';
            echo '.title{font-size:15pt;font-weight:bold;color:#064e3b;text-align:center;border:none !important;}';
            echo '.subtitle{font-size:11pt;color:#10b981;text-align:center;border:none !important;}';
            echo '.meta-label{font-weight:bold;background-color:#f0fdf4;border:1px solid #d1fae5;width:150px;}';
            echo '.meta-value{border:1px solid #d1fae5;}';
            echo '.gudang{background-color:#dbeafe;font-weight:bold;}';
            echo '.low{background-color:#fee2e2;color:#b91c1c;font-weight:bold;}';
            echo '.zero{background-color:#f9fafb;color:#9ca3af;}';
            echo '.total-row{background-color:#d1fae5;font-weight:bold;color:#064e3b;}';
            echo '.spacer{border:none !important;height:8px;}';
            echo '.stripe{background-color:#f9fafb;}';
            echo '</style></head><body>';

            if ($isPdf) {
                echo $this->pdfHeader();
                
                // Render summary cards
                $cards = [
                    [
                        'label' => 'Total Jenis Produk',
                        'value' => $totalProducts . ' Item',
                        'color' => '#059669'
                    ],
                    [
                        'label' => 'Total Kuantitas Stok',
                        'value' => number_format($grandTotal, 0, ',', '.') . ' Unit',
                        'color' => '#2563eb'
                    ],
                    [
                        'label' => 'Jumlah Cabang & Gudang',
                        'value' => $totalLocations . ' Lokasi',
                        'color' => '#d97706'
                    ]
                ];
                echo $this->pdfCards($cards);
            }

            // Metadata
            $colspan = count($locations) + 3;
            echo '<table style="margin-bottom:12px; width:100%;">';
            if (!$isPdf) {
                echo "<tr><td colspan=\"{$colspan}\" class=\"title\" style=\"height:40px;\">LAPORAN STOK PER CABANG & GUDANG</td></tr>";
                echo "<tr><td colspan=\"{$colspan}\" class=\"subtitle\" style=\"height:24px;\">PUSAT KURMA PREMIUM</td></tr>";
                echo "<tr><td colspan=\"{$colspan}\" class=\"spacer\"></td></tr>";
            }
            echo "<tr><td class=\"meta-label\">Tanggal Cetak</td><td colspan=\"" . ($colspan - 1) . "\" class=\"meta-value\">" . $now->translatedFormat('d F Y - H:i') . "</td></tr>";
            echo "<tr><td class=\"meta-label\">Dicetak Oleh</td><td colspan=\"" . ($colspan - 1) . "\" class=\"meta-value\">" . htmlspecialchars($printBy) . "</td></tr>";
            echo "<tr><td colspan=\"{$colspan}\" class=\"spacer\"></td></tr>";
            echo '</table>';

            // Data table
            echo '<table>';
            echo '<thead><tr>';
            echo '<th style="width:40px;">No</th>';
            echo '<th style="width:180px;">Nama Produk</th>';
            echo '<th style="width:80px;">Satuan</th>';
            foreach ($locations as $loc) {
                $cls = $loc->type === 'gudang' ? ' class="gudang"' : '';
                echo "<th style=\"width:90px;\"$cls>" . htmlspecialchars($loc->name) . "</th>";
            }
            echo '<th style="width:90px;">TOTAL</th>';
            echo '</tr></thead><tbody>';

            $idx = 1;
            $totals = array_fill_keys($locations->pluck('id')->toArray(), 0);
            $grandTotalCalculated = 0;

            foreach ($products as $product) {
                $stockByLoc = $product->productStocks->keyBy('location_id');
                $rowTotal   = $product->productStocks->sum('stock');
                $grandTotalCalculated += $rowTotal;
                $rowClass = $idx % 2 === 0 ? ' class="stripe"' : '';

                echo "<tr$rowClass>";
                echo '<td style="text-align:center;">' . $idx++ . '</td>';
                echo '<td><strong>' . htmlspecialchars($product->name) . '</strong></td>';
                echo '<td style="text-align:center;">' . $product->price_unit . '</td>';

                foreach ($locations as $loc) {
                    $s = isset($stockByLoc[$loc->id]) ? (float) $stockByLoc[$loc->id]->stock : 0;
                    $totals[$loc->id] += $s;
                    $cls = $s <= 0 ? 'zero' : ($s <= 10 ? 'low' : '');
                    $clsAttr = $cls ? " class=\"$cls\"" : '';
                    
                    $displayVal = number_format($s, 2, ',', '.');
                    if ($product->price_unit === 'gram' && $s >= 1000) {
                        $displayVal = number_format($s / 1000, 2, ',', '.') . ' kg';
                    }
                    echo "<td style=\"text-align:right;\"$clsAttr>" . $displayVal . '</td>';
                }

                $displayTotal = number_format($rowTotal, 2, ',', '.');
                if ($product->price_unit === 'gram' && $rowTotal >= 1000) {
                    $displayTotal = number_format($rowTotal / 1000, 2, ',', '.') . ' kg';
                }
                echo '<td style="text-align:right;font-weight:bold;">' . $displayTotal . '</td>';
                echo '</tr>';
            }

            // Total row
            echo '<tr><td colspan="3" class="spacer"></td>' . str_repeat('<td class="spacer"></td>', count($locations) + 1) . '</tr>';
            echo '<tr class="total-row">';
            echo '<td colspan="3" style="text-align:center;font-size:11pt;">TOTAL STOK PER LOKASI</td>';
            foreach ($locations as $loc) {
                echo '<td style="text-align:right;">' . number_format($totals[$loc->id], 2, ',', '.') . '</td>';
            }
            echo '<td style="text-align:right;">' . number_format($grandTotalCalculated, 2, ',', '.') . '</td>';
            echo '</tr>';

            echo '</tbody></table>';

            if ($isPdf) {
                echo $this->pdfSignature($printBy);
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
                return $pdf->download('Laporan_Stok_Cabang_' . $now->format('Y-m-d') . '.pdf');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Stock Report PDF export failed: ' . $e->getMessage());
                return response('Gagal mengekspor PDF Laporan Stok: ' . $e->getMessage(), 500);
            }
        }

        $filename = 'Laporan_Stok_Cabang_' . $now->format('Y-m-d') . '.xls';
        return response()->streamDownload($renderTemplate, $filename, [
            'Content-Type'  => 'application/vnd.ms-excel; charset=UTF-8',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    /**
     * Log mutasi stok (histori pergerakan stok).
     * Mendukung mode akumulasi harian (default) untuk barang keluar/transaksi yang sama di lokasi yang sama.
     */
    public function adjustmentLog(Request $request)
    {
        $mode      = $request->get('mode', 'accumulated'); // Default mode: akumulasi harian
        $products  = Product::orderBy('name')->get();
        $locations = StockLocation::active()->orderBy('name')->get();

        if ($mode === 'detail') {
            // Mode Detail: Tampilkan setiap baris transaksi secara individual
            $query = StockAdjustmentLog::with(['product', 'location', 'creator'])->latest('created_at');

            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            if ($request->filled('location_id')) {
                $query->where('location_id', $request->location_id);
            }
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }

            $logs = $query->paginate(25)->withQueryString();

            return view('owner.stock-adjustment-log', compact('logs', 'products', 'locations', 'mode'));
        }

        // Mode Akumulasi Harian (Default):
        // Jika barang yang sama keluar/berubah di tempat yang sama pada hari yang sama, akumulasi per hari
        $query = \Illuminate\Support\Facades\DB::table('stock_adjustment_logs')
            ->selectRaw("
                DATE(created_at) as log_date,
                product_id,
                location_id,
                type,
                MIN(id) as first_log_id,
                MAX(id) as last_log_id,
                SUM(quantity_change) as total_change,
                COUNT(*) as transaction_count,
                MAX(created_at) as latest_created_at
            ");

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }
        if ($request->filled('location_id')) {
            $query->where('location_id', $request->location_id);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $query->groupBy(\Illuminate\Support\Facades\DB::raw("DATE(created_at)"), 'product_id', 'location_id', 'type')
              ->orderByDesc('latest_created_at');

        $paginated = $query->paginate(25)->withQueryString();

        $firstLogIds = $paginated->pluck('first_log_id')->filter()->unique();
        $lastLogIds  = $paginated->pluck('last_log_id')->filter()->unique();
        $allLogIds   = $firstLogIds->merge($lastLogIds)->unique();

        $logsById = StockAdjustmentLog::with(['product', 'location', 'creator'])
            ->whereIn('id', $allLogIds)
            ->get()
            ->keyBy('id');

        $transformed = $paginated->getCollection()->map(function ($row) use ($logsById) {
            $firstLog = $logsById[$row->first_log_id] ?? null;
            $lastLog  = $logsById[$row->last_log_id] ?? $firstLog;

            if (!$firstLog) return null;

            $item = clone $lastLog;
            $item->quantity_before   = (float)$firstLog->quantity_before;
            $item->quantity_change   = (float)$row->total_change;
            $item->quantity_after    = (float)$lastLog->quantity_after;
            $item->transaction_count = (int)$row->transaction_count;
            $item->is_accumulated    = ($row->transaction_count > 1);
            $item->log_date          = $row->log_date;
            $item->created_at        = $lastLog->created_at;

            return $item;
        })->filter();

        $paginated->setCollection($transformed);
        $logs = $paginated;

        return view('owner.stock-adjustment-log', compact('logs', 'products', 'locations', 'mode'));
    }

    /**
     * Halaman Diagnosa Barang Mati (Dead Stock & Slow-Moving Analysis).
     */
    public function deadStock(Request $request)
    {
        $days = (int) $request->query('days', 60);
        if (!in_array($days, [30, 60, 90, 180], true)) {
            $days = 60;
        }

        $locationId = $request->filled('location_id') ? (int) $request->query('location_id') : null;
        $category   = $request->filled('category') ? trim((string) $request->query('category')) : '';
        $status     = $request->query('status', 'all_issues');
        if (!in_array($status, ['all_issues', 'dead', 'slow', 'new'], true)) {
            $status = 'all_issues';
        }
        $sort = $request->query('sort', 'capital_desc');
        if (!in_array($sort, ['capital_desc', 'idle_desc', 'stock_desc'], true)) {
            $sort = 'capital_desc';
        }
        $search       = trim((string) $request->query('search', ''));
        $forceRefresh = $request->boolean('refresh');

        $analysis = $this->buildDeadStockData($days, $locationId, $forceRefresh);
        $filtered = $this->filterAndSortDeadStock($analysis['items'], $status, $category, $search, $sort);

        $perPage = 25;
        $page    = max(1, (int) $request->query('page', 1));
        $total   = $filtered->count();
        $items   = new \Illuminate\Pagination\LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $locations = Cache::remember('active_locations_sorted_report', 600,
            fn() => StockLocation::active()->orderByRaw("type = 'gudang' DESC")->orderBy('name')->get()
        );
        $categories = Cache::remember('categories_sorted_name', 1800,
            fn() => \App\Models\Category::orderBy('name')->get()
        );

        return view('owner.dead-stock', [
            'items'       => $items,
            'summary'     => $analysis['summary'],
            'locations'   => $locations,
            'categories'  => $categories,
            'days'        => $days,
            'locationId'  => $locationId,
            'category'    => $category,
            'status'      => $status,
            'sort'        => $sort,
            'search'      => $search,
            'generatedAt' => $analysis['generated_at'],
        ]);
    }

    /**
     * Export Diagnosa Barang Mati ke Excel atau PDF.
     */
    public function exportDeadStock(Request $request)
    {
        $days = (int) $request->query('days', 60);
        if (!in_array($days, [30, 60, 90, 180], true)) {
            $days = 60;
        }
        $locationId = $request->filled('location_id') ? (int) $request->query('location_id') : null;
        $category   = $request->filled('category') ? trim((string) $request->query('category')) : '';
        $status     = $request->query('status', 'all_issues');
        $sort       = $request->query('sort', 'capital_desc');
        $search     = trim((string) $request->query('search', ''));
        $isPdf      = $request->query('format') === 'pdf';

        $analysis = $this->buildDeadStockData($days, $locationId, false);
        $items    = $this->filterAndSortDeadStock($analysis['items'], $status, $category, $search, $sort);
        $summary  = $analysis['summary'];

        $locationName = 'Semua Cabang & Gudang';
        if ($locationId) {
            $loc = StockLocation::find($locationId);
            if ($loc) {
                $locationName = $loc->name;
            }
        }

        $now     = now();
        $printBy = auth()->user()->name . ' (' . ucfirst(auth()->user()->role) . ')';

        $renderTemplate = function () use ($items, $summary, $days, $locationName, $now, $printBy, $isPdf) {
            echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">';
            echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
            echo '<style>';
            if ($isPdf) {
                echo '@page { margin: 1.0cm 1.2cm; } table { width: 100%; margin: 0 auto; } th { width: auto !important; } ';
            }
            echo 'body{font-family:"Segoe UI",Arial,sans-serif;color:#1e293b;}';
            echo 'table{border-collapse:collapse;width:100%;}';
            echo 'th{background-color:#059669;color:#fff;font-weight:bold;border:1px solid #047857;padding:8px 10px;font-size:10pt;white-space:nowrap;}';
            echo 'td{border:1px solid #d1fae5;padding:7px 9px;font-size:9.5pt;vertical-align:middle;}';
            echo '.title{font-size:15pt;font-weight:bold;color:#064e3b;text-align:center;border:none !important;}';
            echo '.subtitle{font-size:11pt;font-weight:bold;color:#10b981;text-align:center;border:none !important;}';
            echo '.meta-label{font-weight:bold;color:#374151;background-color:#f0fdf4;width:160px;border:1px solid #d1fae5;}';
            echo '.meta-value{color:#1f2937;border:1px solid #d1fae5;}';
            echo '.stripe{background-color:#f9fafb;}';
            echo '.total-row{background-color:#d1fae5;font-weight:bold;color:#064e3b;}';
            echo '.badge-dead{color:#b91c1c;font-weight:bold;}';
            echo '.badge-slow{color:#b45309;font-weight:bold;}';
            echo '.badge-new{color:#0369a1;font-weight:bold;}';
            echo '</style></head><body>';

            if ($isPdf) {
                echo $this->pdfHeader();
            }

            echo '<table style="margin-bottom:12px;">';
            echo '<tr><td colspan="8" class="title">DIAGNOSA BARANG MATI &amp; SLOW MOVING</td></tr>';
            echo '<tr><td colspan="8" class="subtitle">Analisis Perputaran Persediaan &amp; Modal Tertahan</td></tr>';
            echo '<tr><td colspan="2" class="meta-label">Periode Analisis</td><td colspan="6" class="meta-value">' . $days . ' Hari Terakhir</td></tr>';
            echo '<tr><td colspan="2" class="meta-label">Lokasi Stok</td><td colspan="6" class="meta-value">' . htmlspecialchars($locationName) . '</td></tr>';
            echo '<tr><td colspan="2" class="meta-label">Tanggal Cetak</td><td colspan="6" class="meta-value">' . $now->translatedFormat('d F Y H:i') . ' WIB | Oleh: ' . htmlspecialchars($printBy) . '</td></tr>';
            echo '</table>';

            if ($isPdf) {
                echo $this->pdfCards([
                    ['label' => 'Barang Mati Total', 'value' => $summary['dead_count'] . ' SKU', 'color' => '#be123c'],
                    ['label' => 'Slow Moving', 'value' => $summary['slow_count'] . ' SKU', 'color' => '#b45309'],
                    ['label' => 'Total Modal Tertahan', 'value' => 'Rp ' . number_format($summary['problem_capital'], 0, ',', '.'), 'color' => '#065f46'],
                    ['label' => 'Porsi Modal Tertahan', 'value' => $summary['problem_capital_percent'] . '%', 'color' => '#1e293b'],
                ]);
            }

            echo '<table><thead><tr>';
            echo '<th style="width:40px;text-align:center;">No</th>';
            echo '<th>Produk &amp; SKU</th>';
            echo '<th>Kategori</th>';
            echo '<th style="text-align:right;">Sisa Stok</th>';
            echo '<th style="text-align:right;">Modal Tertahan</th>';
            echo '<th style="text-align:center;">Terjual (' . $days . 'h)</th>';
            echo '<th style="text-align:center;">Mengendap</th>';
            echo '<th>Status &amp; Rekomendasi</th>';
            echo '</tr></thead><tbody>';

            $idx = 1;
            $totalModal = 0;
            foreach ($items as $row) {
                $totalModal += $row['dead_capital'];
                $rowClass = $idx % 2 === 0 ? ' class="stripe"' : '';
                $statusClass = $row['status'] === 'dead' ? 'badge-dead' : ($row['status'] === 'new' ? 'badge-new' : 'badge-slow');

                echo "<tr{$rowClass}>";
                echo '<td style="text-align:center;">' . $idx++ . '</td>';
                echo '<td><strong>' . htmlspecialchars($row['name']) . '</strong><br><span style="font-size:8pt;color:#64748b;">' . htmlspecialchars($row['sku']) . '</span></td>';
                echo '<td>' . htmlspecialchars($row['category']) . '</td>';
                echo '<td style="text-align:right;">' . htmlspecialchars($row['display_stock']) . '</td>';
                echo '<td style="text-align:right;font-weight:bold;">Rp ' . number_format($row['dead_capital'], 0, ',', '.') . '</td>';
                echo '<td style="text-align:center;">' . htmlspecialchars($row['display_sold']) . ' (' . $row['sale_count'] . 'x)</td>';
                echo '<td style="text-align:center;">' . $row['idle_days'] . ' hari</td>';
                echo '<td><span class="' . $statusClass . '">' . htmlspecialchars($row['status_label']) . '</span><br><span style="font-size:8pt;color:#475569;">' . htmlspecialchars($row['recommendation']) . '</span></td>';
                echo '</tr>';
            }

            echo '<tr class="total-row">';
            echo '<td colspan="4" style="text-align:right;">TOTAL MODAL TERTAHAN (DATA TERFILTER)</td>';
            echo '<td style="text-align:right;">Rp ' . number_format($totalModal, 0, ',', '.') . '</td>';
            echo '<td colspan="3"></td>';
            echo '</tr>';

            echo '</tbody></table>';

            if ($isPdf) {
                echo $this->pdfSignature($printBy);
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
                return $pdf->download('Diagnosa_Barang_Mati_' . $days . 'Hari_' . $now->format('Y-m-d') . '.pdf');
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Dead Stock PDF export failed: ' . $e->getMessage());
                return back()->with('error', 'Gagal memproses PDF: ' . $e->getMessage());
            }
        }

        $filename = 'Diagnosa_Barang_Mati_' . $days . 'Hari_' . $now->format('Y-m-d') . '.xls';
        return response()->streamDownload($renderTemplate, $filename, [
            'Content-Type'        => 'application/vnd.ms-excel; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma'              => 'no-cache',
            'Expires'             => '0',
        ]);
    }

    /**
     * Hitung data diagnosa barang mati dalam 3 query agregasi SQL yang efisien (zero N+1).
     */
    private function buildDeadStockData(int $days, ?int $locationId, bool $forceRefresh = false): array
    {
        $cacheKey = "dead_stock_analysis_{$days}_" . ($locationId ?: 'all');
        if ($forceRefresh) {
            Cache::forget($cacheKey);
        }

        return Cache::remember($cacheKey, 300, function () use ($days, $locationId) {
            $now         = now();
            $periodStart = $now->copy()->subDays($days);

            // Query 1: Agregasi total penjualan dalam periode analisis
            $periodSales = StockAdjustmentLog::query()
                ->selectRaw('product_id, SUM(ABS(quantity_change)) as qty_sold, COUNT(id) as sale_count')
                ->where('type', 'sale')
                ->where('created_at', '>=', $periodStart)
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id');

            // Query 2: Tanggal penjualan terakhir & masuk stok terakhir sepanjang waktu (1 query kondisional)
            $historyDates = StockAdjustmentLog::query()
                ->selectRaw("
                    product_id,
                    MAX(CASE WHEN type = 'sale' THEN created_at END) as last_sold_at,
                    MAX(CASE WHEN reference_type IN ('purchase', 'transfer_in', 'initial') THEN created_at END) as last_restock_at
                ")
                ->when($locationId, fn($q) => $q->where('location_id', $locationId))
                ->groupBy('product_id')
                ->get()
                ->keyBy('product_id');

            // Query 3: Master produk aktif dengan stok > 0
            if ($locationId) {
                $stockRows = ProductStock::with('product')
                    ->where('location_id', $locationId)
                    ->where('stock', '>', 0)
                    ->get();

                $productEntries = [];
                foreach ($stockRows as $ps) {
                    if ($ps->product && $ps->product->is_active && !$ps->product->is_bundle) {
                        $productEntries[] = [
                            'product' => $ps->product,
                            'stock'   => (float) $ps->stock,
                        ];
                    }
                }
            } else {
                $products = Product::where('is_active', true)
                    ->where('is_bundle', false)
                    ->where('stock', '>', 0)
                    ->select('id', 'sku', 'name', 'category', 'cost_price', 'selling_price', 'price_unit', 'stock', 'created_at')
                    ->get();

                $productEntries = [];
                foreach ($products as $p) {
                    $productEntries[] = [
                        'product' => $p,
                        'stock'   => (float) $p->stock,
                    ];
                }
            }

            $items = collect();
            $totalInventoryCapital   = 0;
            $deadCount               = 0;
            $deadCapitalTotal        = 0;
            $slowCount               = 0;
            $slowCapitalTotal        = 0;
            $newCount                = 0;
            $problemPotentialRevenue = 0;

            foreach ($productEntries as $entry) {
                /** @var Product $prod */
                $prod  = $entry['product'];
                $stock = $entry['stock'];

                $costPrice    = (float) $prod->cost_price;
                $sellingPrice = (float) $prod->selling_price;
                $deadCapital  = round($stock * $costPrice);
                $potRevenue   = round($stock * $sellingPrice);
                $totalInventoryCapital += $deadCapital;

                $saleStat = $periodSales->get($prod->id);
                $qtySold  = $saleStat ? (float) $saleStat->qty_sold : 0.0;
                $saleCnt  = $saleStat ? (int) $saleStat->sale_count : 0;

                $hist          = $historyDates->get($prod->id);
                $lastSoldAt    = ($hist && $hist->last_sold_at) ? \Carbon\Carbon::parse($hist->last_sold_at) : null;
                $lastRestockAt = ($hist && $hist->last_restock_at) ? \Carbon\Carbon::parse($hist->last_restock_at) : null;

                $refRestockDate     = $lastRestockAt ?? $prod->created_at ?? $now;
                $daysSinceRestock   = (int) $refRestockDate->diffInDays($now);
                $refIdleDate        = $lastSoldAt ?? $refRestockDate;
                $idleDays           = (int) $refIdleDate->diffInDays($now);

                $velocity = $qtySold / $days;
                $doiDays  = $velocity > 0 ? (int) round($stock / $velocity) : null;

                // Klasifikasi Diagnosa
                if ($qtySold <= 0) {
                    if ($daysSinceRestock <= 14) {
                        $status         = 'new';
                        $statusLabel    = 'Stok Baru (≤14 Hari)';
                        $recommendation = 'Pantau penjualan awal produk baru atau stok yang baru masuk.';
                        $newCount++;
                    } else {
                        $status         = 'dead';
                        $statusLabel    = 'Mati Total (Dead Stock)';
                        $recommendation = 'Buat promo cuci gudang / paket bundling, atau transfer ke cabang lain yang lebih ramai.';
                        $deadCount++;
                        $deadCapitalTotal        += $deadCapital;
                        $problemPotentialRevenue += $potRevenue;
                    }
                } elseif ($doiDays !== null && $doiDays > 120) {
                    $status         = 'critical_slow';
                    $statusLabel    = 'Sangat Lambat (DOI > 120h)';
                    $recommendation = 'Hentikan PO / restock sementara, berikan diskon bertahap atau jadikan item bundling.';
                    $slowCount++;
                    $slowCapitalTotal        += $deadCapital;
                    $problemPotentialRevenue += $potRevenue;
                } elseif ($doiDays !== null && $doiDays >= 60) {
                    $status         = 'slow';
                    $statusLabel    = 'Slow Moving (DOI 60-120h)';
                    $recommendation = 'Evaluasi harga jual, tampilkan di etalase depan atau promosikan di toko online.';
                    $slowCount++;
                    $slowCapitalTotal        += $deadCapital;
                    $problemPotentialRevenue += $potRevenue;
                } else {
                    // Perputaran lancar (DOI < 60 hari) — tidak masuk daftar masalah
                    continue;
                }

                $items->push([
                    'product_id'       => $prod->id,
                    'sku'              => $prod->sku ?: '-',
                    'name'             => $prod->name,
                    'category'         => $prod->category ?: 'Umum',
                    'price_unit'       => $prod->price_unit ?: 'pcs',
                    'stock'            => $stock,
                    'display_stock'    => $this->formatStockUnit($stock, $prod->price_unit ?: 'pcs'),
                    'cost_price'       => $costPrice,
                    'selling_price'    => $sellingPrice,
                    'dead_capital'     => $deadCapital,
                    'potential_rev'    => $potRevenue,
                    'qty_sold'         => $qtySold,
                    'display_sold'     => $this->formatStockUnit($qtySold, $prod->price_unit ?: 'pcs'),
                    'sale_count'       => $saleCnt,
                    'doi_days'         => $doiDays,
                    'last_sold_at'     => $lastSoldAt ? $lastSoldAt->translatedFormat('d M Y') : 'Belum pernah',
                    'idle_days'        => $idleDays,
                    'status'           => $status,
                    'status_label'     => $statusLabel,
                    'recommendation'   => $recommendation,
                ]);
            }

            $problemCapital = $deadCapitalTotal + $slowCapitalTotal;
            $problemPct     = $totalInventoryCapital > 0
                ? round(($problemCapital / $totalInventoryCapital) * 100, 1)
                : 0;

            return [
                'items'        => $items,
                'generated_at' => $now->translatedFormat('d M Y H:i'),
                'summary'      => [
                    'total_inventory_capital'   => $totalInventoryCapital,
                    'dead_count'                => $deadCount,
                    'dead_capital_total'        => $deadCapitalTotal,
                    'slow_count'                => $slowCount,
                    'slow_capital_total'        => $slowCapitalTotal,
                    'new_count'                 => $newCount,
                    'problem_count'             => $deadCount + $slowCount,
                    'problem_capital'           => $problemCapital,
                    'problem_potential_revenue' => $problemPotentialRevenue,
                    'problem_capital_percent'   => $problemPct,
                ],
            ];
        });
    }

    /**
     * Filter dan urutkan koleksi hasil diagnosa barang mati di memori.
     */
    private function filterAndSortDeadStock($items, string $status, string $category, string $search, string $sort)
    {
        $filtered = $items->filter(function ($row) use ($status, $category, $search) {
            if ($status === 'all_issues' && !in_array($row['status'], ['dead', 'critical_slow', 'slow'], true)) {
                return false;
            }
            if ($status === 'dead' && $row['status'] !== 'dead') {
                return false;
            }
            if ($status === 'slow' && !in_array($row['status'], ['critical_slow', 'slow'], true)) {
                return false;
            }
            if ($status === 'new' && $row['status'] !== 'new') {
                return false;
            }
            if ($category !== '' && strcasecmp($row['category'], $category) !== 0) {
                return false;
            }
            if ($search !== '') {
                $needle = mb_strtolower($search);
                if (!str_contains(mb_strtolower($row['name']), $needle) && !str_contains(mb_strtolower($row['sku']), $needle)) {
                    return false;
                }
            }
            return true;
        });

        return match ($sort) {
            'idle_desc'  => $filtered->sortByDesc('idle_days')->values(),
            'stock_desc' => $filtered->sortByDesc('stock')->values(),
            default      => $filtered->sortByDesc('dead_capital')->values(),
        };
    }

    /**
     * Format angka stok dengan konversi ramah baca untuk satuan gram >= 1000.
     */
    private function formatStockUnit(float $qty, string $unit): string
    {
        $cleanUnit = strtolower(trim($unit));
        if (in_array($cleanUnit, ['gram', 'gr', 'g'], true) && $qty >= 1000) {
            return rtrim(rtrim(number_format($qty / 1000, 2, ',', '.'), '0'), ',') . ' kg';
        }
        return rtrim(rtrim(number_format($qty, 2, ',', '.'), '0'), ',') . ' ' . $unit;
    }

    /**
     * Private helper methods for formal PDF layout.
     */
    private function pdfHeader(): string
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
                    <div style="font-size: 10pt; font-weight: 800; color: #059669; text-transform: uppercase; letter-spacing: 0.5px;">LAPORAN STOK</div>
                    <div style="font-size: 7.5pt; color: #6b7280; margin-top: 1px;">Status: Aktif</div>
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
}
