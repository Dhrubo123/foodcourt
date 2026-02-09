<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

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
            'image_path' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
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

        $hasStockQuantityColumn = Schema::hasColumn('products', 'stock_quantity');
        $hasImagePathColumn = Schema::hasColumn('products', 'image_path');
        $stockQuantity = (int) ($data['stock_quantity'] ?? 0);

        $payload = [
            'seller_id' => $seller->id,
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? 0,
            'is_available' => array_key_exists('is_available', $data) ? (bool) $data['is_available'] : $stockQuantity > 0,
        ];

        if ($hasStockQuantityColumn) {
            $payload['stock_quantity'] = $stockQuantity;
        }

        if ($hasImagePathColumn) {
            $payload['image_path'] = $data['image_path'] ?? null;
            if ($request->hasFile('image')) {
                $payload['image_path'] = $this->storeImage($request);
            }
        }

        $product = Product::create($payload);

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
            'image_path' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'stock_quantity' => ['sometimes', 'integer', 'min:0'],
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

        $hasStockQuantityColumn = Schema::hasColumn('products', 'stock_quantity');
        $hasImagePathColumn = Schema::hasColumn('products', 'image_path');

        if (array_key_exists('stock_quantity', $data) && ! array_key_exists('is_available', $data)) {
            $data['is_available'] = (int) $data['stock_quantity'] > 0;
        }

        if (! $hasStockQuantityColumn) {
            unset($data['stock_quantity']);
        }

        if (! $hasImagePathColumn) {
            unset($data['image_path']);
        } else {
            if ($request->hasFile('image')) {
                $this->deleteLocalImage($product->image_path);
                $data['image_path'] = $this->storeImage($request);
            } elseif (array_key_exists('image_path', $data) && ($data['image_path'] === null || $data['image_path'] === '')) {
                $this->deleteLocalImage($product->image_path);
                $data['image_path'] = null;
            }
        }

        unset($data['image']);

        $product->update($data);

        return response()->json(['product' => $product->fresh('category')]);
    }

    public function destroy(Request $request, Product $product)
    {
        $seller = $request->user()->seller;

        if (! $seller || $product->seller_id !== $seller->id) {
            return response()->json(['message' => 'Unauthorized product access.'], 403);
        }

        $this->deleteLocalImage($product->image_path);
        $product->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function storeImage(Request $request): string
    {
        $path = $request->file('image')->store('products', 'public');
        return Storage::disk('public')->url($path);
    }

    private function deleteLocalImage(?string $imagePath): void
    {
        if (! $imagePath || ! str_starts_with($imagePath, '/storage/')) {
            return;
        }

        $relativePath = ltrim(str_replace('/storage/', '', $imagePath), '/');
        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
