<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PosCache
{
    public const PRODUCTS_VERSION_KEY = 'kasir_products_cache_version';

    /**
     * Get the current monotonically increasing product catalog version token.
     */
    public static function productsVersion(): string
    {
        return (string) Cache::rememberForever(self::PRODUCTS_VERSION_KEY, fn () => '1');
    }

    /**
     * Bump the catalog version token and clear related product/category caches.
     * Waits until any active DB transaction commits so listeners never warm stale data.
     */
    public static function flushProducts(): void
    {
        $callback = static function (): void {
            $nowToken = (int) round(microtime(true) * 10000);
            $currentToken = (int) Cache::get(self::PRODUCTS_VERSION_KEY, 0);
            $nextVersion = (string) ($nowToken > $currentToken ? $nowToken : ($currentToken + 1));

            Cache::forever(self::PRODUCTS_VERSION_KEY, $nextVersion);
            Cache::forget('categories_for_kasir');
            Cache::forget('categories_sorted_name');
            Cache::forget('active_products_purchase_form');
            Cache::forget('products_for_transfer_form');
            Cache::forget('owner_inventory_metrics');
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($callback);
        } else {
            $callback();
        }
    }
}
