<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    protected static function booted(): void
    {
        $flush = static fn () => \App\Support\PosCache::flushProducts();
        static::saved($flush);
        static::deleted($flush);
    }
}
