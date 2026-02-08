<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $query = Review::query()
            ->with([
                'customer:id,name,phone',
                'order:id,order_status,payment_status',
            ])
            ->where('seller_id', $seller->id);

        $query->when($request->filled('rating'), function ($builder) use ($request) {
            $builder->where('rating', $request->integer('rating'));
        });

        $query->when($request->filled('search'), function ($builder) use ($request) {
            $search = '%' . $request->string('search') . '%';
            $builder->where(function ($searchBuilder) use ($search) {
                $searchBuilder->where('comment', 'like', $search)
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', $search)
                            ->orWhere('phone', 'like', $search);
                    });
            });
        });

        $breakdown = Review::query()
            ->select('rating', DB::raw('COUNT(id) as total'))
            ->where('seller_id', $seller->id)
            ->groupBy('rating')
            ->orderByDesc('rating')
            ->get();

        $summary = [
            'average_rating' => round((float) Review::query()->where('seller_id', $seller->id)->avg('rating'), 2),
            'total_reviews' => (int) Review::query()->where('seller_id', $seller->id)->count(),
            'breakdown' => $breakdown,
        ];

        return response()->json([
            'summary' => $summary,
            'reviews' => $query->orderByDesc('id')->paginate(20),
        ]);
    }
}

