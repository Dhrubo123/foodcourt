<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function index(Request $request)
    {
        $query = Subscription::query()
            ->with([
                'seller:id,name,type',
                'plan:id,name,price,duration_days',
            ]);

        $query->when($request->filled('seller_id'), function ($builder) use ($request) {
            $builder->where('seller_id', $request->integer('seller_id'));
        });

        $query->when($request->filled('status'), function ($builder) use ($request) {
            $builder->where('status', $request->string('status'));
        });

        return $query->orderByDesc('id')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'seller_id' => ['required', 'integer', 'exists:sellers,id'],
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'start_at' => ['nullable', 'date'],
            'status' => ['nullable', 'in:active,expired,pending,cancelled'],
        ]);

        $plan = SubscriptionPlan::query()->findOrFail($data['plan_id']);
        $startAt = isset($data['start_at']) ? Carbon::parse($data['start_at']) : now();

        $subscription = Subscription::create([
            'seller_id' => $data['seller_id'],
            'plan_id' => $plan->id,
            'start_at' => $startAt,
            'end_at' => $startAt->copy()->addDays((int) $plan->duration_days),
            'status' => $data['status'] ?? 'active',
        ]);

        return response()->json([
            'subscription' => $subscription->load('seller:id,name,type', 'plan:id,name,price,duration_days'),
        ], 201);
    }

    public function update(Request $request, Subscription $subscription)
    {
        $data = $request->validate([
            'plan_id' => ['sometimes', 'integer', 'exists:subscription_plans,id'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'status' => ['sometimes', 'in:active,expired,pending,cancelled'],
        ]);

        if (array_key_exists('plan_id', $data)) {
            $plan = SubscriptionPlan::query()->findOrFail($data['plan_id']);
            $subscription->plan_id = $plan->id;

            if (! array_key_exists('end_at', $data)) {
                $startAt = array_key_exists('start_at', $data)
                    ? Carbon::parse($data['start_at'])
                    : ($subscription->start_at ?? now());
                $data['end_at'] = $startAt->copy()->addDays((int) $plan->duration_days);
            }
        }

        $subscription->fill($data);
        $subscription->save();

        return response()->json([
            'subscription' => $subscription->fresh()->load('seller:id,name,type', 'plan:id,name,price,duration_days'),
        ]);
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->delete();

        return response()->json(['message' => 'Deleted.']);
    }
}

