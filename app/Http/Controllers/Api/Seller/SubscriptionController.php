<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function show(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $current = Subscription::query()
            ->with('plan:id,name,price,duration_days')
            ->where('seller_id', $seller->id)
            ->orderByDesc('id')
            ->first();

        $availablePlans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->orderBy('price')
            ->get();

        $history = Subscription::query()
            ->with('plan:id,name,price,duration_days')
            ->where('seller_id', $seller->id)
            ->orderByDesc('id')
            ->limit(12)
            ->get();

        return response()->json([
            'current_subscription' => $current,
            'plans' => $availablePlans,
            'history' => $history,
        ]);
    }

    public function renew(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
        ]);

        $plan = SubscriptionPlan::query()
            ->where('id', $data['plan_id'])
            ->where('is_active', true)
            ->first();

        if (! $plan) {
            return response()->json(['message' => 'Selected plan is not active.'], 422);
        }

        $current = Subscription::query()
            ->where('seller_id', $seller->id)
            ->orderByDesc('id')
            ->first();

        $startAt = now();
        if ($current && $current->end_at && Carbon::parse($current->end_at)->isFuture()) {
            $startAt = Carbon::parse($current->end_at);
        }

        $subscription = Subscription::create([
            'seller_id' => $seller->id,
            'plan_id' => $plan->id,
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addDays((int) $plan->duration_days),
            'status' => 'active',
        ]);

        return response()->json([
            'subscription' => $subscription->load('plan:id,name,price,duration_days'),
        ], 201);
    }
}

