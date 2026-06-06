<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';

use Illuminate\Contracts\Console\Kernel;
$app->make(Kernel::class)->bootstrap();

$request = Illuminate\Http\Request::create('/api/v1/live-stock', 'GET');
$response = Route::dispatch($request);

echo "Status Code: " . $response->getStatusCode() . "\n";
echo "Raw Content: " . $response->getContent() . "\n";

if (isset($response->exception)) {
    echo "Exception: " . $response->exception->getMessage() . "\n";
    echo $response->exception->getTraceAsString() . "\n";
}
