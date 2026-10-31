<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind dynamic public path for cPanel custom layout (public_html sibling structure)
        if (isset($_SERVER['SCRIPT_FILENAME']) && str_ends_with($_SERVER['SCRIPT_FILENAME'], 'index.php')) {
            $publicPath = dirname($_SERVER['SCRIPT_FILENAME']);
            $this->app->usePublicPath($publicPath);
        } else {
            // Fallback for CLI / Artisan if public_html exists as sibling folder
            $publicHtml = realpath(base_path('../public_html'));
            if ($publicHtml && is_dir($publicHtml)) {
                $this->app->usePublicPath($publicHtml);
            }
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production') || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }

        // Ensure dompdf fonts directory exists
        $dompdfFontDir = storage_path('fonts');
        if (!is_dir($dompdfFontDir)) {
            @mkdir($dompdfFontDir, 0775, true);
        }

        // Share shop settings globally to all views if table exists
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                $settings = \App\Models\Setting::pluck('value', 'key')->all();
                \Illuminate\Support\Facades\View::share('shop_settings', $settings);
            }
        } catch (\Throwable $e) {
            // Prevent artisan commands from crashing when DB is uninitialized or unreachable
        }
    }
}
