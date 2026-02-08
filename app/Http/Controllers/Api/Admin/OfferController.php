<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OfferController extends Controller
{
    public function index(Request $request)
    {
        $query = Offer::query()->with('seller:id,name,type');

        $query->when($request->filled('seller_id'), function ($builder) use ($request) {
            $builder->where('seller_id', $request->integer('seller_id'));
        });

        $query->when($request->filled('is_active'), function ($builder) use ($request) {
            $builder->where('is_active', $request->boolean('is_active'));
        });

        $query->when($request->filled('search'), function ($builder) use ($request) {
            $search = '%' . $request->string('search') . '%';
            $builder->where(function ($searchBuilder) use ($search) {
                $searchBuilder->where('title', 'like', $search)
                    ->orWhere('code', 'like', $search);
            });
        });

        return $query->orderByDesc('id')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'seller_id' => ['required', 'integer', 'exists:sellers,id'],
            'title' => ['required', 'string', 'max:150'],
            'code' => ['nullable', 'string', 'max:60', 'unique:offers,code'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $offer = Offer::create($data);

        return response()->json([
            'offer' => $offer->load('seller:id,name,type'),
        ], 201);
    }

    public function update(Request $request, Offer $offer)
    {
        $data = $request->validate([
            'seller_id' => ['sometimes', 'integer', 'exists:sellers,id'],
            'title' => ['sometimes', 'string', 'max:150'],
            'code' => [
                'nullable',
                'string',
                'max:60',
                Rule::unique('offers', 'code')->ignore($offer->id),
            ],
            'type' => ['sometimes', 'in:percent,fixed'],
            'value' => ['sometimes', 'numeric', 'min:0'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $offer->update($data);

        return response()->json([
            'offer' => $offer->fresh()->load('seller:id,name,type'),
        ]);
    }

    public function destroy(Offer $offer)
    {
        $offer->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}

