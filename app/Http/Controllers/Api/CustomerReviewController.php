<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use Illuminate\Http\Request;

class CustomerReviewController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'seller_id' => ['required', 'integer', 'exists:sellers,id'],
            'order_id' => ['nullable', 'integer', 'exists:orders,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! empty($data['order_id'])) {
            $orderAllowed = Order::query()
                ->where('id', $data['order_id'])
                ->where('customer_id', $user->id)
                ->whereHas('sellerOrders', function ($query) use ($data) {
                    $query->where('seller_id', $data['seller_id']);
                })
                ->exists();

            if (! $orderAllowed) {
                return response()->json(['message' => 'Invalid order for this review.'], 422);
            }
        }

        $review = Review::create([
            'customer_id' => $user->id,
            'seller_id' => $data['seller_id'],
            'order_id' => $data['order_id'] ?? null,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
            'is_visible' => true,
        ]);

        return response()->json(['review' => $review->load('seller', 'customer')], 201);
    }
}
