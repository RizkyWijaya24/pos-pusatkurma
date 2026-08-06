<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductReview extends Model
{
    protected $fillable = [
        'product_id',
        'order_code',
        'reviewer_name',
        'rating',
        'comment',
        'is_approved',
    ];

    protected function casts(): array
    {
        return [
            'rating'      => 'integer',
            'is_approved' => 'boolean',
        ];
    }

    /** Produk yang diulas */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** Hanya ulasan yang sudah disetujui admin */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
