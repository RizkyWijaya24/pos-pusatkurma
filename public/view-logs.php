<?php
/**
 * EMERGENCY DIAGNOSTICS & LOG VIEWER - Pusat Kurma POS
 * Upload file ini ke: public/view-logs.php
 * Akses via browser: https://pusatkurmacianjur.my.id/view-logs.php?token=pk2026
 * HAPUS file ini setelah selesai digunakan!
 */

$token = $_GET['token'] ?? '';
if ($token !== 'pk2026') {
    die('Akses ditolak. Tambahkan ?token=pk2026 di URL.');
}

// EMERGENCY SHELL RUNNER (Runs before Laravel boots to avoid 500 errors preventing execution)
$shellMessage = '';
$shellOutput = '';
if (isset($_GET['shell_action'])) {
    $action = $_GET['shell_action'];
    $cmd = '';
    
    // Auto-detect project root folder relative to view-logs.php
    $projectRoot = null;
    if (file_exists(__DIR__ . '/../pos-pusatkurma/artisan')) {
        $projectRoot = realpath(__DIR__ . '/../pos-pusatkurma');
    } elseif (file_exists(__DIR__ . '/../kasir-pk/artisan')) {
        $projectRoot = realpath(__DIR__ . '/../kasir-pk');
    } elseif (file_exists(__DIR__ . '/../artisan')) {
        $projectRoot = realpath(__DIR__ . '/..');
    } elseif (file_exists(__DIR__ . '/artisan')) {
        $projectRoot = realpath(__DIR__);
    }
    
    if (!$projectRoot) {
        $shellMessage = '❌ Gagal mendeteksi folder root project (artisan tidak ditemukan)';
    } else {
        // Detect PHP binary
        $phpBinary = 'php';
        // Common cPanel PHP paths (prefer PHP 8.2+)
        $possiblePhp = [
            '/usr/local/bin/ea-php82',
            '/usr/local/bin/ea-php83',
            '/opt/cpanel/ea-php82/root/usr/bin/php',
            '/opt/cpanel/ea-php83/root/usr/bin/php',
            '/usr/local/bin/php',
        ];
        foreach ($possiblePhp as $p) {
            if (@file_exists($p) && @is_executable($p)) {
                $phpBinary = $p;
                break;
            }
        }
        
        // Detect Composer binary
        $composerBinary = 'composer';
        $possibleComposer = [
            '/usr/local/bin/composer',
            $projectRoot . '/composer.phar',
        ];
        foreach ($possibleComposer as $c) {
            if (@file_exists($c)) {
                $composerBinary = $c;
                break;
            }
        }
        
        if ($action === 'version_check') {
            $cmd = "cd " . escapeshellarg($projectRoot) . " && $phpBinary -v && echo '---' && $phpBinary $composerBinary --version 2>&1";
        } elseif ($action === 'composer_install') {
            // Run composer install with PHP memory limit configuration
            $cmd = "cd " . escapeshellarg($projectRoot) . " && $phpBinary -d memory_limit=-1 $composerBinary install --no-dev --no-interaction --optimize-autoloader 2>&1";
        } elseif ($action === 'artisan_migrate') {
            $cmd = "cd " . escapeshellarg($projectRoot) . " && $phpBinary artisan migrate --force 2>&1";
        } elseif ($action === 'artisan_clear') {
            $cmd = "cd " . escapeshellarg($projectRoot) . " && $phpBinary artisan optimize:clear 2>&1";
        } elseif ($action === 'custom_command' && !empty($_POST['custom_cmd'])) {
            $cmd = "cd " . escapeshellarg($projectRoot) . " && " . $_POST['custom_cmd'] . " 2>&1";
        }
        
        if ($cmd) {
            $shellMessage = "Mengeksekusi: <code>" . htmlspecialchars($cmd) . "</code>";
            $output = shell_exec($cmd);
            $shellOutput = $output ? $output : "(Tidak ada output / Eksekusi selesai)";
            
            // RENDER A CLEAN OUTPUT PAGE AND EXIT IMMEDIATELY TO AVOID LARAVEL BOOT 500 ERROR!
            ?>
            <!DOCTYPE html>
            <html lang="id">
            <head>
                <meta charset="UTF-8">
                <title>Shell Output - Diagnostics</title>
                <style>
                    body { font-family: sans-serif; background: #0f172a; color: #cbd5e1; padding: 20px; }
                    pre { background: #020617; border: 1px solid #1e293b; border-radius: 12px; padding: 20px; overflow-x: auto; white-space: pre-wrap; font-family: monospace; font-size: 12px; color: #a7f3d0; line-height: 1.5; box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.6); }
                    .card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 20px; margin-bottom: 20px; }
                    .btn { display: inline-block; padding: 8px 16px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 12px; transition: 0.2s; }
                    .btn:hover { background: #1d4ed8; }
                </style>
            </head>
            <body>
                <div class="card">
                    <h2>📟 Hasil Eksekusi Command</h2>
                    <p style="color: #38bdf8; font-weight: bold;"><?php echo $shellMessage; ?></p>
                    <pre><?php echo htmlspecialchars($shellOutput); ?></pre>
                    <br>
                    <a href="?token=pk2026" class="btn">← Kembali ke Halaman Utama Diagnosa</a>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

// Auto-detect Struktur Direktori cPanel
$bootstrapPath = null;
$vendorPath = null;
$logFile = null;
$diagnostics = [];
$uploadedFiles = [];
$orphanedFiles = [];

// 1. Coba deteksi via Jalur Kustom cPanel /pos-pusatkurma/ (Sesuai dengan isi index.php)
if (file_exists(__DIR__ . '/../pos-pusatkurma/bootstrap/app.php')) {
    $bootstrapPath = __DIR__ . '/../pos-pusatkurma/bootstrap/app.php';
    $vendorPath = __DIR__ . '/../pos-pusatkurma/vendor/autoload.php';
    $logFile = __DIR__ . '/../pos-pusatkurma/storage/logs/laravel.log';
    $diagnostics[] = "ℹ️  Struktur Direktori: Kustom cPanel (/../pos-pusatkurma/)";
}
// 2. Coba deteksi via Jalur Kustom cPanel /kasir-pk/
elseif (file_exists(__DIR__ . '/../kasir-pk/bootstrap/app.php')) {
    $bootstrapPath = __DIR__ . '/../kasir-pk/bootstrap/app.php';
    $vendorPath = __DIR__ . '/../kasir-pk/vendor/autoload.php';
    $logFile = __DIR__ . '/../kasir-pk/storage/logs/laravel.log';
    $diagnostics[] = "ℹ️  Struktur Direktori: Kustom cPanel (/../kasir-pk/)";
}
// 3. Fallback ke struktur standar
elseif (file_exists(__DIR__ . '/../bootstrap/app.php')) {
    $bootstrapPath = __DIR__ . '/../bootstrap/app.php';
    $vendorPath = __DIR__ . '/../vendor/autoload.php';
    $logFile = __DIR__ . '/../storage/logs/laravel.log';
    $diagnostics[] = "ℹ️  Struktur Direktori: Standar (Folder Laravel di luar public/)";
}
// 4. Fallback ke struktur flat
elseif (file_exists(__DIR__ . '/bootstrap/app.php')) {
    $bootstrapPath = __DIR__ . '/bootstrap/app.php';
    $vendorPath = __DIR__ . '/vendor/autoload.php';
    $logFile = __DIR__ . '/storage/logs/laravel.log';
    $diagnostics[] = "ℹ️  Struktur Direktori: Flat (Semua folder digabung di root public_html/)";
}

// Jalankan Diagnosa
$bootLaravel = isset($_GET['boot_laravel']) && $_GET['boot_laravel'] === '1';

if ($bootLaravel && $bootstrapPath && file_exists($bootstrapPath) && file_exists($vendorPath)) {
    try {
        require_once $vendorPath;
        $app = require_once $bootstrapPath;
        $app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
        
        // AKSI PEMBUATAN SYMLINK OTOMATIS
        $action = $_GET['action'] ?? '';
        if ($action === 'make_link') {
            try {
                $storageLink = public_path('storage');
                if (file_exists($storageLink) || is_link($storageLink)) {
                    if (is_link($storageLink)) {
                        unlink($storageLink);
                    } else {
                        @rmdir($storageLink);
                    }
                }
                \Illuminate\Support\Facades\Artisan::call('storage:link');
            } catch (\Throwable $e1) {
                try {
                    $target = storage_path('app/public');
                    $shortcut = public_path('storage');
                    symlink($target, $shortcut);
                } catch (\Throwable $e2) {}
            }
        }

        // AKSI MIGRASI FOTO LAMA KE PUBLIC_HTML
        if ($action === 'migrate_photos') {
            $oldDir = realpath(__DIR__ . '/../pos-pusatkurma/public/storage/products');
            $newDir = public_path('storage/products');
            if ($oldDir && is_dir($oldDir)) {
                if (!file_exists($newDir)) {
                    mkdir($newDir, 0755, true);
                }
                $movedCount = 0;
                $files = glob($oldDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $dest = $newDir . '/' . basename($file);
                        if (!file_exists($dest)) {
                            if (copy($file, $dest)) {
                                $movedCount++;
                            }
                        }
                    }
                }
                $diagnostics[] = "🎉 Sukses menyalin $movedCount foto lama dari pos-pusatkurma/public ke public_html/storage!";
            } else {
                $diagnostics[] = "⚠️ Folder lama tidak ditemukan atau kosong. Tidak ada foto yang perlu disalin.";
            }
        }

        // AKSI HAPUS FOTO YATIM (TANPA PRODUK)
        if ($action === 'clean_orphaned') {
            $deletedCount = 0;
            $productsDir = public_path('storage/products');
            if (is_dir($productsDir)) {
                $activeImages = \App\Models\Product::whereNotNull('image_path')->pluck('image_path')->toArray();
                $activeFilenames = array_map(function($path) {
                    return basename($path);
                }, $activeImages);
                
                $files = glob($productsDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        $filename = basename($file);
                        if (!in_array($filename, $activeFilenames)) {
                            if (@unlink($file)) {
                                $deletedCount++;
                            }
                        }
                    }
                }
                $diagnostics[] = "🧹 Sukses menghapus $deletedCount foto yatim (tanpa produk) dari folder publik!";
            } else {
                $diagnostics[] = "⚠️ Folder produk aktif tidak ditemukan.";
            }
        }

        // 0. Periksa Jalur Folder Publik Aktif
        $diagnostics[] = "ℹ️  Laravel public_path(): " . public_path();

        // 1. Periksa $fillable pada Model Product
        $productModel = new \App\Models\Product();
        $fillables = $productModel->getFillable();
        if (in_array('image_path', $fillables)) {
            $diagnostics[] = "✅ Model Product: 'image_path' SUDAH ada di \$fillable";
        } else {
            $diagnostics[] = "❌ Model Product: 'image_path' BELUM ada di \$fillable! UPLOAD file app/Models/Product.php terbaru!";
        }
        
        // 2. Periksa Kolom database
        if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'image_path')) {
            $diagnostics[] = "✅ Database: Kolom 'image_path' SUDAH ada di tabel products";
        } else {
            $diagnostics[] = "❌ Database: Kolom 'image_path' TIDAK DITEMUKAN di tabel products!";
        }

        // 3. Periksa public/storage symlink
        $storageLink = public_path('storage');
        if (file_exists($storageLink)) {
            $diagnostics[] = "✅ Symlink: Folder link/direktori public/storage SUDAH ada";
        } else {
            $diagnostics[] = "❌ Symlink: Link public/storage belum dibuat! Fotonya tidak akan bisa diakses jika link ini belum ada.";
        }
        
        // 4. Daftar berkas foto produk terunggah secara fisik
        $productsDir = public_path('storage/products');
        if (is_dir($productsDir)) {
            // Ambil daftar file aktif dari database
            $activeImages = \App\Models\Product::whereNotNull('image_path')->pluck('image_path')->toArray();
            $activeFilenames = array_map(function($path) {
                return basename($path);
            }, $activeImages);

            $files = glob($productsDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    $fileSizeKB = round(filesize($file)/1024, 1);
                    if (in_array($filename, $activeFilenames)) {
                        $uploadedFiles[] = $filename . ' (' . $fileSizeKB . ' KB)';
                    } else {
                        $orphanedFiles[] = $filename . ' (' . $fileSizeKB . ' KB)';
                    }
                }
            }
        }
        
    } catch (\Throwable $e) {
        $diagnostics[] = "⚠️ Diagnosa Laravel tertunda: " . $e->getMessage();
    }
} else {
    // RUN PLAIN PHP DIAGNOSTICS!
    $diagnostics[] = "ℹ️  Menjalankan diagnosa dalam Mode Aman (Plain PHP). Laravel tidak di-boot.";
    $diagnostics[] = "👉 Untuk mencoba memuat penuh Laravel (uji boot), tambahkan <a href='?token=pk2026&boot_laravel=1' style='color:#60a5fa; font-weight:bold;'>&boot_laravel=1</a> di URL.";
    
    // 0. Periksa Jalur Folder Publik
    $diagnostics[] = "ℹ️  Jalur Publik: " . __DIR__;
    
    // 1. Periksa $fillable pada Model Product (via membaca file)
    $productModelFile = $projectRoot ? $projectRoot . '/app/Models/Product.php' : null;
    if ($productModelFile && file_exists($productModelFile)) {
        $productContent = file_get_contents($productModelFile);
        if (strpos($productContent, 'image_path') !== false) {
            $diagnostics[] = "✅ Model Product: 'image_path' SUDAH ada di \$fillable";
        } else {
            $diagnostics[] = "❌ Model Product: 'image_path' BELUM ada di \$fillable! Silakan perbarui app/Models/Product.php!";
        }
    } else {
        $diagnostics[] = "⚠️ File Model Product tidak dapat dideteksi.";
    }
    
    // 2. Periksa Database via PDO dan ambil berkas foto aktif
    $dbHost = ''; $dbName = ''; $dbUser = ''; $dbPass = ''; $dbPort = '3306';
    if ($projectRoot && file_exists($projectRoot . '/.env')) {
        $envLines = file($projectRoot . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($envLines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                $val = trim($val, '"\'');
                if ($key === 'DB_HOST') $dbHost = $val;
                elseif ($key === 'DB_DATABASE') $dbName = $val;
                elseif ($key === 'DB_USERNAME') $dbUser = $val;
                elseif ($key === 'DB_PASSWORD') $dbPass = $val;
                elseif ($key === 'DB_PORT') $dbPort = $val;
            }
        }
    }
    
    $activeFilenames = [];
    $dbOk = false;
    if ($dbHost && $dbName && $dbUser) {
        try {
            $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 3
            ]);
            
            // Check column image_path in products
            $stmt = $pdo->query("DESCRIBE products");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('image_path', $columns)) {
                $diagnostics[] = "✅ Database: Kolom 'image_path' SUDAH ada di tabel products";
            } else {
                $diagnostics[] = "❌ Database: Kolom 'image_path' TIDAK DITEMUKAN di tabel products!";
            }
            $dbOk = true;
            
            // Fetch active images for file listing
            $stmtImage = $pdo->query("SELECT image_path FROM products WHERE image_path IS NOT NULL");
            $activeImages = $stmtImage->fetchAll(PDO::FETCH_COLUMN);
            $activeFilenames = array_map(function($path) {
                return basename($path);
            }, $activeImages);
        } catch (\Throwable $dbEx) {
            $diagnostics[] = "⚠️ Database (Plain PHP): Gagal koneksi database - " . $dbEx->getMessage();
        }
    } else {
        $diagnostics[] = "⚠️ Database (Plain PHP): Konfigurasi database di .env tidak lengkap.";
    }
    
    // 3. Periksa public/storage symlink
    $storageLink = __DIR__ . '/storage';
    if (file_exists($storageLink)) {
        $diagnostics[] = "✅ Symlink: Folder link/direktori public/storage SUDAH ada";
    } else {
        $diagnostics[] = "❌ Symlink: Link public/storage belum dibuat! Fotonya tidak akan bisa diakses jika link ini belum ada.";
    }
    
    // 4. Daftar berkas foto terunggah
    $productsDir = __DIR__ . '/storage/products';
    if (is_dir($productsDir)) {
        $files = glob($productsDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                $filename = basename($file);
                $fileSizeKB = round(filesize($file)/1024, 1);
                if (in_array($filename, $activeFilenames)) {
                    $uploadedFiles[] = $filename . ' (' . $fileSizeKB . ' KB)';
                } else {
                    $orphanedFiles[] = $filename . ' (' . $fileSizeKB . ' KB)';
                }
            }
        }
    }
    
    // Action handling for non-Laravel actions
    $action = $_GET['action'] ?? '';
    if ($action === 'make_link') {
        $target = $projectRoot ? $projectRoot . '/storage/app/public' : null;
        if ($target && file_exists($target)) {
            if (file_exists($storageLink) || is_link($storageLink)) {
                if (is_link($storageLink)) {
                    unlink($storageLink);
                } else {
                    @rmdir($storageLink);
                }
            }
            if (@symlink($target, $storageLink)) {
                header("Location: ?token=pk2026");
                exit;
            } else {
                $diagnostics[] = "❌ Gagal membuat symlink via PHP. Coba buat link manual di file manager cPanel.";
            }
        } else {
            $diagnostics[] = "❌ Folder target penyimpanan tidak ditemukan: " . htmlspecialchars($target);
        }
    }
    
    if ($action === 'migrate_photos') {
        $oldDir = $projectRoot ? realpath($projectRoot . '/public/storage/products') : null;
        $newDir = __DIR__ . '/storage/products';
        if ($oldDir && is_dir($oldDir)) {
            if (!file_exists($newDir)) {
                mkdir($newDir, 0755, true);
            }
            $movedCount = 0;
            $files = glob($oldDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $dest = $newDir . '/' . basename($file);
                    if (!file_exists($dest)) {
                        if (copy($file, $dest)) {
                            $movedCount++;
                        }
                    }
                }
            }
            $diagnostics[] = "🎉 Sukses menyalin $movedCount foto lama dari pos-pusatkurma/public ke public_html/storage!";
        } else {
            $diagnostics[] = "⚠️ Folder lama tidak ditemukan atau kosong. Tidak ada foto yang perlu disalin.";
        }
    }
    
    if ($action === 'clean_orphaned' && $dbOk) {
        $deletedCount = 0;
        $productsDir = __DIR__ . '/storage/products';
        if (is_dir($productsDir)) {
            $files = glob($productsDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filename = basename($file);
                    if (!in_array($filename, $activeFilenames)) {
                        if (@unlink($file)) {
                            $deletedCount++;
                        }
                    }
                }
            }
            $diagnostics[] = "🧹 Sukses menghapus $deletedCount foto yatim (tanpa produk) dari folder publik!";
        }
    }
}

