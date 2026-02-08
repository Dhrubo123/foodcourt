<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $query = Review::query()->with([
            'customer:id,name,email,phone',
            'seller:id,name,type',
        ]);

        $query->when($request->filled('seller_id'), function ($builder) use ($request) {
            $builder->where('seller_id', $request->integer('seller_id'));
        });

        $query->when($request->filled('rating'), function ($builder) use ($request) {
            $builder->where('rating', $request->integer('rating'));
        });

        $query->when($request->filled('is_visible'), function ($builder) use ($request) {
            $builder->where('is_visible', $request->boolean('is_visible'));
        });

        $query->when($request->filled('search'), function ($builder) use ($request) {
            $search = '%' . $request->string('search') . '%';
            $builder->where(function ($sub) use ($search) {
                $sub->where('comment', 'like', $search)
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', $search);
                    })
                    ->orWhereHas('seller', function ($sellerQuery) use ($search) {
                        $sellerQuery->where('name', 'like', $search);
                    });
            });
        });

        return $query->orderByDesc('id')->paginate(20);
    }

    public function updateVisibility(Request $request, Review $review)
    {
        $data = $request->validate([
            'is_visible' => ['required', 'boolean'],
        ]);

        $review->is_visible = $data['is_visible'];
        $review->save();

        return response()->json(['review' => $review->fresh(['customer', 'seller'])]);
    }
}
