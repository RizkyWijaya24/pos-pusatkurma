<?php
$dir = new RecursiveDirectoryIterator(__DIR__ . '/resources/views');
$iterator = new RecursiveIteratorIterator($dir);
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        if (strpos($content, 'Rincian Pendapatan Harian Pekan Ini') !== false) {
            echo "Found in: " . $file->getPathname() . "\n";
        }
    }
}
