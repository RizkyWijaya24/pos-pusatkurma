<?php
// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Impersonate Owner
$owner = \App\Models\User::where('role', 'owner')->first();
if (!$owner) {
    echo "No owner user found in database!\n";
    exit(1);
}
auth()->login($owner);

// Render dashboard with different filters
foreach (['harian', 'mingguan', 'bulanan'] as $filter) {
    $request = \Illuminate\Http\Request::create('/owner/dashboard', 'GET', ['filter_type' => $filter]);
    
    // Dispatch route
    $response = $app->handle($request);
    $html = $response->getContent();
    
    echo "Filter: $filter, HTML Length: " . strlen($html) . "\n";
    file_put_contents(__DIR__ . "/dashboard_$filter.html", $html);
}
echo "Done rendering dashboards.\n";
