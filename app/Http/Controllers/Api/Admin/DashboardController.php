<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\SellerOrder;

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

        $monthlyRevenue = (float) SellerOrder::query()
            ->where('seller_status', 'delivered')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_after_discount');

        $recentSellers = Seller::query()
            ->with('area:id,name')
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'name', 'type', 'area_id', 'is_approved', 'is_blocked']);

        return response()->json([
            'active_sellers' => $activeSellers,
            'pending_approvals' => $pendingApprovals,
            'today_orders' => $todayOrders,
            'monthly_revenue' => round($monthlyRevenue, 2),
            'recent_sellers' => $recentSellers,
        ]);
    }
}
