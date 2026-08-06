<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductStock;
use App\Models\StockAdjustmentLog;
use App\Models\StockLocation;
use App\Models\StockTransfer;
use App\Services\StockService;
use Illuminate\Http\Request;

class StockReportController extends Controller
{
    public function __construct(private StockService $stockService) {}

    /**
     * Laporan stok per lokasi — Matriks produk × cabang (Owner).
     */
    public function index(Request $request)
    {
        $locations = StockLocation::active()->orderByRaw("type = 'gudang' DESC")->orderBy('name')->get();

        $query = Product::with(['productStocks.location'])->orderBy('name');

        // Filter kategori
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Filter nama produk
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products   = $query->paginate(30)->withQueryString();
        $categories = \App\Models\Category::orderBy('name')->get();

        // Bangun matrix stok: [product_id][location_id] = stock
        $stockMatrix = [];
        foreach ($products as $product) {
            foreach ($product->productStocks as $ps) {
                $stockMatrix[$product->id][$ps->location_id] = $ps->stock;
            }
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
        $locations = StockLocation::active()->orderByRaw("type = 'gudang' DESC")->orderBy('name')->get();

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
            ob_start();
            $renderTemplate();
            $html = ob_get_clean();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
            $pdf->setPaper('a4', 'landscape');
            return $pdf->download('Laporan_Stok_Cabang_' . $now->format('Y-m-d') . '.pdf');
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
     */
    public function adjustmentLog(Request $request)
    {
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

        $logs      = $query->paginate(25)->withQueryString();
        $products  = Product::orderBy('name')->get();
        $locations = StockLocation::active()->orderBy('name')->get();

        return view('owner.stock-adjustment-log', compact('logs', 'products', 'locations'));
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
