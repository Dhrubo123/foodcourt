<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount(['orders', 'reviews'])
            ->withSum('orders as total_spent', 'grand_total')
            ->where(function ($builder) {
                $builder
                    ->whereHas('roles', function ($roleQuery) {
                        $roleQuery->where('name', 'customer');
                    })
                    ->orWhereHas('orders');
            });

        $query->when($request->filled('search'), function ($builder) use ($request) {
            $search = '%' . $request->string('search') . '%';
            $builder->where(function ($searchBuilder) use ($search) {
                $searchBuilder->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        });

        $query->when($request->filled('status'), function ($builder) use ($request) {
            $builder->where('status', $request->string('status'));
        });

        return $query->orderByDesc('id')->paginate(20);
    }

    public function updateStatus(Request $request, User $customer)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,blocked'],
        ]);

        if ($customer->hasRole('super_admin') || $customer->hasRole('seller_owner') || $customer->hasRole('vendor')) {
            return response()->json([
                'message' => 'Only customer accounts can be updated from this module.',
            ], 422);
        }

        $customer->status = $data['status'];
        $customer->save();

        return response()->json([
            'customer' => $customer->fresh(),
        ]);
    }
}
