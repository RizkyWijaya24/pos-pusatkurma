<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Affiliate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'phone',
        'agency_name',
        'commission_rate_percentage',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'status',
        'user_id',
        'notes',
    ];

    protected $casts = [
        'commission_rate_percentage' => 'float',
    ];

    /**
     * Relasi ke transaksi yang dibawa oleh agen ini
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Relasi ke buku besar mutasi komisi (Komisi Ledger)
     */
    public function ledgers()
    {
        return $this->hasMany(KomisiLedger::class)->orderBy('created_at', 'desc');
    }

    /**
     * Relasi opsional ke user akun login
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope agen aktif
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Hitung nilai komisi berdasarkan omset dan persentase aktif agen
     */
    public function calculateCommission(float $omset): float
    {
        return round(($omset * $this->commission_rate_percentage) / 100);
    }

    /**
     * Accessor: Total Akumulasi Omset
     */
    public function getTotalOmsetAttribute(): float
    {
        return (float) $this->ledgers()
            ->where('type', 'credit')
            ->sum('transaction_amount');
    }

    /**
     * Accessor: Total Komisi yang Pernah Didapatkan (Gross Earnings)
     */
    public function getTotalKomisiEarnedAttribute(): float
    {
        return (float) $this->ledgers()
            ->where('type', 'credit')
            ->sum('amount');
    }

    /**
     * Accessor: Total Komisi yang Sudah Dicairkan (Paid Payouts)
     */
    public function getTotalKomisiWithdrawnAttribute(): float
    {
        return (float) $this->ledgers()
            ->where('type', 'debit')
            ->where('status', 'paid')
            ->sum('amount');
    }

    /**
     * Accessor: Komisi Tersedia (Saldo bersih yang siap ditarik)
     * Total Credit - Total Debit (yang pending, approved, atau paid)
     */
    public function getKomisiTersediaAttribute(): float
    {
        $credit = (float) $this->ledgers()
            ->where('type', 'credit')
            ->whereIn('status', ['approved', 'paid'])
            ->sum('amount');

        $debit = (float) $this->ledgers()
            ->where('type', 'debit')
            ->whereIn('status', ['pending', 'approved', 'paid'])
            ->sum('amount');

        return max(0, $credit - $debit);
    }

    /**
     * Accessor: Total Pax / Jamaah Rombongan yang Terbawa
     */
    public function getTotalJamaahAttribute(): int
    {
        return (int) $this->transactions()->sum('pax_count');
    }
}

