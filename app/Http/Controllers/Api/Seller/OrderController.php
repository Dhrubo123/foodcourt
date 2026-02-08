<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\SellerOrder;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $query = SellerOrder::query()
            ->with([
                'order.customer:id,name,phone',
                'items',
                'offer:id,title,type,value',
            ])
            ->where('seller_id', $seller->id);

        $query->when($request->filled('status'), function ($builder) use ($request) {
            $builder->where('seller_status', $request->string('status'));
        });

        $query->when($request->filled('from'), function ($builder) use ($request) {
            $builder->whereDate('created_at', '>=', $request->string('from'));
        });

        $query->when($request->filled('to'), function ($builder) use ($request) {
            $builder->whereDate('created_at', '<=', $request->string('to'));
        });

        $query->when($request->filled('search'), function ($builder) use ($request) {
            $search = $request->string('search');
            if (is_numeric((string) $search)) {
                $builder->where('order_id', (int) $search);
            } else {
                $likeSearch = '%' . $search . '%';
                $builder->where(function ($searchBuilder) use ($likeSearch) {
                    $searchBuilder->where('pickup_token', 'like', $likeSearch)
                        ->orWhereHas('order.customer', function ($customerQuery) use ($likeSearch) {
                            $customerQuery->where('name', 'like', $likeSearch)
                                ->orWhere('phone', 'like', $likeSearch);
                        });
                });
            }
        });

        return $query->orderByDesc('id')->paginate(20);
    }

    public function updateStatus(Request $request, SellerOrder $sellerOrder)
    {
        $seller = $request->user()->seller;

        if (! $seller || $sellerOrder->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized order access.'], 403);
        }

        $data = $request->validate([
            'seller_status' => ['required', 'in:new,accepted,preparing,ready,delivered,cancelled'],
            'prep_time_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'cancel_reason' => ['nullable', 'string', 'max:255'],
        ]);

        if (! $this->isValidTransition($sellerOrder->seller_status, $data['seller_status'])) {
            return response()->json([
                'message' => 'Invalid status transition.',
            ], 422);
        }

        $sellerOrder->seller_status = $data['seller_status'];

        if (array_key_exists('prep_time_minutes', $data)) {
            $sellerOrder->prep_time_minutes = $data['prep_time_minutes'];
        }

        if (
            $data['seller_status'] === 'accepted'
            && empty($data['prep_time_minutes'])
            && $seller->default_prep_time_minutes
        ) {
            $sellerOrder->prep_time_minutes = $seller->default_prep_time_minutes;
        }

        if ($data['seller_status'] === 'accepted') {
            $sellerOrder->accepted_at = now();
        }

        if ($data['seller_status'] === 'ready') {
            $sellerOrder->ready_at = now();
        }

        if ($data['seller_status'] === 'delivered') {
            $sellerOrder->delivered_at = now();
        }

        if ($data['seller_status'] === 'cancelled') {
            $sellerOrder->cancelled_at = now();
            $sellerOrder->cancel_reason = $data['cancel_reason'] ?? $sellerOrder->cancel_reason;
        } else {
            $sellerOrder->cancel_reason = null;
        }

        $sellerOrder->save();

        $order = $sellerOrder->order()->first();
        if ($order) {
            $this->syncParentStatusFromSellerOrders($order);
        }

        return response()->json([
            'seller_order' => $sellerOrder->fresh([
                'order.customer:id,name,phone',
                'items',
                'offer:id,title,type,value',
            ]),
            'order' => $order ? $order->fresh() : null,
        ]);
    }

    private function isValidTransition(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $allowed = [
            'new' => ['accepted', 'cancelled'],
            'accepted' => ['preparing', 'cancelled'],
            'preparing' => ['ready', 'cancelled'],
            'ready' => ['delivered', 'cancelled'],
            'delivered' => [],
            'cancelled' => [],
        ];

        return in_array($to, $allowed[$from] ?? [], true);
    }

    private function syncParentStatusFromSellerOrders(Order $order): void
    {
        $statuses = $order->sellerOrders()->pluck('seller_status');

        if ($statuses->isEmpty()) {
            return;
        }

        $nextStatus = 'placed';

        if ($statuses->every(fn ($status) => $status === 'cancelled')) {
            $nextStatus = 'cancelled';
        } elseif (
            $statuses->every(fn ($status) => in_array($status, ['delivered', 'cancelled'], true))
            && $statuses->contains('delivered')
        ) {
            $nextStatus = 'completed';
        } elseif ($statuses->contains('ready')) {
            $nextStatus = 'ready';
        } elseif ($statuses->contains(fn ($status) => in_array($status, ['accepted', 'preparing'], true))) {
            $nextStatus = 'preparing';
        } else {
            $nextStatus = 'placed';
        }

        if ($order->order_status !== $nextStatus) {
            $order->order_status = $nextStatus;
            $order->save();
        }
    }
}

