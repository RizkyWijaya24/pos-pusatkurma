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

// Auto-detect Struktur Direktori cPanel
$bootstrapPath = null;
$vendorPath = null;
$logFile = null;
$diagnostics = [];
$uploadedFiles = [];

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

// Jalankan Diagnosa jika bootstrap ditemukan
if ($bootstrapPath && file_exists($bootstrapPath) && file_exists($vendorPath)) {
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
            $files = glob($productsDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $uploadedFiles[] = basename($file) . ' (' . round(filesize($file)/1024, 1) . ' KB)';
                }
            }
        }
        
    } catch (\Throwable $e) {
        $diagnostics[] = "⚠️ Diagnosa Laravel tertunda: " . $e->getMessage();
    }
} else {
    $diagnostics[] = "❌ Gagal memuat bootstrap Laravel untuk diagnosa. Jalur tidak ditemukan.";
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
        .btn-link { display: inline-block; margin-top: 10px; padding: 8px 16px; background: #2563eb; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 12px; transition: 0.2s; }
        .btn-link:hover { background: #1d4ed8; }
        a { display: inline-block; margin-top: 15px; padding: 8px 16px; background: #047857; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; transition: 0.2s; }
        a:hover { background: #065f46; }
    </style>
</head>
<body>
    <h1>📋 Diagnostics & Log Viewer - Pusat Kurma POS</h1>
    
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
    </div>

    <h2>📁 Berkas Foto Terunggah di Hosting:</h2>
    <div class="diagnostic-card">
        <?php if (empty($uploadedFiles)): ?>
            <div class="diagnostic-item" style="color: #f87171;">❌ Belum ada berkas foto yang tersimpan di folder public/storage/products/</div>
        <?php else: ?>
            <?php foreach ($uploadedFiles as $f): ?>
                <div class="diagnostic-item" style="color: #34d399;">📷 <?php echo $f; ?></div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <h2>📄 Log Aktivitas Server Terbaru:</h2>
    <div class="info">
        Menampilkan 150 baris terakhir log aktivitas server di hosting.
    </div>
    <pre><?php echo htmlspecialchars($logContent); ?></pre>
    
    <a href="/admin/dashboard">← Kembali ke Dashboard Admin</a>
</body>
</html>
