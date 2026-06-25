<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RepackLogItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'repack_log_id',
        'target_product_id',
        'target_quantity',
        'additional_packaging_cost',
        'calculated_cost_price',
    ];

    protected function casts(): array
    {
        return [
            'target_quantity'            => 'float',
            'additional_packaging_cost' => 'integer',
            'calculated_cost_price'      => 'integer',
        ];
    }

    // ═══════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════

    public function repackLog()
    {
        return $this->belongsTo(RepackLog::class, 'repack_log_id');
    }

    public function targetProduct()
    {
        return $this->belongsTo(Product::class, 'target_product_id');
    }
}
