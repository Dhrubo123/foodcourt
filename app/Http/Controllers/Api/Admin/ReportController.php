<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function meta()
    {
        $sellers = Seller::query()
            ->select(['id', 'name', 'type'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'sellers' => $sellers,
        ]);
    }

    public function orders(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'seller_id' => ['nullable', 'integer', 'exists:sellers,id'],
            'seller_type' => ['nullable', 'in:cart,food_court'],
            'status' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $this->filteredSellerOrdersQuery($filters)
            ->select([
                'o.id as order_id',
                'so.id as seller_order_id',
                'o.created_at as order_date',
                's.id as seller_id',
                's.name as seller_name',
                's.type as seller_type',
                'o.order_status as parent_status',
                'so.seller_status',
                'o.payment_status',
                'so.subtotal',
                'so.discount_amount',
                'so.total_after_discount',
            ]);

        $query->addSelect(DB::raw('COALESCE(pay.method, "-") as payment_method'));
        $query->addSelect(DB::raw('COALESCE(pay.paid_amount, 0) as paid_amount'));

        $perPage = $filters['per_page'] ?? 20;

        return $query->orderByDesc('so.id')->paginate($perPage);
    }

    public function salesSummary(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'seller_id' => ['nullable', 'integer', 'exists:sellers,id'],
            'seller_type' => ['nullable', 'in:cart,food_court'],
            'status' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
        ]);

        $base = $this->filteredSellerOrdersQuery($filters);

        $summary = (clone $base)
            ->selectRaw('COUNT(DISTINCT o.id) as parent_orders')
            ->selectRaw('COUNT(so.id) as seller_orders')
            ->selectRaw('COALESCE(SUM(so.subtotal), 0) as gross_sales')
            ->selectRaw('COALESCE(SUM(so.discount_amount), 0) as total_discounts')
            ->selectRaw('COALESCE(SUM(so.total_after_discount), 0) as net_sales')
            ->selectRaw("COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN so.total_after_discount ELSE 0 END), 0) as paid_sales")
            ->first();

        $daily = (clone $base)
            ->selectRaw('DATE(so.created_at) as date')
            ->selectRaw('COUNT(DISTINCT o.id) as parent_orders')
            ->selectRaw('COUNT(so.id) as seller_orders')
            ->selectRaw('COALESCE(SUM(so.total_after_discount), 0) as total_sales')
            ->selectRaw("COALESCE(SUM(CASE WHEN o.payment_status = 'paid' THEN so.total_after_discount ELSE 0 END), 0) as paid_sales")
            ->groupBy(DB::raw('DATE(so.created_at)'))
            ->orderBy('date')
            ->get();

        $paymentStatusBreakdown = (clone $base)
            ->selectRaw('o.payment_status')
            ->selectRaw('COUNT(so.id) as seller_orders')
            ->selectRaw('COALESCE(SUM(so.total_after_discount), 0) as amount')
            ->groupBy('o.payment_status')
            ->orderByDesc('amount')
            ->get();

        $orderIds = $this->filteredOrderIdsQuery($filters);

        $paymentMethodBreakdown = DB::table('payments')
            ->selectRaw('method')
            ->selectRaw('COUNT(id) as payments_count')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) as paid_amount")
            ->where('payable_type', Order::class)
            ->whereIn('payable_id', $orderIds)
            ->groupBy('method')
            ->orderByDesc('paid_amount')
            ->get();

        return response()->json([
            'summary' => $summary,
            'daily' => $daily,
            'payment_status_breakdown' => $paymentStatusBreakdown,
            'payment_method_breakdown' => $paymentMethodBreakdown,
        ]);
    }

    public function sellerSettlements(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'seller_id' => ['nullable', 'integer', 'exists:sellers,id'],
            'seller_type' => ['nullable', 'in:cart,food_court'],
            'status' => ['nullable', 'string', 'max:50'],
            'payment_status' => ['nullable', 'string', 'max:50'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $commissionPercent = (float) ($filters['commission_percent'] ?? 10);

        $base = $this->filteredSellerOrdersQuery($filters);

        if (empty($filters['status'])) {
            $base->where('so.seller_status', 'delivered');
        }

        $rows = (clone $base)
            ->select([
                's.id as seller_id',
                's.name as seller_name',
                's.type as seller_type',
            ])
            ->selectRaw('COUNT(so.id) as orders_count')
            ->selectRaw('COALESCE(SUM(so.total_after_discount), 0) as gross_amount')
            ->groupBy('s.id', 's.name', 's.type')
            ->orderByDesc('gross_amount')
            ->get()
            ->map(function ($row) use ($commissionPercent) {
                $gross = (float) $row->gross_amount;
                $commission = round(($gross * $commissionPercent) / 100, 2);
                $net = round($gross - $commission, 2);

                return [
                    'seller_id' => $row->seller_id,
                    'seller_name' => $row->seller_name,
                    'seller_type' => $row->seller_type,
                    'orders_count' => (int) $row->orders_count,
                    'gross_amount' => round($gross, 2),
                    'commission_percent' => $commissionPercent,
                    'commission_amount' => $commission,
                    'net_amount' => $net,
                ];
            });

        $totals = [
            'gross_amount' => round((float) $rows->sum('gross_amount'), 2),
            'commission_amount' => round((float) $rows->sum('commission_amount'), 2),
            'net_amount' => round((float) $rows->sum('net_amount'), 2),
            'orders_count' => (int) $rows->sum('orders_count'),
        ];

        return response()->json([
            'commission_percent' => $commissionPercent,
            'totals' => $totals,
            'rows' => $rows,
        ]);
    }

    private function filteredSellerOrdersQuery(array $filters)
    {
        $query = DB::table('seller_orders as so')
            ->join('orders as o', 'o.id', '=', 'so.order_id')
            ->join('sellers as s', 's.id', '=', 'so.seller_id')
            ->leftJoinSub($this->paymentRollupSubquery(), 'pay', function ($join) {
                $join->on('pay.order_id', '=', 'o.id');
            });

        if (! empty($filters['from'])) {
            $query->whereDate('so.created_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('so.created_at', '<=', $filters['to']);
        }

        if (! empty($filters['seller_id'])) {
            $query->where('so.seller_id', $filters['seller_id']);
        }

        if (! empty($filters['seller_type'])) {
            $query->where('s.type', $filters['seller_type']);
        }

        if (! empty($filters['status'])) {
            $query->where('so.seller_status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('o.payment_status', $filters['payment_status']);
        }

        if (! empty($filters['payment_method'])) {
            $query->whereExists(function ($sub) use ($filters) {
                $sub->select(DB::raw(1))
                    ->from('payments')
                    ->whereColumn('payments.payable_id', 'o.id')
                    ->where('payments.payable_type', Order::class)
                    ->where('payments.method', $filters['payment_method']);
            });
        }

        return $query;
    }

    private function filteredOrderIdsQuery(array $filters)
    {
        return $this->filteredSellerOrdersQuery($filters)
            ->select('o.id')
            ->distinct();
    }

    private function paymentRollupSubquery()
    {
        return DB::table('payments')
            ->selectRaw('payable_id as order_id')
            ->selectRaw('MIN(method) as method')
            ->selectRaw("SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount")
            ->where('payable_type', Order::class)
            ->groupBy('payable_id');
    }
}
