<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'product_id',
        'product_name',
        'quantity',
        'unit',
        'unit_cost',
        'subtotal',
        'update_product_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity'            => 'float',
            'unit_cost'           => 'float',
            'subtotal'            => 'float',
            'update_product_cost' => 'boolean',
        ];
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}

