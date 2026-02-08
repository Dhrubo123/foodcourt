<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        return Product::query()
            ->with('category:id,name')
            ->where('seller_id', $seller->id)
            ->orderByDesc('id')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        if (! empty($data['category_id'])) {
            $categoryAllowed = ProductCategory::query()
                ->where('id', $data['category_id'])
                ->where(function ($query) use ($seller) {
                    $query->whereNull('seller_id')->orWhere('seller_id', $seller->id);
                })
                ->exists();

            if (! $categoryAllowed) {
                return response()->json(['message' => 'Invalid category for this seller.'], 422);
            }
        }

        $product = Product::create([
            'seller_id' => $seller->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? 0,
            'is_available' => $data['is_available'] ?? true,
        ]);

        return response()->json(['product' => $product->load('category')], 201);
    }

    public function update(Request $request, Product $product)
    {
        $seller = $request->user()->seller;

        if (! $seller || $product->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized product access.'], 403);
        }

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'name' => ['sometimes', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'is_available' => ['sometimes', 'boolean'],
        ]);

        if (! empty($data['category_id'])) {
            $categoryAllowed = ProductCategory::query()
                ->where('id', $data['category_id'])
                ->where(function ($query) use ($seller) {
                    $query->whereNull('seller_id')->orWhere('seller_id', $seller->id);
                })
                ->exists();

            if (! $categoryAllowed) {
                return response()->json(['message' => 'Invalid category for this seller.'], 422);
            }
        }

        $product->update($data);

        return response()->json(['product' => $product->fresh('category')]);
    }

    public function destroy(Request $request, Product $product)
    {
        $seller = $request->user()->seller;

        if (! $seller || $product->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized product access.'], 403);
        }

        $product->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