// Muat data Log
$logContent = "File log (laravel.log) tidak ditemukan atau masih kosong.";
if ($logFile && file_exists($logFile) && filesize($logFile) > 0) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -150); // Tampilkan 150 baris terakhir
    $logContent = implode("", $lastLines);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log & Diagnostics - Pusat Kurma POS</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: #cbd5e1; margin: 0; padding: 20px; }
        h1 { color: #f8fafc; font-size: 20px; border-bottom: 1px solid #334155; padding-bottom: 10px; margin-bottom: 15px; }
        h2 { color: #f8fafc; font-size: 16px; margin-top: 25px; margin-bottom: 10px; }
        pre { background: #020617; border: 1px solid #1e293b; border-radius: 12px; padding: 20px; overflow-x: auto; white-space: pre-wrap; font-family: monospace; font-size: 12px; color: #a7f3d0; line-height: 1.5; box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.6); }
        .info { background: #1e293b; border-left: 4px solid #10b981; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; color: #94a3b8; }
        .diagnostic-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 15px; margin-bottom: 20px; }
        .diagnostic-item { padding: 6px 0; font-size: 13px; font-weight: bold; border-bottom: 1px solid #334155/30; }
        .diagnostic-item:last-child { border-bottom: none; }
        .btn-link, .btn-shell { display: inline-block; margin-top: 10px; padding: 8px 16px; background: #2563eb; color: white; text-decoration: none; border: none; cursor: pointer; border-radius: 8px; font-weight: bold; font-size: 12px; transition: 0.2s; }
        .btn-link:hover, .btn-shell:hover { background: #1d4ed8; }
        .btn-shell-danger { background: #dc2626; }
        .btn-shell-danger:hover { background: #b91c1c; }
        .btn-shell-success { background: #059669; }
        .btn-shell-success:hover { background: #047857; }
        .btn-shell-info { background: #0891b2; }
        .btn-shell-info:hover { background: #0b7285; }
        .shell-cmd-box { margin-top: 15px; display: flex; gap: 10px; }
        .shell-cmd-input { flex: 1; padding: 8px 12px; border-radius: 8px; border: 1px solid #334155; background: #020617; color: #a7f3d0; font-family: monospace; font-size: 13px; }
        a { display: inline-block; margin-top: 15px; padding: 8px 16px; background: #047857; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; transition: 0.2s; }
        a:hover { background: #065f46; }
    </style>
</head>
<body>
    <h1>📋 Diagnostics & Log Viewer - Pusat Kurma POS</h1>

    <h2>⚡ Emergency Shell Runner (Gunakan Jika Site Error 500 / Tidak Bisa SSH Terminal):</h2>
    <div class="diagnostic-card" style="border-color: #e11d48; background: rgba(225, 29, 72, 0.05);">
        <span style="font-size: 13px; color: #fda4af; font-weight: bold; display: block; margin-bottom: 12px;">
            ⚠️ PERINGATAN: Menu ini mengeksekusi shell command di server production. Gunakan hanya jika cPanel Terminal tidak bekerja/tidak bisa diakses.
        </span>
        
        <div style="display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 15px;">
            <a href="?token=pk2026&shell_action=version_check" class="btn-shell btn-shell-info">🔍 1. Cek Versi PHP & Composer</a>
            <a href="?token=pk2026&shell_action=composer_install" class="btn-shell btn-shell-danger" onclick="return confirm('Proses composer install mungkin butuh waktu beberapa detik. Lanjutkan?');">📦 2. Jalankan Composer Install</a>
            <a href="?token=pk2026&shell_action=artisan_migrate" class="btn-shell btn-shell-success" onclick="return confirm('Jalankan migrasi database sekarang?');">🗄️ 3. Jalankan Artisan Migrate</a>
            <a href="?token=pk2026&shell_action=artisan_clear" class="btn-shell">🧹 4. Bersihkan Cache Laravel</a>
        </div>

        <form method="POST" action="?token=pk2026&shell_action=custom_command" class="shell-cmd-box">
            <input type="text" name="custom_cmd" class="shell-cmd-input" placeholder="Masukkan custom shell command (contoh: ls -la atau php artisan list)..." required>
            <button type="submit" class="btn-shell btn-shell-info">Kirim Command</button>
        </form>

        <?php if ($shellMessage): ?>
            <div style="margin-top: 15px; padding: 12px; background: #1e293b; border-radius: 8px; font-size: 13px;">
                <p style="margin: 0 0 8px 0; font-weight: bold; color: #38bdf8;"><?php echo $shellMessage; ?></p>
                <pre style="margin: 0; background: #020617; max-height: 300px; overflow-y: auto;"><?php echo htmlspecialchars($shellOutput); ?></pre>
            </div>
        <?php endif; ?>
    </div>
    
    <h2>🔍 Hasil Diagnosa Otomatis Sistem:</h2>
    <div class="diagnostic-card">
        <?php foreach ($diagnostics as $item): ?>
            <div class="diagnostic-item"><?php echo $item; ?></div>
        <?php endforeach; ?>
        
        <?php if (!file_exists(public_path('storage'))): ?>
            <div style="margin-top: 15px; padding: 12px; background: rgba(59, 130, 246, 0.1); border: 1px dashed #2563eb; border-radius: 8px;">
                <span style="font-size: 12px; color: #60a5fa; font-weight: bold; display: block; margin-bottom: 8px;">👉 Link penyimpanan belum dibuat. Klik tombol di bawah untuk membuat secara otomatis:</span>
                <a href="?token=pk2026&action=make_link" class="btn-link">Buat Symlink Storage Otomatis</a>
            </div>
        <?php endif; ?>

        <div style="margin-top: 15px; padding: 12px; background: rgba(16, 185, 129, 0.1); border: 1px dashed #10b981; border-radius: 8px; display: flex; flex-wrap: wrap; gap: 15px;">
            <div style="flex: 1; min-width: 250px;">
                <span style="font-size: 12px; color: #34d399; font-weight: bold; display: block; margin-bottom: 8px;">👉 Salin/Migrasikan foto produk dari folder lama (pos-pusatkurma/public) ke folder publik aktif (public_html/storage) secara otomatis:</span>
                <a href="?token=pk2026&action=migrate_photos" class="btn-link" style="background: #10b981;">Salin/Migrasi Foto Lama ke Folder Publik Aktif</a>
            </div>
            
            <div style="flex: 1; min-width: 250px; border-left: 1px dashed #334155; padding-left: 15px;">
                <span style="font-size: 12px; color: #f87171; font-weight: bold; display: block; margin-bottom: 8px;">👉 Hapus semua berkas foto produk yatim (yang produknya sudah dihapus dari sistem) untuk hemat memori hosting:</span>
                <a href="?token=pk2026&action=clean_orphaned" class="btn-link" style="background: #ef4444;" onclick="return confirm('Apakah Anda yakin ingin menghapus semua foto yang tidak memiliki produk?');">Bersihkan & Hapus Foto Yatim</a>
            </div>
        </div>
    </div>

    <h2>📁 Berkas Foto Terunggah di Hosting:</h2>
    <div style="display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px;">
        <!-- Foto Aktif -->
        <div style="flex: 1; min-width: 300px;" class="diagnostic-card">
            <h3 style="color: #34d399; font-size: 14px; margin-top: 0; border-bottom: 1px solid #334155; padding-bottom: 8px; margin-bottom: 10px;">✅ Foto Aktif (Dipakai Produk)</h3>
            <?php if (empty($uploadedFiles)): ?>
                <div class="diagnostic-item" style="color: #94a3b8; font-style: italic;">Belum ada berkas foto aktif yang tersimpan.</div>
            <?php else: ?>
                <div style="max-height: 250px; overflow-y: auto;">
                    <?php foreach ($uploadedFiles as $f): ?>
                        <div class="diagnostic-item" style="color: #34d399;">📷 <?php echo $f; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Foto Yatim -->
        <div style="flex: 1; min-width: 300px;" class="diagnostic-card">
            <h3 style="color: #f87171; font-size: 14px; margin-top: 0; border-bottom: 1px solid #334155; padding-bottom: 8px; margin-bottom: 10px;">⚠️ Foto Yatim (Tanpa Produk / Terbengkalai)</h3>
            <?php if (empty($orphanedFiles)): ?>
                <div class="diagnostic-item" style="color: #34d399; font-style: italic;">🎉 Bersih! Tidak ada foto yatim di folder publik Anda.</div>
            <?php else: ?>
                <div style="max-height: 250px; overflow-y: auto;">
                    <?php foreach ($orphanedFiles as $fo): ?>
                        <div class="diagnostic-item" style="color: #f87171;">⚠️ <?php echo $fo; ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <h2>📄 Log Aktivitas Server Terbaru:</h2>
    <div class="info">
        Menampilkan 150 baris terakhir log aktivitas server di hosting.
    </div>
    <pre><?php echo htmlspecialchars($logContent); ?></pre>
    
    <a href="/admin/dashboard">← Kembali ke Dashboard Admin</a>
</body>
</html>
