<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Order;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('orders:clear-unpaid', function () {
    $count = Order::where('payment_status', 'pending')
        ->where('created_at', '<', \Carbon\Carbon::now()->subHours(24))
        ->delete();

    $this->info("Successfully deleted {$count} unpaid orders older than 24 hours.");
})->purpose('Clear unpaid orders older than 24 hours');

Schedule::command('orders:clear-unpaid')->hourly();
