<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WholesalePriceMemory extends Model
{
    protected $fillable = [
        'customer_key',
        'product_key',
        'price_unit',
        'last_selling_price',
        'last_cost_price',
        'last_transaction_at',
    ];

    protected function casts(): array
    {
        return [
            'last_selling_price'  => 'integer',
            'last_cost_price'     => 'integer',
            'last_transaction_at' => 'datetime',
        ];
    }

    public static function normalizeKey(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
