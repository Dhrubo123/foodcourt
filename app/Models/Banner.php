<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'subtitle',
        'image_path',
        'cta_label',
        'cta_link',
        'is_active',
        'sort_order',
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function scopeLive($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($builder) {
                $builder->whereNull('start_at')->orWhere('start_at', '<=', now());
            })
            ->where(function ($builder) {
                $builder->whereNull('end_at')->orWhere('end_at', '>=', now());
            });
    }
}
