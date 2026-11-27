<?php

declare(strict_types=1);

/**
 * PRE-BOOT SYSTEM RESCUE - Pusat Kurma POS
 * Standalone emergency recovery endpoint that runs BEFORE Laravel boots.
 * Works even when Laravel returns HTTP 500 due to corrupt caches or syntax errors.
 */

header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$detectProjectRoot = static function (): ?string {
    $candidates = [
        __DIR__ . '/..',
        __DIR__ . '/../pos-pusatkurma',
        __DIR__ . '/../kasir-pk',
        __DIR__,
    ];
    foreach ($candidates as $path) {
        $real = @realpath($path);
        if ($real && is_file($real . DIRECTORY_SEPARATOR . 'artisan')) {
            return $real;
        }
    }
    return null;
};

$projectRoot = $detectProjectRoot();
if (!$projectRoot) {
    http_response_code(500);
    exit('Root project Laravel (artisan) tidak ditemukan.');
}

$readEnvValue = static function (string $envPath, string $key): string {
    if (!is_file($envPath) || !is_readable($envPath)) {
        return '';
    }
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }
        $pos = strpos($trimmed, '=');
        if ($pos === false) {
            continue;
        }
        $k = trim(substr($trimmed, 0, $pos));
        if ($k !== $key) {
            continue;
        }
        $val = trim(substr($trimmed, $pos + 1));
        if (
            (str_starts_with($val, '"') && str_ends_with($val, '"'))
            || (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            $val = substr($val, 1, -1);
        }
        return $val;
    }
    return '';
};

$expectedKey = $readEnvValue($projectRoot . DIRECTORY_SEPARATOR . '.env', 'REPAIR_RESCUE_KEY');
$providedKey = (string) ($_POST['key'] ?? $_GET['key'] ?? '');

if ($expectedKey === '') {
    http_response_code(403);
    exit('Pre-Boot Rescue belum diaktifkan. Aktifkan terlebih dahulu dari Dashboard Owner -> Kesehatan & Self-Repair.');
}

if ($providedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    http_response_code(403);
    exit('Akses ditolak: Rescue key tidak valid.');
}

$detectPhpBinary = static function (): string {
    $candidates = [
        '/usr/local/bin/ea-php83',
        '/usr/local/bin/ea-php82',
        '/opt/cpanel/ea-php83/root/usr/bin/php',
        '/opt/cpanel/ea-php82/root/usr/bin/php',
        '/usr/local/bin/php',
        PHP_BINARY,
    ];
    foreach ($candidates as $bin) {
        if ($bin && @is_file($bin) && @is_executable($bin)) {
            return $bin;
        }
    }
    return 'php';
};

$clearSystemCaches = static function (string $root): array {
    $steps = [];

    $bootstrapRemoved = 0;
    foreach (['config.php', 'routes-v7.php', 'routes.php', 'services.php', 'packages.php'] as $f) {
        $p = $root . '/bootstrap/cache/' . $f;
        if (is_file($p) && @unlink($p)) {
            $bootstrapRemoved++;
        }
    }
    $steps[] = "[OK] Bootstrap cache dibersihkan ({$bootstrapRemoved} file).";

    $viewsRemoved = 0;
    $viewFiles = glob($root . '/storage/framework/views/*.php') ?: [];
    foreach ($viewFiles as $vf) {
        if (is_file($vf) && @unlink($vf)) {
            $viewsRemoved++;
        }
    }
    $steps[] = "[OK] View cache dibersihkan ({$viewsRemoved} file).";

    $permFixed = 0;
    foreach ([
        $root . '/storage/logs',
        $root . '/storage/framework/views',
        $root . '/storage/framework/cache',
        $root . '/storage/framework/sessions',
        $root . '/bootstrap/cache',
    ] as $dir) {
        if (!is_dir($dir) && @mkdir($dir, 0775, true)) {
            $permFixed++;
        }
        if (is_dir($dir) && !is_writable($dir) && @chmod($dir, 0775)) {
            $permFixed++;
        }
    }
    $steps[] = $permFixed > 0
        ? "[OK] Izin tulis diperbaiki pada {$permFixed} direktori."
        : '[OK] Direktori storage & bootstrap/cache writable.';

    clearstatcache(true);
    if (function_exists('opcache_reset') && @opcache_reset()) {
        $steps[] = '[OK] PHP OPcache direset.';
    }

    return $steps;
};

