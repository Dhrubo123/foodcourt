<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerOrder;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $activeSellers = Seller::query()
            ->where('is_approved', true)
            ->where('is_blocked', false)
            ->count();

        $pendingApprovals = Seller::query()
            ->where('is_approved', false)
            ->where('is_blocked', false)
            ->count();

        $todayOrders = SellerOrder::query()
            ->whereDate('created_at', today())
            ->count();

        $dailyRevenue = (float) SellerOrder::query()
            ->whereDate('created_at', today())
            ->where('seller_status', 'delivered')
            ->sum('total_after_discount');

        $monthlyRevenue = (float) SellerOrder::query()
            ->where('seller_status', 'delivered')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_after_discount');

        $pendingOrders = SellerOrder::query()
            ->whereIn('seller_status', ['new', 'accepted', 'preparing'])
            ->count();

        $topItems = DB::table('order_items as oi')
            ->join('seller_orders as so', 'so.id', '=', 'oi.seller_order_id')
            ->select([
                'oi.product_id',
                'oi.product_name_snapshot',
            ])
            ->selectRaw('SUM(oi.qty) as total_qty')
            ->selectRaw('SUM(oi.line_total) as total_revenue')
            ->whereDate('so.created_at', '>=', now()->subDays(30)->toDateString())
            ->where('so.seller_status', 'delivered')
            ->groupBy('oi.product_id', 'oi.product_name_snapshot')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        $recentSellers = Seller::query()
            ->with('area:id,name')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'name', 'type', 'area_id', 'address', 'lat', 'lng', 'is_approved', 'is_blocked'])
            ->map(function ($seller) {
                $areaLabel = $seller->area?->name;
                if (! $areaLabel && $seller->address) {
                    $areaLabel = $seller->address;
                }
                if (! $areaLabel && $seller->lat !== null && $seller->lng !== null) {
                    $areaLabel = 'Lat ' . $seller->lat . ', Lng ' . $seller->lng;
                }
                if (! $areaLabel) {
                    $areaLabel = 'N/A';
                }

                $seller->area_label = $areaLabel;

                return $seller;
            });

        return response()->json([
            'active_sellers' => $activeSellers,
            'pending_approvals' => $pendingApprovals,
            'today_orders' => $todayOrders,
            'daily_revenue' => round($dailyRevenue, 2),
            'monthly_revenue' => round($monthlyRevenue, 2),
            'pending_orders' => $pendingOrders,
            'top_items' => $topItems,
            'recent_sellers' => $recentSellers,
        ]);
    }
}
