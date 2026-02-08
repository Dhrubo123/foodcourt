<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoodCourtReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = DB::table('seller_orders as so')
            ->join('sellers as s', 's.id', '=', 'so.seller_id')
            ->select(
                's.id',
                's.name',
                DB::raw('count(so.id) as total_orders'),
                DB::raw('sum(so.total_after_discount) as total_revenue')
            )
            ->where('s.type', 'food_court')
            ->groupBy('s.id', 's.name')
            ->orderByDesc('total_orders');

        if (! empty($data['from'])) {
            $query->whereDate('so.created_at', '>=', $data['from']);
        }

        if (! empty($data['to'])) {
            $query->whereDate('so.created_at', '<=', $data['to']);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function queue(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'seller_id' => ['nullable', 'integer', 'exists:sellers,id'],
            'status' => ['nullable', 'in:new,accepted,preparing,ready,delivered,cancelled'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = DB::table('seller_orders as so')
            ->join('sellers as s', 's.id', '=', 'so.seller_id')
            ->join('orders as o', 'o.id', '=', 'so.order_id')
            ->where('s.type', 'food_court')
            ->select([
                'so.id',
                'so.order_id',
                'o.payment_status',
                'o.order_status',
                'so.seller_id',
                's.name as seller_name',
                'so.pickup_token',
                'so.seller_status',
                'so.cancel_reason',
                'so.subtotal',
                'so.discount_amount',
                'so.total_after_discount',
                'so.prep_time_minutes',
                'so.accepted_at',
                'so.ready_at',
                'so.delivered_at',
                'so.created_at',
            ]);

        if (! empty($data['from'])) {
            $query->whereDate('so.created_at', '>=', $data['from']);
        }

        if (! empty($data['to'])) {
            $query->whereDate('so.created_at', '<=', $data['to']);
        }

        if (! empty($data['seller_id'])) {
            $query->where('so.seller_id', $data['seller_id']);
        }

        if (! empty($data['status'])) {
            $query->where('so.seller_status', $data['status']);
        }

        $perPage = $data['per_page'] ?? 20;

        return $query->orderByDesc('so.id')->paginate($perPage);
    }
}
