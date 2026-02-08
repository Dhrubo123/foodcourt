<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionPlanController extends Controller
{
    public function index()
    {
        return SubscriptionPlan::query()
            ->withCount([
                'subscriptions as total_subscriptions_count',
                'subscriptions as active_subscriptions_count' => function ($query) {
                    $query->where('status', 'active')
                        ->where('end_at', '>=', now());
                },
            ])
            ->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:subscription_plans,name'],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $plan = SubscriptionPlan::create($data);

        return response()->json(['plan' => $plan], 201);
    }

    public function update(Request $request, SubscriptionPlan $subscriptionPlan)
    {
        $data = $request->validate([
            'name' => [
                'sometimes',
                'string',
                'max:100',
                Rule::unique('subscription_plans', 'name')->ignore($subscriptionPlan->id),
            ],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'duration_days' => ['sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $subscriptionPlan->update($data);

        return response()->json(['plan' => $subscriptionPlan]);
    }

    public function destroy(SubscriptionPlan $subscriptionPlan)
    {
        $subscriptionPlan->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}
