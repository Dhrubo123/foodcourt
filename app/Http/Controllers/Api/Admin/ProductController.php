<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()->with([
            'seller:id,name,type',
            'category:id,name',
        ]);

        $query->when($request->filled('seller_id'), function ($builder) use ($request) {
            $builder->where('seller_id', $request->integer('seller_id'));
        });

        $query->when($request->filled('category_id'), function ($builder) use ($request) {
            $builder->where('category_id', $request->integer('category_id'));
        });

        $query->when($request->filled('is_available'), function ($builder) use ($request) {
            $builder->where('is_available', $request->boolean('is_available'));
        });

        $query->when($request->filled('search'), function ($builder) use ($request) {
            $search = '%' . $request->string('search') . '%';
            $builder->where(function ($searchBuilder) use ($search) {
                $searchBuilder->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhereHas('seller', function ($sellerQuery) use ($search) {
                        $sellerQuery->where('name', 'like', $search);
                    });
            });
        });

        return $query->orderByDesc('id')->paginate(30);
    }
}

