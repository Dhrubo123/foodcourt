<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query()
            ->with([
                'customer:id,name,email,phone',
                'sellerOrders' => function ($builder) {
                    $builder->with(['seller:id,name,type', 'items']);
                },
            ]);

        $query->when($request->filled('order_status'), function ($builder) use ($request) {
            $builder->where('order_status', $request->string('order_status'));
        });

        $query->when($request->filled('payment_status'), function ($builder) use ($request) {
            $builder->where('payment_status', $request->string('payment_status'));
        });

        $query->when($request->filled('seller_id'), function ($builder) use ($request) {
            $builder->whereHas('sellerOrders', function ($sellerQuery) use ($request) {
                $sellerQuery->where('seller_id', $request->integer('seller_id'));
            });
        });

        $perPage = min(max((int) $request->get('per_page', 15), 1), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }
}
