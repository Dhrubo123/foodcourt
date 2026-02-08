<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'seller_id',
        'pickup_token',
        'token_date',
        'subtotal',
        'discount_amount',
        'total_after_discount',
        'offer_id',
        'seller_status',
        'cancel_reason',
        'prep_time_minutes',
        'accepted_at',
        'ready_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected $casts = [
        'token_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_after_discount' => 'decimal:2',
        'accepted_at' => 'datetime',
        'ready_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}
