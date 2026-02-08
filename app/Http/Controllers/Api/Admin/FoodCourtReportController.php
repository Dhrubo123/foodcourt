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
}
