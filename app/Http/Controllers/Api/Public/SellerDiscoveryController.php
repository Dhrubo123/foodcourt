<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Seller;
use Illuminate\Http\Request;

class SellerDiscoveryController extends Controller
{
    public function areas(Request $request)
    {
        $query = Area::query()->select(['id', 'city_id', 'name']);

        $query->when($request->filled('city_id'), function ($builder) use ($request) {
            $builder->where('city_id', $request->integer('city_id'));
        });

        return response()->json([
            'data' => $query->orderBy('name')->get(),
        ]);
    }

    public function nearby(Request $request)
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.2', 'max:25'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:300'],
            'type' => ['nullable', 'in:cart,food_court'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'open_now' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:80'],
        ]);

        $radiusKm = (float) ($data['radius_km'] ?? 5);
        $limit = (int) ($data['limit'] ?? 100);

        $query = Seller::query()
            ->with(['area:id,name'])
            ->visible()
            ->nearby((float) $data['lat'], (float) $data['lng'], $radiusKm);

        $query->when(! empty($data['type']), function ($builder) use ($data) {
            $builder->where('type', $data['type']);
        });

        $query->when(! empty($data['area_id']), function ($builder) use ($data) {
            $builder->where('area_id', $data['area_id']);
        });

        $query->when(array_key_exists('open_now', $data) && $data['open_now'], function ($builder) {
            $builder->where('is_open', true);
        });

        $query->when(! empty($data['search']), function ($builder) use ($data) {
            $search = '%' . $data['search'] . '%';
            $builder->where(function ($searchBuilder) use ($search) {
                $searchBuilder->where('name', 'like', $search)
                    ->orWhere('address', 'like', $search);
            });
        });

        $sellers = $query
            ->limit($limit)
            ->get([
                'id',
                'name',
                'type',
                'address',
                'area_id',
                'lat',
                'lng',
                'logo_path',
                'is_open',
                'open_time',
                'close_time',
            ])
            ->map(function ($seller) {
                return [
                    'id' => $seller->id,
                    'name' => $seller->name,
                    'type' => $seller->type,
                    'address' => $seller->address,
                    'area' => $seller->area?->name,
                    'lat' => $seller->lat,
                    'lng' => $seller->lng,
                    'logo_path' => $seller->logo_path,
                    'is_open' => (bool) $seller->is_open,
                    'open_time' => $seller->open_time,
                    'close_time' => $seller->close_time,
                    'distance_km' => isset($seller->distance) ? round((float) $seller->distance, 2) : null,
                ];
            });

        return response()->json([
            'center' => [
                'lat' => (float) $data['lat'],
                'lng' => (float) $data['lng'],
            ],
            'radius_km' => $radiusKm,
            'count' => $sellers->count(),
            'data' => $sellers,
        ]);
    }
}

