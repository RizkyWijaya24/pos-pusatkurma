<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'category',
        'cost_price',
        'selling_price',
        'price_unit',
        'image_path',
        'stock',
        'price_tiers',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stock' => 'float',
            'cost_price' => 'integer',
            'selling_price' => 'integer',
            'price_tiers' => 'array',
        ];
    }

    /**
     * Get the active price based on the purchased quantity.
     * If tiered pricing is empty or invalid, defaults to selling_price.
     *
     * @param float $qty
     * @return int
     */
    public function getPriceForQuantity($qty): int
    {
        if (!empty($this->price_tiers) && is_array($this->price_tiers)) {
            foreach ($this->price_tiers as $tier) {
                $min = isset($tier['min_qty']) && $tier['min_qty'] !== '' ? floatval($tier['min_qty']) : 0;
                $max = isset($tier['max_qty']) && $tier['max_qty'] !== '' ? floatval($tier['max_qty']) : INF;
                if ($qty >= $min && $qty <= $max) {
                    return intval($tier['price']);
                }
            }
        }
        return intval($this->selling_price);
    }
}

