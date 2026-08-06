<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'badge_text',
        'button_text',
        'button_url',
        'bg_from',
        'bg_to',
        'sort_order',
        'start_date',
        'end_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active'  => 'boolean',
            'start_date' => 'date',
            'end_date'   => 'date',
        ];
    }

    /**
     * Banner yang aktif dan dalam rentang tanggal valid.
     */
    public function scopeActive($query)
    {
        $today = now()->toDateString();
        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->orderBy('sort_order', 'asc');
    }
}
