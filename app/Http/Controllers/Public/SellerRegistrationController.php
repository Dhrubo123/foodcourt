<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SellerRegistrationController extends Controller
{
    public function show()
    {
        return view('seller-register');
    }

    public function store(Request $request)
    {
        $this->registerSeller($request);

        return redirect()
            ->route('seller.register')
            ->with('success', 'Your registration has been submitted. We will review and contact you soon.');
    }

    public function apiStore(Request $request)
    {
        $seller = $this->registerSeller($request);

        return response()->json([
            'message' => 'Registration submitted.',
            'seller_id' => $seller->id,
        ], 201);
    }

    private function registerSeller(Request $request): Seller
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
        ]);

        return DB::transaction(function () use ($data) {
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
                'is_approved' => false,
                'is_open' => true,
            ]);
        });
    }
}
