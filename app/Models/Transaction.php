<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'transaction_code',
        'items_summary',
        'total_price',
        'discount',
        'total_cost',
        'payment_method',
        'branch',
    ];

    /**
     * Get the cashier that owns the transaction.
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
