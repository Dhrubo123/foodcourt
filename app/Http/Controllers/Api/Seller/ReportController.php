<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\SellerOrder;
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

        $daily = SellerOrder::query()
            ->selectRaw('DATE(created_at) as date')
            ->selectRaw("SUM(CASE WHEN seller_status = 'delivered' THEN total_after_discount ELSE 0 END) as revenue")
            ->selectRaw("SUM(CASE WHEN seller_status = 'cancelled' THEN total_after_discount ELSE 0 END) as cancelled_value")
            ->where('seller_id', $seller->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to])
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

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
            ],
            'daily' => $daily,
        ]);
    }
}
