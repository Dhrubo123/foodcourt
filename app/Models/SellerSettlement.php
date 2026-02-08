<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerSettlement extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'period_from',
        'period_to',
        'orders_count',
        'gross_amount',
        'commission_percent',
        'commission_amount',
        'net_amount',
        'status',
        'payment_method',
        'trx_id',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'period_from' => 'date',
        'period_to' => 'date',
        'orders_count' => 'integer',
        'gross_amount' => 'decimal:2',
        'commission_percent' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }
}

