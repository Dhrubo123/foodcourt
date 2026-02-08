<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'grand_total',
        'payment_status',
        'order_status',
    ];

    protected $casts = [
        'grand_total' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function sellerOrders()
    {
        return $this->hasMany(SellerOrder::class);
    }

    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }
}
