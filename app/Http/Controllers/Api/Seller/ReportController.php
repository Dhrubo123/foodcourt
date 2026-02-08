<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\SellerOrder;
use App\Models\SellerSettlement;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function summary(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = $data['from'] ?? now()->subDays(30)->toDateString();
        $to = $data['to'] ?? now()->toDateString();

        $deliveredOrders = SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'delivered')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);

        $revenue = (float) (clone $deliveredOrders)->sum('total_after_discount');
        $grossSales = (float) (clone $deliveredOrders)->sum('subtotal');
        $discounts = (float) (clone $deliveredOrders)->sum('discount_amount');
        $deliveredCount = (int) (clone $deliveredOrders)->count();

        $cancelledValue = (float) SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'cancelled')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->sum('total_after_discount');

        $cogs = (float) OrderItem::query()
            ->join('seller_orders as so', 'so.id', '=', 'order_items.seller_order_id')
            ->join('products as p', 'p.id', '=', 'order_items.product_id')
            ->where('so.seller_id', $seller->id)
            ->where('so.seller_status', 'delivered')
            ->whereBetween(DB::raw('DATE(so.created_at)'), [$from, $to])
            ->sum(DB::raw('order_items.qty * p.cost_price'));

        $profit = $revenue - $cogs;
        $commissionPercent = (float) SystemSetting::getValue('commission_percent', 10);
        $commissionAmount = round(($revenue * $commissionPercent) / 100, 2);
        $netPayout = round($revenue - $commissionAmount, 2);
        $averageRating = round((float) Review::query()->where('seller_id', $seller->id)->avg('rating'), 2);
        $totalReviews = (int) Review::query()->where('seller_id', $seller->id)->count();

        $daily = SellerOrder::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw("SUM(CASE WHEN seller_status = 'delivered' THEN total_after_discount ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN seller_status = 'cancelled' THEN total_after_discount ELSE 0 END) as cancelled_value")
            ->where('seller_id', $seller->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        $weekly = SellerOrder::query()
            ->selectRaw("YEARWEEK(created_at, 1) as year_week")
            ->selectRaw('MIN(DATE(created_at)) as week_start')
            ->selectRaw("SUM(CASE WHEN seller_status = 'delivered' THEN total_after_discount ELSE 0 END) as revenue")
            ->where('seller_id', $seller->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy(DB::raw('YEARWEEK(created_at, 1)'))
            ->orderByDesc('year_week')
            ->limit(12)
            ->get();

        $monthly = SellerOrder::query()
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month")
            ->selectRaw("SUM(CASE WHEN seller_status = 'delivered' THEN total_after_discount ELSE 0 END) as revenue")
            ->where('seller_id', $seller->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%Y-%m')"))
            ->orderByDesc('month')
            ->limit(12)
            ->get();

        $todayRevenue = (float) SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'delivered')
            ->whereDate('created_at', today())
            ->sum('total_after_discount');

        $weekRevenue = (float) SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'delivered')
            ->whereBetween(DB::raw('DATE(created_at)'), [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
            ->sum('total_after_discount');

        $monthRevenue = (float) SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'delivered')
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->sum('total_after_discount');

        $topItems = OrderItem::query()
            ->join('seller_orders as so', 'so.id', '=', 'order_items.seller_order_id')
            ->where('so.seller_id', $seller->id)
            ->where('so.seller_status', 'delivered')
            ->whereBetween(DB::raw('DATE(so.created_at)'), [$from, $to])
            ->selectRaw('order_items.product_id')
            ->selectRaw('order_items.product_name_snapshot')
            ->selectRaw('SUM(order_items.qty) as total_qty')
            ->selectRaw('SUM(order_items.line_total) as total_amount')
            ->groupBy('order_items.product_id', 'order_items.product_name_snapshot')
            ->orderByDesc('total_qty')
            ->limit(10)
            ->get();

        $peakOrderTime = SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw('HOUR(created_at) as hour')
            ->selectRaw('COUNT(id) as total_orders')
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderByDesc('total_orders')
            ->limit(5)
            ->get();

        $cancelledByReason = SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'cancelled')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->selectRaw("COALESCE(cancel_reason, 'Not provided') as reason")
            ->selectRaw('COUNT(id) as total_orders')
            ->selectRaw('SUM(total_after_discount) as total_value')
            ->groupBy(DB::raw("COALESCE(cancel_reason, 'Not provided')"))
            ->orderByDesc('total_orders')
            ->get();

        $cancelledOrdersCount = (int) SellerOrder::query()
            ->where('seller_id', $seller->id)
            ->where('seller_status', 'cancelled')
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->count();

        $paymentHistory = SellerSettlement::query()
            ->where('seller_id', $seller->id)
            ->orderByDesc('id')
            ->limit(20)
            ->get([
                'id',
                'period_from',
                'period_to',
                'gross_amount',
                'commission_percent',
                'commission_amount',
                'net_amount',
                'status',
                'payment_method',
                'trx_id',
                'paid_at',
            ]);

        return response()->json([
            'summary' => [
                'from' => $from,
                'to' => $to,
                'revenue' => round($revenue, 2),
                'gross_sales' => round($grossSales, 2),
                'discounts' => round($discounts, 2),
                'cogs' => round($cogs, 2),
                'profit' => round($profit, 2),
                'loss' => round($cancelledValue, 2),
                'delivered_orders' => $deliveredCount,
                'commission_percent' => $commissionPercent,
                'commission_amount' => $commissionAmount,
                'net_payout' => $netPayout,
                'today_revenue' => round($todayRevenue, 2),
                'weekly_revenue' => round($weekRevenue, 2),
                'monthly_revenue' => round($monthRevenue, 2),
                'average_rating' => $averageRating,
                'total_reviews' => $totalReviews,
                'cancelled_orders' => $cancelledOrdersCount,
            ],
            'daily' => $daily,
            'weekly' => $weekly,
            'monthly' => $monthly,
            'top_items' => $topItems,
            'peak_order_time' => $peakOrderTime,
            'cancelled_by_reason' => $cancelledByReason,
            'payment_history' => $paymentHistory,
        ]);
    }
}
