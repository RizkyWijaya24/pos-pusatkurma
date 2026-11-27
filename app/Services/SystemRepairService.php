<?php

namespace App\Services;

use App\Models\InstallmentPayment;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\PurchaseItem;
use App\Models\StockAdjustmentLog;
use App\Models\StockLocation;
use App\Models\Transaction;
use App\Support\PosCache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Throwable;

class SystemRepairService
{
    public const AVAILABLE_MODULES = [
        'cache_opcache',
        'blade_integrity',
        'database_migrations',
        'stock_consistency',
        'dp_receivables_consistency',
        'storage_and_logs',
    ];

    public function __construct(
        protected StockService $stockService,
    ) {}

    /**
     * Run non-destructive system diagnostics across caches, views, DB, stock, DP/receivables, and storage.
     */
    public function diagnose(): array
    {
        $checks = [];

        $dbCheck = $this->diagnoseDatabaseAndMigrations();
        $checks['database_migrations'] = $dbCheck;
        $dbConnected = (bool) ($dbCheck['meta']['connected'] ?? false);

        $checks['cache_opcache'] = $this->diagnoseCacheAndOpcache();
        $checks['blade_integrity'] = $this->diagnoseBladeIntegrity();
        $checks['stock_consistency'] = $dbConnected
            ? $this->diagnoseStockConsistency()
            : $this->skippedCheck('stock_consistency', 'Sinkronisasi Stok Multi-Cabang', 'Dilewati karena koneksi database belum aktif.');
        $checks['dp_receivables_consistency'] = $dbConnected
            ? $this->diagnoseDpAndReceivables()
            : $this->skippedCheck('dp_receivables_consistency', 'Konsistensi DP, Piutang & Satuan', 'Dilewati karena koneksi database belum aktif.');
        $checks['storage_and_logs'] = $this->diagnoseStorageAndLogs();

        $score = $this->calculateHealthScore($checks);
        $hasCritical = collect($checks)->contains(fn (array $c) => $c['status'] === 'critical');
        $hasWarning = collect($checks)->contains(fn (array $c) => $c['status'] === 'warning');

        return [
            'score' => $score,
            'overall_status' => $hasCritical ? 'critical' : ($hasWarning ? 'warning' : 'healthy'),
            'checked_at' => Carbon::now('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB',
            'db_connected' => $dbConnected,
            'checks' => array_values($checks),
        ];
    }

    /**
     * Execute selected self-repair modules and return structured per-module outcomes.
     */
    public function repair(array $modules, int $userId): array
    {
        $selected = in_array('all', $modules, true) || empty($modules)
            ? ['cache_opcache', 'database_migrations', 'stock_consistency', 'dp_receivables_consistency', 'storage_and_logs', 'blade_integrity']
            : array_values(array_intersect($modules, self::AVAILABLE_MODULES));

        $results = [];

        // Run cache clearing first so subsequent steps read clean config/paths.
        if (in_array('cache_opcache', $selected, true)) {
            $results[] = $this->repairCacheAndOpcache();
        }
        if (in_array('storage_and_logs', $selected, true)) {
            $results[] = $this->repairStorageAndLogs();
        }
        if (in_array('database_migrations', $selected, true)) {
            $results[] = $this->repairDatabaseMigrations();
        }
        if (in_array('stock_consistency', $selected, true)) {
            $results[] = $this->repairStockConsistency();
        }
        if (in_array('dp_receivables_consistency', $selected, true)) {
            $results[] = $this->repairDpAndReceivables($userId);
        }
        if (in_array('blade_integrity', $selected, true)) {
            $results[] = $this->repairBladeViews();
        }

        try {
            Log::channel('repair')->info('Self-Repair executed', [
                'user_id' => $userId,
                'modules' => $selected,
                'results' => array_map(fn ($r) => [
                    'key' => $r['key'],
                    'status' => $r['status'],
                    'summary' => $r['summary'],
                ], $results),
            ]);
        } catch (Throwable) {
            // Do not fail repair response if repair.log is temporarily unwritable.
        }

        return [
            'executed_at' => Carbon::now('Asia/Jakarta')->translatedFormat('d M Y, H:i:s') . ' WIB',
            'results' => $results,
            'diagnostics' => $this->diagnose(),
        ];
    }

    /**
     * Read recent error entries from the tail of storage/logs/laravel.log safely.
     */
    public function getRecentErrors(int $limit = 50): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!is_file($logPath) || !is_readable($logPath)) {
            return [];
        }

