<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Seller extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_user_id',
        'type',
        'name',
        'phone',
        'address',
        'area_id',
        'lat',
        'lng',
        'open_time',
        'close_time',
        'is_approved',
        'is_blocked',
        'is_open',
        'default_prep_time_minutes',
        'logo_path',
        'cover_path',
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'is_blocked' => 'boolean',
        'is_open' => 'boolean',
        'default_prep_time_minutes' => 'integer',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    public function categories()
    {
        return $this->hasMany(ProductCategory::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(Subscription::class)
            ->where('status', 'active')
            ->where('end_at', '>=', now())
            ->latest('end_at');
    }

    public function latestSubscription()
    {
        return $this->hasOne(Subscription::class)->latestOfMany('end_at');
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }

    public function sellerOrders()
    {
        return $this->hasMany(SellerOrder::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function settlements()
    {
        return $this->hasMany(SellerSettlement::class);
    }

    public function scopeVisible($query)
    {
        return $query
            ->where('is_approved', true)
            ->where('is_blocked', false)
            ->whereHas('activeSubscription');
    }

    public function scopeNearby($query, float $lat, float $lng, float $radiusKm = 5.0)
    {
        $haversine = '(6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lng) - radians(?)) + sin(radians(?)) * sin(radians(lat))))';

        return $query
            ->select('*')
            ->selectRaw($haversine . ' as distance', [$lat, $lng, $lat])
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->having('distance', '<=', $radiusKm)
            ->orderBy('distance');
    }
}
