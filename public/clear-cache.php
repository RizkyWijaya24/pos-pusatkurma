<?php
/**
 * EMERGENCY CACHE CLEAR - Pusat Kurma POS
 * Upload file ini ke: public/clear-cache.php
 * Akses via browser: https://pusatkurmacianjur.my.id/kasir/clear-cache.php
 * HAPUS file ini setelah selesai digunakan!
 */

// Simple security token - change this if you want
$token = $_GET['token'] ?? '';
if ($token !== 'pk2026') {
    die('Akses ditolak. Tambahkan ?token=pk2026 di URL.');
}

$results = [];

// 1. Clear View Cache (storage/framework/views/*.php)
$viewsPath = __DIR__ . '/../storage/framework/views';
if (is_dir($viewsPath)) {
    $files = glob($viewsPath . '/*.php');
    $count = 0;
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            $count++;
        }
    }
    $results[] = "✅ View cache: $count file dihapus dari storage/framework/views/";
} else {
    $results[] = "❌ Folder storage/framework/views tidak ditemukan di: $viewsPath";
}

// 2. Clear Config Cache
$configCache = __DIR__ . '/../bootstrap/cache/config.php';
if (file_exists($configCache)) {
    unlink($configCache);
    $results[] = "✅ Config cache dihapus";
} else {
    $results[] = "ℹ️  Config cache tidak ada (OK)";
}

// 3. Clear Route Cache
$routeCache = __DIR__ . '/../bootstrap/cache/routes-v7.php';
if (file_exists($routeCache)) {
    unlink($routeCache);
    $results[] = "✅ Route cache dihapus";
} else {
    // try other route cache filenames
    $routeCache2 = __DIR__ . '/../bootstrap/cache/routes.php';
    if (file_exists($routeCache2)) {
        unlink($routeCache2);
        $results[] = "✅ Route cache dihapus";
    } else {
        $results[] = "ℹ️  Route cache tidak ada (OK)";
    }
}

// 4. Check if dashboard.blade.php exists and show its modification time
$dashboardFile = __DIR__ . '/../resources/views/kasir/dashboard.blade.php';
if (file_exists($dashboardFile)) {
    $mtime = date('d-m-Y H:i:s', filemtime($dashboardFile));
    $results[] = "ℹ️  dashboard.blade.php: last modified $mtime";
    
    // Check if our fix is in the file
    $content = file_get_contents($dashboardFile);
    if (strpos($content, 'background-color: #1b4332') !== false) {
        $results[] = "✅ Fix gambar produk (hijau gelap) SUDAH ada di file";
    } else {
        $results[] = "❌ Fix gambar produk BELUM ada - upload dashboard.blade.php terbaru!";
    }
    
    if (strpos($content, 'return this.products.filter') !== false && strpos($content, 'return this.products;') === false) {
        $results[] = "✅ Fix filteredProducts (Semua category) SUDAH ada";
    } else {
        $results[] = "❌ Fix filteredProducts BELUM ada - upload dashboard.blade.php terbaru!";
    }
} else {
    $results[] = "❌ dashboard.blade.php tidak ditemukan!";
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cache Clear - Pusat Kurma POS</title>
    <style>
        body { font-family: sans-serif; max-width: 600px; margin: 40px auto; padding: 20px; background: #f0fdf4; }
        h1 { color: #065f46; }
        .result { background: white; border: 1px solid #d1fae5; border-radius: 8px; padding: 16px; margin: 8px 0; font-size: 14px; }
        .success { color: #065f46; }
        .warning { background: #fffbeb; border-color: #fde68a; }
        a { display: block; margin-top: 20px; padding: 12px; background: #059669; color: white; text-align: center; border-radius: 8px; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🌴 Cache Clear - Pusat Kurma POS</h1>
    <?php foreach ($results as $r): ?>
        <div class="result"><?= htmlspecialchars($r) ?></div>
    <?php endforeach; ?>
    <p style="color:#6b7280; font-size:12px; margin-top:20px;">⚠️ Setelah selesai, HAPUS file clear-cache.php dari public/ untuk keamanan!</p>
    <a href="/kasir/dashboard">→ Buka Dashboard Kasir</a>
</body>
</html>
