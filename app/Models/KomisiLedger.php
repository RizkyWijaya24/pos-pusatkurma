<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KomisiLedger extends Model
{
    use HasFactory;

    protected $table = 'komisi_ledger';

    protected $fillable = [
        'affiliate_id',
        'transaction_id',
        'type',
        'transaction_amount',
        'commission_rate_percentage',
        'amount',
        'status',
        'reference_no',
        'description',
        'notes',
    ];

    protected $casts = [
        'transaction_amount'         => 'float',
        'commission_rate_percentage' => 'float',
        'amount'                     => 'float',
    ];

    /**
     * Relasi ke data agen / tour leader
     */
    public function affiliate()
    {
        return $this->belongsTo(Affiliate::class);
    }

    /**
     * Relasi ke transaksi POS sumber komisi
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope untuk mutasi kredit (komisi masuk)
     */
    public function scopeCredits($query)
    {
        return $query->where('type', 'credit');
    }

    /**
     * Scope untuk mutasi debit (penarikan/pencairan)
     */
    public function scopeDebits($query)
    {
        return $query->where('type', 'debit');
    }
}

