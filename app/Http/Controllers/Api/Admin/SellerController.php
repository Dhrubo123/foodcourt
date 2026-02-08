<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SellerController extends Controller
{
    public function index(Request $request)
    {
        $query = Seller::query()->with(['owner', 'area']);

        $query->when($request->filled('type'), function ($builder) use ($request) {
            $builder->where('type', $request->string('type'));
        });

        $query->when($request->filled('approved'), function ($builder) use ($request) {
            $builder->where('is_approved', $request->boolean('approved'));
        });

        $query->when($request->filled('blocked'), function ($builder) use ($request) {
            $builder->where('is_blocked', $request->boolean('blocked'));
        });

        $query->when($request->filled('area_id'), function ($builder) use ($request) {
            $builder->where('area_id', $request->integer('area_id'));
        });

        $query->when($request->filled('search'), function ($builder) use ($request) {
            $search = '%' . $request->string('search') . '%';
            $builder->where('name', 'like', $search);
        });

        return $query->orderByDesc('id')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'owner_name' => ['required', 'string', 'max:150'],
            'owner_email' => ['nullable', 'email', 'max:150', 'unique:users,email', 'required_without:owner_phone'],
            'owner_phone' => ['nullable', 'string', 'max:30', 'unique:users,phone', 'required_without:owner_email'],
            'password' => ['required', 'string', 'min:8'],
            'type' => ['required', 'in:cart,food_court'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'lat' => ['nullable', 'numeric'],
            'lng' => ['nullable', 'numeric'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'is_approved' => ['nullable', 'boolean'],
            'is_open' => ['nullable', 'boolean'],
        ]);

        $seller = DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'] ?? null,
                'phone' => $data['owner_phone'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => 'active',
            ]);

            if (Role::where('name', 'seller_owner')->exists()) {
                $user->assignRole('seller_owner');
            }

            return Seller::create([
                'owner_user_id' => $user->id,
                'type' => $data['type'],
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'area_id' => $data['area_id'] ?? null,
                'lat' => $data['lat'] ?? null,
                'lng' => $data['lng'] ?? null,
                'open_time' => $data['open_time'] ?? null,
                'close_time' => $data['close_time'] ?? null,
                'is_approved' => $data['is_approved'] ?? false,
                'is_open' => $data['is_open'] ?? true,
            ]);
        });

        return response()->json(['seller' => $seller->load('owner', 'area')], 201);
    }

    public function approve(Request $request, Seller $seller)
    {
        $data = $request->validate([
            'is_approved' => ['nullable', 'boolean'],
        ]);

        $seller->is_approved = $data['is_approved'] ?? true;
        $seller->save();

        return response()->json(['seller' => $seller->fresh()]);
    }

    public function block(Request $request, Seller $seller)
    {
        $data = $request->validate([
            'is_blocked' => ['nullable', 'boolean'],
        ]);

        $seller->is_blocked = $data['is_blocked'] ?? true;
        $seller->save();

        return response()->json(['seller' => $seller->fresh()]);
    }
}
