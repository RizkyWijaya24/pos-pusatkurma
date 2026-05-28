<?php
/**
 * EMERGENCY LOG VIEWER - Pusat Kurma POS
 * Upload file ini ke: public/view-logs.php
 * Akses via browser: https://pusatkurmacianjur.my.id/view-logs.php?token=pk2026
 * HAPUS file ini setelah selesai digunakan!
 */

$token = $_GET['token'] ?? '';
if ($token !== 'pk2026') {
    die('Akses ditolak. Tambahkan ?token=pk2026 di URL.');
}

$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logFile)) {
    die('File log (storage/logs/laravel.log) tidak ditemukan atau masih kosong.');
}

$lines = file($logFile);
$lastLines = array_slice($lines, -150); // Show last 150 lines of logs

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Log Viewer - Pusat Kurma POS</title>
    <style>
        body { font-family: sans-serif; background: #0f172a; color: #cbd5e1; margin: 0; padding: 20px; }
        h1 { color: #f8fafc; font-size: 20px; border-bottom: 1px solid #334155; padding-bottom: 10px; margin-bottom: 15px; }
        pre { background: #020617; border: 1px solid #1e293b; border-radius: 12px; padding: 20px; overflow-x: auto; white-space: pre-wrap; font-family: monospace; font-size: 12px; color: #a7f3d0; line-height: 1.5; box-shadow: inset 0 2px 4px 0 rgba(0,0,0,0.6); }
        .info { background: #1e293b; border-left: 4px solid #10b981; padding: 12px; border-radius: 8px; font-size: 13px; margin-bottom: 15px; color: #94a3b8; }
        .danger { border-left-color: #ef4444; }
        a { display: inline-block; margin-top: 15px; padding: 8px 16px; background: #047857; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 13px; transition: 0.2s; }
        a:hover { background: #065f46; }
    </style>
</head>
<body>
    <h1>📋 Log Viewer - Pusat Kurma POS</h1>
    <div class="info">
        Menampilkan 150 baris terakhir log aktivitas server di hosting.
    </div>
    <pre><?php echo htmlspecialchars(implode("", $lastLines)); ?></pre>
    <a href="/admin/dashboard">← Kembali ke Dashboard Admin</a>
</body>
</html>