$scanPhpFiles = static function (string $root, string $phpBin): array {
    if (!function_exists('exec')) {
        return ['[INFO] Fungsi exec() dinonaktifkan oleh server; lewati scan CLI php -l.'];
    }
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (in_array('exec', $disabled, true)) {
        return ['[INFO] Fungsi exec() dinonaktifkan di php.ini; lewati scan CLI php -l.'];
    }

    $targets = [];
    foreach (['app/Http/Controllers', 'app/Models', 'app/Services', 'routes'] as $sub) {
        $dir = $root . '/' . $sub;
        if (!is_dir($dir)) {
            continue;
        }
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        foreach ($it as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $targets[] = $file->getPathname();
            }
        }
    }

    $errors = [];
    foreach ($targets as $filePath) {
        $out = [];
        $code = 0;
        @exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($filePath) . ' 2>&1', $out, $code);
        if ($code !== 0) {
            $rel = ltrim(str_replace($root, '', $filePath), '/\\');
            $errors[] = "[ERROR] {$rel}: " . implode(' ', $out);
        }
    }

    if (empty($errors)) {
        return ['[OK] Pemeriksaan sintaks ' . count($targets) . ' file PHP inti lulus tanpa ParseError.'];
    }
    return $errors;
};

$runWhitelistedArtisan = static function (string $root, string $phpBin, string $artisanArgs): string {
    if (!function_exists('shell_exec')) {
        return '[ERROR] shell_exec() tidak tersedia di server ini.';
    }
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    if (in_array('shell_exec', $disabled, true)) {
        return '[ERROR] shell_exec() dinonaktifkan di php.ini.';
    }
    $cmd = 'cd ' . escapeshellarg($root) . ' && ' . escapeshellarg($phpBin) . ' artisan ' . $artisanArgs . ' 2>&1';
    $out = @shell_exec($cmd);
    return $out !== null ? trim($out) : '(Perintah selesai tanpa output)';
};

$readLogTail = static function (string $logPath, int $maxLines = 120): string {
    if (!is_file($logPath) || !is_readable($logPath)) {
        return 'File storage/logs/laravel.log belum ada.';
    }
    $handle = @fopen($logPath, 'rb');
    if (!$handle) {
        return 'Gagal membuka laravel.log.';
    }
    $size = @filesize($logPath) ?: 0;
    $readBytes = min($size, 256 * 1024);
    if ($size > $readBytes) {
        @fseek($handle, -$readBytes, SEEK_END);
        @fgets($handle);
    }
    $chunk = @stream_get_contents($handle) ?: '';
    @fclose($handle);

    $lines = preg_split('/\r\n|\r|\n/', trim($chunk)) ?: [];
    if (count($lines) > $maxLines) {
        $lines = array_slice($lines, -$maxLines);
    }
    return implode("\n", $lines);
};

$results = [];
$commandOutput = '';
$action = (string) ($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phpBin = $detectPhpBinary();

    if ($action === 'rescue_all') {
        $results = array_merge($results, $clearSystemCaches($projectRoot));
        $results = array_merge($results, $scanPhpFiles($projectRoot, $phpBin));
    } elseif ($action === 'clear_cache') {
        $results = $clearSystemCaches($projectRoot);
    } elseif ($action === 'run_migrate') {
        $results = $clearSystemCaches($projectRoot);
        $commandOutput = $runWhitelistedArtisan($projectRoot, $phpBin, 'migrate --force');
    } elseif ($action === 'archive_logs') {
        $logFile = $projectRoot . '/storage/logs/laravel.log';
        if (is_file($logFile)) {
            $archive = $projectRoot . '/storage/logs/laravel-archived-' . date('Ymd-His') . '.log';
            @copy($logFile, $archive);
            @file_put_contents($logFile, '');
            $results[] = '[OK] Log utama diarsipkan dan dikosongkan.';
        } else {
            $results[] = '[INFO] File log utama belum ada.';
        }
    }
}

