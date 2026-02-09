<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use App\Models\SellerOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $todayOrders = SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->whereDate('created_at', today())
            ->count();

        $todayEarnings = (float) SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'delivered')
            ->whereDate('created_at', today())
            ->sum('total_after_discount');

        $pendingOrders = SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'new')
            ->count();

        $activeOrders = SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->whereIn('seller_status', ['accepted', 'preparing', 'ready'])
            ->count();

        $averageRating = round((float) Review::query()
            ->where('seller_id', $seller->id)
            ->avg('rating'), 2);

        $lowStockCount = 0;
        $lowStockItems = collect();
        if (Schema::hasColumn('products', 'stock_quantity')) {
            $lowStockCount = Product::query()
                ->where('seller_id', $seller->id)
                ->where('stock_quantity', '<', 10)
                ->count();

            $lowStockItems = Product::query()
                ->where('seller_id', $seller->id)
                ->where('stock_quantity', '<', 10)
                ->orderBy('stock_quantity')
                ->orderBy('name')
                ->limit(8)
                ->get([
                    'id',
                    'name',
                    'stock_quantity',
                    'is_available',
                ]);
        }

        $newOrders = SellerOrder::query()
            ->with([
                'order.customer:id,name,phone',
            ])
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'new')
            ->orderByDesc('id')
            ->limit(8)
            ->get([
                'id',
                'order_id',
                'pickup_token',
                'subtotal',
                'discount_amount',
                'total_after_discount',
                'seller_status',
                'created_at',
            ]);

        return response()->json([
            'seller' => [
                'id' => $seller->id,
                'name' => $seller->name,
                'is_open' => (bool) $seller->is_open,
                'default_prep_time_minutes' => $seller->default_prep_time_minutes,
            ],
            'today_orders' => $todayOrders,
            'today_earnings' => round($todayEarnings, 2),
            'pending_orders' => $pendingOrders,
            'active_orders' => $activeOrders,
            'average_rating' => $averageRating,
            'low_stock_count' => $lowStockCount,
            'low_stock_items' => $lowStockItems,
            'new_orders' => $newOrders,
        ]);
    }
}
