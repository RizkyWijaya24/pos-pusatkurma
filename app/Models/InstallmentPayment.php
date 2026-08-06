<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstallmentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'amount',
        'payment_method',
        'note',
    ];

    /**
     * Get the transaction that owns the installment payment.
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