$logTail = $readLogTail($projectRoot . '/storage/logs/laravel.log', 120);
$logSize = is_file($projectRoot . '/storage/logs/laravel.log')
    ? round(((@filesize($projectRoot . '/storage/logs/laravel.log') ?: 0) / 1024), 1) . ' KB'
    : '0 KB';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Boot System Rescue - Pusat Kurma POS</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; background: #0f172a; color: #e2e8f0; margin: 0; padding: 24px 16px; }
        .wrap { max-width: 920px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 20px; }
        h1 { margin: 0 0 6px; font-size: 20px; color: #f8fafc; }
        p { margin: 0; font-size: 13px; color: #94a3b8; line-height: 1.5; }
        .actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 14px; }
        button, .link-btn { border: 0; border-radius: 10px; padding: 10px 16px; font-size: 13px; font-weight: 700; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
        .btn-primary { background: #059669; color: #ffffff; }
        .btn-primary:hover { background: #047857; }
        .btn-amber { background: #d97706; color: #ffffff; }
        .btn-slate { background: #334155; color: #f1f5f9; }
        .btn-rose { background: #be123c; color: #ffffff; }
        .item { background: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 10px 12px; font-size: 12px; margin-top: 8px; }
        .ok { border-color: #065f46; color: #6ee7b7; }
        .err { border-color: #9f1239; color: #fda4af; }
        pre { background: #020617; border: 1px solid #1e293b; border-radius: 12px; padding: 14px; font-size: 11.5px; color: #cbd5e1; overflow-x: auto; white-space: pre-wrap; max-height: 380px; margin: 10px 0 0; line-height: 1.45; }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <h1>Pre-Boot System Rescue — Pusat Kurma POS</h1>
            <p>Alat pemulihan darurat mandiri yang berjalan sebelum Laravel booting. Gunakan jika aplikasi utama mengalami Error 500 dan halaman Admin tidak dapat diakses.</p>
            <form method="post" class="actions">
                <input type="hidden" name="key" value="<?= htmlspecialchars($providedKey, ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" name="action" value="rescue_all" class="btn-primary">1-Click Pemulihan Darurat &amp; Audit PHP</button>
                <button type="submit" name="action" value="clear_cache" class="btn-amber">Bersihkan Cache View/Config/Route</button>
                <button type="submit" name="action" value="run_migrate" class="btn-slate">Jalankan Migrasi DB (--force)</button>
                <button type="submit" name="action" value="archive_logs" class="btn-rose">Arsipkan &amp; Kosongkan Log (<?= htmlspecialchars($logSize) ?>)</button>
                <a href="/" class="link-btn btn-slate">Coba Buka POS Kembali</a>
            </form>
        </div>

        <?php if (!empty($results) || $commandOutput !== ''): ?>
        <div class="card">
            <h1>Hasil Eksekusi Rescue</h1>
            <?php foreach ($results as $line): ?>
                <?php $isErr = str_starts_with($line, '[ERROR]'); ?>
                <div class="item <?= $isErr ? 'err' : 'ok' ?>"><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endforeach; ?>
            <?php if ($commandOutput !== ''): ?>
                <pre><?= htmlspecialchars($commandOutput, ENT_QUOTES, 'UTF-8') ?></pre>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card">
            <h1>120 Baris Terakhir storage/logs/laravel.log (<?= htmlspecialchars($logSize) ?>)</h1>
            <pre><?= htmlspecialchars($logTail, ENT_QUOTES, 'UTF-8') ?></pre>
        </div>
    </div>
</body>
</html>