        $tail = $this->readFileTail($logPath, 512 * 1024);
        if ($tail === '') {
            return [];
        }

        $lines = preg_split('/\r\n|\r|\n/', $tail) ?: [];
        $entries = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\]\s+[a-zA-Z0-9_-]+\.(ERROR|CRITICAL|ALERT|EMERGENCY):\s+(.*)$/', $line, $m)) {
                $message = $m[3];
                $location = null;
                if (preg_match('/\s+at\s+([^\s\}]+:\d+)/', $message, $locMatch)) {
                    $location = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $locMatch[1]);
                } elseif (preg_match('/([a-zA-Z0-9_\/\\\\-]+\.(?:php|blade\.php))\((\d+)\)/', $message, $locMatch2)) {
                    $location = $locMatch2[1] . ':' . $locMatch2[2];
                }

                // Strip verbose exception JSON blob for UI readability
                $cleanMsg = preg_replace('/\s*\{\"exception\":\"\[object\].*$/', '', $message);
                $entries[] = [
                    'timestamp' => $m[1],
                    'level' => $m[2],
                    'message' => mb_strimwidth(trim((string) $cleanMsg), 0, 260, '...'),
                    'location' => $location,
                ];
            }
        }

        return array_slice(array_reverse($entries), 0, $limit);
    }

    /**
     * Read recent self-repair executions from storage/logs/repair.log.
     */
    public function getRepairHistory(int $limit = 15): array
    {
        $logPath = storage_path('logs/repair.log');
        if (!is_file($logPath) || !is_readable($logPath)) {
            return [];
        }

        $tail = $this->readFileTail($logPath, 128 * 1024);
        $lines = preg_split('/\r\n|\r|\n/', $tail) ?: [];
        $entries = [];

        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2})\].*?Self-Repair executed\s+(\{.*\})$/', $line, $m)) {
                $payload = json_decode($m[2], true);
                if (is_array($payload)) {
                    $entries[] = [
                        'timestamp' => $m[1],
                        'user_id' => $payload['user_id'] ?? null,
                        'modules' => $payload['modules'] ?? [],
                        'results' => $payload['results'] ?? [],
                    ];
                }
            }
        }

        return array_slice(array_reverse($entries), 0, $limit);
    }

    /**
     * Archive or truncate laravel.log when bloated or cleared by owner/admin.
     */
    public function archiveOrClearLogs(): array
    {
        $logPath = storage_path('logs/laravel.log');
        if (!is_file($logPath)) {
            return [
                'success' => true,
                'message' => 'File log utama belum ada atau sudah kosong.',
            ];
        }

        $sizeBefore = @filesize($logPath) ?: 0;
        $archiveName = null;

        if ($sizeBefore > 0) {
            $archiveName = 'laravel-archived-' . Carbon::now('Asia/Jakarta')->format('Ymd-His') . '.log';
            $archivePath = storage_path('logs/' . $archiveName);
            if (@copy($logPath, $archivePath)) {
                $this->pruneOldArchives(storage_path('logs'), 'laravel-archived-*.log', 2);
            } else {
                $archiveName = null;
            }
        }

        @file_put_contents($logPath, '');

        return [
            'success' => true,
            'message' => $archiveName
                ? "Log dibersihkan (" . $this->formatBytes($sizeBefore) . ") dan diarsipkan sebagai {$archiveName}."
                : "Log berhasil dikosongkan (" . $this->formatBytes($sizeBefore) . ").",
        ];
    }

    private function diagnoseDatabaseAndMigrations(): array
    {
        try {
            DB::connection()->getPdo();
            DB::select('SELECT 1');
        } catch (Throwable $e) {
            return [
                'key' => 'database_migrations',
                'title' => 'Database & Migrasi Skema',
                'status' => 'critical',
                'repairable' => true,
                'summary' => 'Koneksi database gagal: ' . mb_strimwidth($e->getMessage(), 0, 140, '...'),
                'details' => ['Pastikan layanan MySQL aktif dan kredensial DB pada .env benar.'],
                'meta' => ['connected' => false, 'pending_count' => 0],
            ];
        }

        try {
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
            $pending = array_values(array_diff(array_keys($files), $ran));
            $pendingCount = count($pending);

            return [
                'key' => 'database_migrations',
                'title' => 'Database & Migrasi Skema',
                'status' => $pendingCount > 0 ? 'warning' : 'ok',
                'repairable' => true,
                'summary' => $pendingCount > 0
                    ? "Terdeteksi {$pendingCount} migrasi database belum dijalankan."
                    : 'Koneksi database aktif dan seluruh migrasi sinkron.',
                'details' => $pendingCount > 0 ? array_slice($pending, 0, 8) : [],
                'meta' => ['connected' => true, 'pending_count' => $pendingCount],
            ];
        } catch (Throwable $e) {
            return [
                'key' => 'database_migrations',
                'title' => 'Database & Migrasi Skema',
                'status' => 'warning',
                'repairable' => true,
                'summary' => 'Database terhubung, namun tabel migrasi belum terbaca: ' . mb_strimwidth($e->getMessage(), 0, 120, '...'),
                'details' => [],
                'meta' => ['connected' => true, 'pending_count' => 0],
            ];
        }
    }

    private function diagnoseCacheAndOpcache(): array
    {
        $viewFiles = glob(storage_path('framework/views/*.php')) ?: [];
        $bootstrapCaches = [
            'config.php' => is_file(base_path('bootstrap/cache/config.php')),
            'routes-v7.php' => is_file(base_path('bootstrap/cache/routes-v7.php')),
            'routes.php' => is_file(base_path('bootstrap/cache/routes.php')),
        ];
        $activeBootstrap = array_keys(array_filter($bootstrapCaches));
        $opcacheEnabled = function_exists('opcache_get_status') && @opcache_get_status(false) !== false;

        $productsVersion = '-';
        try {
            $productsVersion = PosCache::productsVersion();
        } catch (Throwable) {
            // Cache store might depend on DB when DB is offline
        }

        return [
            'key' => 'cache_opcache',
            'title' => 'Cache Aplikasi, View & Katalog',
            'status' => 'ok',
            'repairable' => true,
            'summary' => count($viewFiles) . ' view terkompilasi, token katalog #' . $productsVersion . ($opcacheEnabled ? ' (OPcache aktif)' : ''),
            'details' => array_filter([
                'Bootstrap cache aktif: ' . (empty($activeBootstrap) ? 'tidak ada (mode dinamis)' : implode(', ', $activeBootstrap)),
                'Direkomendasikan dibersihkan setiap kali baru upload/deploy update kode.',
            ]),
            'meta' => ['compiled_views' => count($viewFiles), 'opcache' => $opcacheEnabled],
        ];
    }

    private function diagnoseBladeIntegrity(): array
    {
        $viewRoot = resource_path('views');
        $files = File::allFiles($viewRoot);
        $broken = [];
        $checkedCount = 0;

        foreach ($files as $file) {
            if (!str_ends_with($file->getFilename(), '.blade.php')) {
                continue;
            }
            $checkedCount++;
            $raw = @file_get_contents($file->getPathname());
            if ($raw === false) {
                $broken[] = $file->getRelativePathname() . ' (gagal dibaca)';
                continue;
            }

            try {
                $compiled = Blade::compileString($raw);
                $syntaxError = $this->checkPhpSyntaxTokens($compiled);
                if ($syntaxError !== null) {
                    $broken[] = $file->getRelativePathname() . ' -> ' . $syntaxError;
                }
            } catch (Throwable $e) {
                $broken[] = $file->getRelativePathname() . ' -> ' . $e->getMessage();
            }
        }

        $brokenCount = count($broken);

        return [
            'key' => 'blade_integrity',
            'title' => 'Integritas Template Blade (' . $checkedCount . ' file)',
            'status' => $brokenCount > 0 ? 'critical' : 'ok',
            'repairable' => true,
            'summary' => $brokenCount > 0
                ? "Ditemukan {$brokenCount} template Blade berpotensi menyebabkan Error 500!"
                : "Seluruh {$checkedCount} template Blade terkompilasi valid tanpa error sintaks.",
            'details' => array_slice($broken, 0, 10),
            'meta' => ['checked' => $checkedCount, 'broken' => $brokenCount],
        ];
    }

    private function diagnoseStockConsistency(): array
    {
        $products = Product::select('id', 'name', 'stock', 'is_bundle')->get();
        $stockSums = ProductStock::selectRaw('product_id, SUM(stock) as total_stock')
            ->groupBy('product_id')
            ->pluck('total_stock', 'product_id');

        $mismatches = [];
        $activeLocations = StockLocation::where('is_active', true)->get();

        foreach ($products as $product) {
            if ($product->is_bundle) {
                $expected = 0.0;
                foreach ($activeLocations as $loc) {
                    $expected += $product->getStockAtLocation($loc->id);
                }
            } else {
                $expected = (float) ($stockSums[$product->id] ?? 0);
            }

            $current = (float) $product->stock;
            // Ignore products whose product_stocks has not been initialized yet when legacy stock > 0
            if (!$product->is_bundle && !isset($stockSums[$product->id]) && $current > 0) {
                $mismatches[] = "{$product->name}: stok global ({$current}) belum tercatat di stok lokasi";
                continue;
            }

            if (abs($current - $expected) > 0.001) {
                $mismatches[] = "{$product->name}: global {$current} vs total cabang {$expected}";
            }
        }

        $count = count($mismatches);

        return [
            'key' => 'stock_consistency',
            'title' => 'Sinkronisasi Stok Global & Cabang',
            'status' => $count > 0 ? 'warning' : 'ok',
            'repairable' => true,
            'summary' => $count > 0
                ? "Ditemukan {$count} produk dengan selisih stok global terhadap stok lokasi."
                : 'Stok global seluruh produk sesuai dengan rincian stok gudang & cabang.',
            'details' => array_slice($mismatches, 0, 10),
            'meta' => ['mismatches' => $count],
        ];
    }

    private function diagnoseDpAndReceivables(): array
    {
        $details = [];

        // 1. Unpaid transactions whose installments already cover total_price
        $shouldBePaidCount = Transaction::where('payment_status', 'unpaid')
            ->where('total_price', '>', 0)
            ->withSum('installments', 'amount')
            ->get()
            ->filter(fn (Transaction $t) => (float) ($t->installments_sum_amount ?? 0) >= (float) $t->total_price)
            ->count();

        if ($shouldBePaidCount > 0) {
            $details[] = "{$shouldBePaidCount} transaksi DP/Piutang sudah terbayar penuh namun statusnya masih 'unpaid'.";
        }

        // 2. Pending unit conversion mismatches (kg purchased for gram product, not yet fixed)
        $conversionCount = $this->findUnfixedUnitConversionItems()->count();
        if ($conversionCount > 0) {
            $details[] = "{$conversionCount} item pembelian satuan kg pada produk gram perlu penyesuaian konversi.";
        }

        // 3. Stale pending online orders > 24h
        $staleOrders = Order::where('payment_status', 'pending')
            ->where('created_at', '<', Carbon::now()->subHours(24))
            ->count();
        if ($staleOrders > 0) {
            $details[] = "{$staleOrders} order online belum dibayar (>24 jam) siap dibersihkan.";
        }

        $issueCount = ($shouldBePaidCount > 0 ? 1 : 0) + ($conversionCount > 0 ? 1 : 0) + ($staleOrders > 0 ? 1 : 0);

        return [
            'key' => 'dp_receivables_consistency',
            'title' => 'Konsistensi DP, Piutang & Satuan',
            'status' => $issueCount > 0 ? 'warning' : 'ok',
            'repairable' => true,
            'summary' => $issueCount > 0
                ? 'Ditemukan ketidaksesuaian status DP/piutang atau konversi pembelian.'
                : 'Status tagihan DP/piutang, konversi pembelian, dan order online dalam kondisi bersih.',
            'details' => $details,
            'meta' => [
                'dp_should_be_paid' => $shouldBePaidCount,
                'unit_conversion_pending' => $conversionCount,
                'stale_orders' => $staleOrders,
            ],
        ];
    }

    private function diagnoseStorageAndLogs(): array
    {
        $paths = [
            'storage/logs' => storage_path('logs'),
            'storage/framework/views' => storage_path('framework/views'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/app/public/products' => storage_path('app/public/products'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $unwritable = [];
        foreach ($paths as $label => $path) {
            if (!$this->isDirectoryWritable($path)) {
                $unwritable[] = $label;
            }
        }

        $logPath = storage_path('logs/laravel.log');
        $logSize = is_file($logPath) ? (@filesize($logPath) ?: 0) : 0;
        $logBloated = $logSize > (20 * 1024 * 1024);

        $details = [];
        if (!empty($unwritable)) {
            $details[] = 'Folder belum ada/tidak writable: ' . implode(', ', $unwritable);
        }
        $details[] = 'Ukuran laravel.log: ' . $this->formatBytes($logSize);

        $status = !empty($unwritable) ? 'critical' : ($logBloated ? 'warning' : 'ok');

        return [
            'key' => 'storage_and_logs',
            'title' => 'Izin Folder Storage & Ukuran Log',
            'status' => $status,
            'repairable' => true,
            'summary' => !empty($unwritable)
                ? count($unwritable) . ' folder penting belum dapat ditulis.'
                : ($logBloated ? 'Ukuran file log melebihi 20 MB, disarankan untuk diringkas.' : 'Folder storage writable & ukuran log terkendali (' . $this->formatBytes($logSize) . ').'),
            'details' => $details,
            'meta' => ['unwritable' => $unwritable, 'log_size' => $logSize],
        ];
    }

    private function repairCacheAndOpcache(): array
    {
        try {
            $removed = 0;
            foreach (['config.php', 'routes-v7.php', 'routes.php', 'services.php', 'packages.php'] as $cacheFile) {
                $full = base_path('bootstrap/cache/' . $cacheFile);
                if (is_file($full) && @unlink($full)) {
                    $removed++;
                }
            }

            Artisan::call('view:clear');
            PosCache::flushProducts();

            clearstatcache(true);
            $opcacheCleared = false;
            if (function_exists('opcache_reset')) {
                $opcacheCleared = @opcache_reset();
            }

            return [
                'key' => 'cache_opcache',
                'title' => 'Cache Aplikasi, View & Katalog',
                'status' => 'fixed',
                'summary' => "Cache view & {$removed} file bootstrap cache dibersihkan; token katalog kasir diperbarui" . ($opcacheCleared ? '; OPcache direset.' : '.'),
            ];
        } catch (Throwable $e) {
            return [
                'key' => 'cache_opcache',
                'title' => 'Cache Aplikasi, View & Katalog',
                'status' => 'failed',
                'summary' => 'Gagal membersihkan cache: ' . $e->getMessage(),
            ];
        }
    }

    private function repairBladeViews(): array
    {
        $diag = $this->diagnoseBladeIntegrity();
        if ($diag['status'] !== 'ok') {
            return [
                'key' => 'blade_integrity',
                'title' => 'Validasi & Kompilasi Ulang Blade',
                'status' => 'failed',
                'summary' => $diag['summary'] . ' (' . implode('; ', array_slice($diag['details'], 0, 2)) . ')',
            ];
        }

        try {
            Artisan::call('view:cache');
            return [
                'key' => 'blade_integrity',
                'title' => 'Validasi & Kompilasi Ulang Blade',
                'status' => 'fixed',
                'summary' => 'Seluruh template Blade lolos validasi sintaks dan berhasil dikompilasi ulang.',
            ];
        } catch (Throwable $e) {
            return [
                'key' => 'blade_integrity',
                'title' => 'Validasi & Kompilasi Ulang Blade',
                'status' => 'failed',
                'summary' => 'Kompilasi Blade gagal: ' . $e->getMessage(),
            ];
        }
    }

    private function repairDatabaseMigrations(): array
    {
        try {
            DB::connection()->getPdo();
            $migrator = app('migrator');
            $files = $migrator->getMigrationFiles(database_path('migrations'));
            $ran = $migrator->getRepository()->getRan();
            $pendingCount = count(array_diff(array_keys($files), $ran));

            if ($pendingCount === 0) {
                return [
                    'key' => 'database_migrations',
                    'title' => 'Database & Migrasi Skema',
                    'status' => 'ok',
                    'summary' => 'Tidak ada migrasi tertunda; struktur tabel sudah terbaru.',
                ];
            }

            Artisan::call('migrate', ['--force' => true]);
            return [
                'key' => 'database_migrations',
                'title' => 'Database & Migrasi Skema',
                'status' => 'fixed',
                'summary' => "Berhasil menjalankan {$pendingCount} migrasi database tertunda.",
            ];
        } catch (Throwable $e) {
            return [
                'key' => 'database_migrations',
                'title' => 'Database & Migrasi Skema',
                'status' => 'failed',
                'summary' => 'Migrasi / koneksi DB gagal: ' . $e->getMessage(),
            ];
        }
    }

    private function repairStockConsistency(): array
    {
        try {
            DB::connection()->getPdo();
            $products = Product::all();
            $stockSums = ProductStock::selectRaw('product_id, SUM(stock) as total_stock')
                ->groupBy('product_id')
                ->pluck('total_stock', 'product_id');

            $synced = 0;
            $activeLocations = StockLocation::where('is_active', true)->get();

            foreach ($products as $product) {
                if ($product->is_bundle) {
                    $expected = 0.0;
                    foreach ($activeLocations as $loc) {
                        $expected += $product->getStockAtLocation($loc->id);
                    }
                    $needsSync = abs((float) $product->stock - $expected) > 0.001;
                } else {
                    $hasRecord = isset($stockSums[$product->id]);
                    $expected = (float) ($stockSums[$product->id] ?? 0);
                    $needsSync = abs((float) $product->stock - $expected) > 0.001 || (!$hasRecord && (float) $product->stock > 0);
                }

                if ($needsSync) {
                    $this->stockService->syncGlobalStock($product);
                    $synced++;
                }
            }

            if ($synced > 0) {
                PosCache::flushProducts();
            }

            return [
                'key' => 'stock_consistency',
                'title' => 'Sinkronisasi Stok Global & Cabang',
                'status' => $synced > 0 ? 'fixed' : 'ok',
                'summary' => $synced > 0
                    ? "Berhasil menyinkronkan stok {$synced} produk antara data global dan rincian lokasi."
                    : 'Stok seluruh produk sudah sinkron, tidak ada yang perlu diubah.',
            ];
        } catch (Throwable $e) {
            return [
                'key' => 'stock_consistency',
                'title' => 'Sinkronisasi Stok Global & Cabang',
                'status' => 'failed',
                'summary' => 'Gagal menyinkronkan stok: ' . $e->getMessage(),
            ];
        }
    }

    private function repairDpAndReceivables(int $userId): array
    {
        try {
            DB::connection()->getPdo();
            $fixedDp = 0;
            $fixedConversions = 0;
            $clearedOrders = 0;

            DB::transaction(function () use (&$fixedDp, &$fixedConversions, &$clearedOrders, $userId) {
                $unpaidTransactions = Transaction::where('payment_status', 'unpaid')
                    ->where('total_price', '>', 0)
                    ->withSum('installments', 'amount')
                    ->get();

                foreach ($unpaidTransactions as $trx) {
                    $paidSum = (float) ($trx->installments_sum_amount ?? 0);
                    if ($paidSum >= (float) $trx->total_price) {
                        $trx->payment_status = 'paid';
                        $trx->save();
                        $fixedDp++;
                    }
                }

                foreach ($this->findUnfixedUnitConversionItems() as $item) {
                    $prod = $item->product;
                    if (!$prod) {
                        continue;
                    }
                    $expectedStock = (float) $item->quantity * 1000;
                    $missingStock = $expectedStock - (float) $item->quantity;

                    $before = (float) $prod->stock;
                    $prod->stock = $before + $missingStock;
                    $oldHpp = (float) $prod->cost_price;
                    if ($oldHpp >= 10000 && (float) $item->unit_cost == $oldHpp) {
                        $prod->cost_price = (int) round($oldHpp / 1000);
                    }
                    $prod->save();

                    if ($item->purchase && $item->purchase->location_id) {
                        $ps = ProductStock::where('product_id', $prod->id)
                            ->where('location_id', $item->purchase->location_id)
                            ->first();
                        if ($ps) {
                            $ps->stock = (float) $ps->stock + $missingStock;
                            $ps->save();
                        }
                    }

                    StockAdjustmentLog::create([
                        'product_id' => $prod->id,
                        'location_id' => $item->purchase?->location_id ?? 1,
                        'type' => 'adjustment',
                        'quantity_before' => $before,
                        'quantity_change' => $missingStock,
                        'quantity_after' => (float) $prod->stock,
                        'reference_type' => 'unit_conversion_fix',
                        'reference_id' => $item->purchase_id,
                        'created_by' => $userId > 0 ? $userId : 1,
                        'notes' => "Self-repair konversi pembelian ({$item->quantity} kg -> {$expectedStock} gram)",
                        'created_at' => now(),
                    ]);

                    $fixedConversions++;
                }

                $clearedOrders = Order::where('payment_status', 'pending')
                    ->where('created_at', '<', Carbon::now()->subHours(24))
                    ->delete();
            });

            if ($fixedConversions > 0) {
                PosCache::flushProducts();
            }

            $totalFixed = $fixedDp + $fixedConversions + $clearedOrders;

            return [
                'key' => 'dp_receivables_consistency',
                'title' => 'Konsistensi DP, Piutang & Satuan',
                'status' => $totalFixed > 0 ? 'fixed' : 'ok',
                'summary' => $totalFixed > 0
                    ? "Diperbaiki: {$fixedDp} status lunas DP/piutang, {$fixedConversions} konversi satuan, {$clearedOrders} order pending kedaluwarsa."
                    : 'Status DP/piutang, satuan pembelian, dan order pending sudah konsisten.',
            ];
        } catch (Throwable $e) {
            return [
                'key' => 'dp_receivables_consistency',
                'title' => 'Konsistensi DP, Piutang & Satuan',
                'status' => 'failed',
                'summary' => 'Gagal memproses perbaikan DP/piutang: ' . $e->getMessage(),
            ];
        }
    }

    private function repairStorageAndLogs(): array
    {
        try {
            $ensured = 0;
            foreach ([
                storage_path('logs'),
                storage_path('framework/views'),
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('app/public/products'),
                base_path('bootstrap/cache'),
            ] as $dir) {
                if (!is_dir($dir) && @mkdir($dir, 0775, true)) {
                    $ensured++;
                }
                if (is_dir($dir) && !is_writable($dir)) {
                    @chmod($dir, 0775);
                }
            }

            $logMsg = '';
            $logPath = storage_path('logs/laravel.log');
            if (is_file($logPath) && (@filesize($logPath) ?: 0) > (15 * 1024 * 1024)) {
                $res = $this->archiveOrClearLogs();
                $logMsg = ' ' . $res['message'];
            }

            return [
                'key' => 'storage_and_logs',
                'title' => 'Izin Folder Storage & Ukuran Log',
                'status' => ($ensured > 0 || $logMsg !== '') ? 'fixed' : 'ok',
                'summary' => trim("Direktori storage & cache dipastikan siap tulis." . $logMsg),
            ];
        } catch (Throwable $e) {
            return [
                'key' => 'storage_and_logs',
                'title' => 'Izin Folder Storage & Ukuran Log',
                'status' => 'failed',
                'summary' => 'Gagal memeriksa storage: ' . $e->getMessage(),
            ];
        }
    }

    private function findUnfixedUnitConversionItems()
    {
        return PurchaseItem::with(['product', 'purchase'])
            ->get()
            ->filter(function (PurchaseItem $item) {
                $prod = $item->product;
                if (!$prod || !$item->purchase_id) {
                    return false;
                }
                $inputUnit = strtolower(trim($item->unit ?? ''));
                $masterUnit = strtolower(trim($prod->price_unit ?? ''));
                $isInputKg = in_array($inputUnit, ['kg', 'kilogram'], true);
                $isMasterGram = in_array($masterUnit, ['gram', 'gr', 'g'], true);
                if (!$isInputKg || !$isMasterGram) {
                    return false;
                }
                return !StockAdjustmentLog::where('reference_type', 'unit_conversion_fix')
                    ->where('reference_id', $item->purchase_id)
                    ->where('product_id', $prod->id)
                    ->exists();
            });
    }

    /**
     * Lightweight, shell-free PHP syntax tokenizer validator for compiled Blade output.
     */
    private function checkPhpSyntaxTokens(string $code): ?string
    {
        $tokens = @token_get_all($code);
        $paren = 0;
        $bracket = 0;
        $brace = 0;
        $controlStack = [];

        $openers = [
            T_IF => T_ENDIF,
            T_FOREACH => T_ENDFOREACH,
            T_FOR => T_ENDFOR,
            T_WHILE => T_ENDWHILE,
            T_SWITCH => T_ENDSWITCH,
        ];
        $closers = array_flip($openers);

        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $tok = $tokens[$i];
            if (is_string($tok)) {
                if ($tok === '(') {
                    $paren++;
                } elseif ($tok === ')') {
                    $paren--;
                    if ($paren < 0) {
                        return 'Kurung tutup `)` berlebih';
                    }
                } elseif ($tok === '[') {
                    $bracket++;
                } elseif ($tok === ']') {
                    $bracket--;
                    if ($bracket < 0) {
                        return 'Kurung siku `]` berlebih';
                    }
                } elseif ($tok === '{') {
                    $brace++;
                } elseif ($tok === '}') {
                    $brace--;
                    if ($brace < 0) {
                        return 'Kurung kurawal `}` berlebih';
                    }
                }
                continue;
            }

            [$id, $text, $line] = $tok;

            if ($id === T_CURLY_OPEN || $id === T_DOLLAR_OPEN_CURLY_BRACES) {
                $brace++;
                continue;
            }

            // Detect alternate-syntax blocks (used heavily by Laravel Blade): if(...): ... endif;
            if (isset($openers[$id])) {
                // Scan forward to matching `)` of condition, then check if next non-whitespace token is `:`
                $depth = 0;
                $j = $i + 1;
                while ($j < $count) {
                    $t = $tokens[$j];
                    if ($t === '(') {
                        $depth++;
                    } elseif ($t === ')') {
                        $depth--;
                        if ($depth === 0) {
                            $j++;
                            while ($j < $count && is_array($tokens[$j]) && $tokens[$j][0] === T_WHITESPACE) {
                                $j++;
                            }
                            if ($j < $count && $tokens[$j] === ':') {
                                $controlStack[] = [$openers[$id], $line, trim($text)];
                            }
                            break;
                        }
                    } elseif ($t === '{' && $depth === 0) {
                        break;
                    }
                    $j++;
                }
            } elseif (isset($closers[$id])) {
                if (empty($controlStack)) {
                    return "Tag penutup `{$text}` di baris ~{$line} tidak punya pembuka";
                }
                [$expectedClose] = array_pop($controlStack);
                if ($expectedClose !== $id) {
                    return "Tag penutup `{$text}` di baris ~{$line} tidak cocok";
                }
            }
        }

        if ($paren !== 0) {
            return 'Jumlah kurung `(` dan `)` tidak seimbang';
        }
        if ($bracket !== 0) {
            return 'Jumlah kurung siku `[` dan `]` tidak seimbang';
        }
        if ($brace !== 0) {
            return 'Jumlah kurung kurawal `{` dan `}` tidak seimbang';
        }
        if (!empty($controlStack)) {
            $unclosed = end($controlStack);
            return "Blok `@{$unclosed[2]}` (baris ~{$unclosed[1]}) belum ditutup";
        }

        return null;
    }

    private function calculateHealthScore(array $checks): int
    {
        $weights = [
            'database_migrations' => 25,
            'blade_integrity' => 25,
            'storage_and_logs' => 15,
            'stock_consistency' => 15,
            'dp_receivables_consistency' => 10,
            'cache_opcache' => 10,
        ];

        $score = 0;
        foreach ($checks as $key => $check) {
            $w = $weights[$key] ?? 10;
            if ($check['status'] === 'ok') {
                $score += $w;
            } elseif ($check['status'] === 'warning' || $check['status'] === 'skipped') {
                $score += (int) round($w * 0.5);
            }
        }

        return max(0, min(100, $score));
    }

    private function skippedCheck(string $key, string $title, string $summary): array
    {
        return [
            'key' => $key,
            'title' => $title,
            'status' => 'skipped',
            'repairable' => false,
            'summary' => $summary,
            'details' => [],
            'meta' => [],
        ];
    }

    private function readFileTail(string $path, int $maxBytes): string
    {
        $handle = @fopen($path, 'rb');
        if (!$handle) {
            return '';
        }
        $size = @filesize($path) ?: 0;
        if ($size > $maxBytes) {
            @fseek($handle, -$maxBytes, SEEK_END);
            // Discard partial first line
            @fgets($handle);
        }
        $data = @stream_get_contents($handle) ?: '';
        @fclose($handle);
        return $data;
    }

    private function pruneOldArchives(string $dir, string $pattern, int $keep): void
    {
        $files = glob($dir . DIRECTORY_SEPARATOR . $pattern) ?: [];
        if (count($files) <= $keep) {
            return;
        }
        usort($files, fn ($a, $b) => @filemtime($b) <=> @filemtime($a));
        foreach (array_slice($files, $keep) as $old) {
            @unlink($old);
        }
    }

    private function isDirectoryWritable(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }
        $probe = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . '.repair_write_probe_' . uniqid('', true);
        $ok = @file_put_contents($probe, 'ok');
        if ($ok !== false) {
            @unlink($probe);
            return true;
        }
        return is_writable($dir);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return round($bytes / 1048576, 2) . ' MB';
    }
}
