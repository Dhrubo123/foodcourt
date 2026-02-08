<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        return ProductCategory::query()
            ->where(function ($builder) use ($seller) {
                $builder->whereNull('seller_id')->orWhere('seller_id', $seller->id);
            })
            ->orderByRaw('seller_id is null')
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category = ProductCategory::create([
            'seller_id' => $seller->id,
            'name' => $data['name'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['category' => $category], 201);
    }

    public function update(Request $request, ProductCategory $category)
    {
        $seller = $request->user()->seller;

        if (! $seller || $category->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized category access.'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category->update($data);

        return response()->json(['category' => $category->fresh()]);
    }

    public function destroy(Request $request, ProductCategory $category)
    {
        $seller = $request->user()->seller;

        if (! $seller || $category->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized category access.'], 403);
        }

        $category->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}

