<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Seller;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function index()
    {
        try {
            $featuredSellers = Seller::query()
                ->with(['area:id,name', 'offers' => function ($query) {
                    $query->where('is_active', true)
                        ->orderByDesc('id')
                        ->limit(1);
                }])
                ->visible()
                ->where('is_open', true)
                ->orderByDesc('id')
                ->limit(8)
                ->get([
                    'id',
                    'name',
                    'type',
                    'address',
                    'area_id',
                    'open_time',
                    'close_time',
                    'logo_path',
                ]);

            $trendingProducts = Product::query()
                ->with(['seller:id,name,type'])
                ->where('is_available', true)
                ->whereHas('seller', function ($query) {
                    $query->visible()->where('is_open', true);
                })
                ->orderByDesc('id')
                ->limit(10)
                ->get([
                    'id',
                    'seller_id',
                    'name',
                    'price',
                    'description',
                ]);

            return response()->json([
                'featured_sellers' => $featuredSellers,
                'trending_products' => $trendingProducts,
            ]);
        } catch (\Throwable $exception) {
            // Keep home page alive even when DB is not ready yet.
            Log::warning('Public home feed failed', ['error' => $exception->getMessage()]);

            return response()->json([
                'featured_sellers' => [],
                'trending_products' => [],
            ]);
        }
    }
}
