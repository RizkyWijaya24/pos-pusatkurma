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
        'transaction_type',
        'customer_name',
        'customer_phone',
        'items_summary',
        'total_price',
        'discount',
        'shipping_cost',
        'total_cost',
        'payment_method',
        'branch',
        'payment_status',
    ];

    /**
     * Get the cashier that owns the transaction.
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * Get the installment payments for the transaction.
     */
    public function installments()
    {
        return $this->hasMany(InstallmentPayment::class);
    }
}
