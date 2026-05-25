<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'cashier_id',
        'branch',
        'category',
        'amount',
        'description'
    ];

    /**
     * Get the cashier that recorded the expense.
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}
