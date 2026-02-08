<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SellerSettlement;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerSettlementController extends Controller
{
    public function index(Request $request)
    {
        $query = SellerSettlement::query()->with('seller:id,name,type');

        $query->when($request->filled('seller_id'), function ($builder) use ($request) {
            $builder->where('seller_id', $request->integer('seller_id'));
        });

        $query->when($request->filled('status'), function ($builder) use ($request) {
            $builder->where('status', $request->string('status'));
        });

        $query->when($request->filled('from'), function ($builder) use ($request) {
            $builder->whereDate('period_to', '>=', $request->string('from'));
        });

        $query->when($request->filled('to'), function ($builder) use ($request) {
            $builder->whereDate('period_from', '<=', $request->string('to'));
        });

        return $query->orderByDesc('id')->paginate(20);
    }

    public function summary()
    {
        $base = SellerSettlement::query();

        $totals = [
            'gross_amount' => round((float) (clone $base)->sum('gross_amount'), 2),
            'commission_amount' => round((float) (clone $base)->sum('commission_amount'), 2),
            'net_amount' => round((float) (clone $base)->sum('net_amount'), 2),
            'paid_amount' => round((float) (clone $base)->where('status', 'paid')->sum('net_amount'), 2),
            'due_amount' => round((float) (clone $base)->where('status', 'pending')->sum('net_amount'), 2),
            'pending_count' => (int) (clone $base)->where('status', 'pending')->count(),
            'paid_count' => (int) (clone $base)->where('status', 'paid')->count(),
        ];

        return response()->json($totals);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
            'seller_id' => ['nullable', 'integer', 'exists:sellers,id'],
            'commission_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $commissionPercent = isset($data['commission_percent'])
            ? (float) $data['commission_percent']
            : (float) SystemSetting::getValue('commission_percent', 10);

        $rows = DB::table('seller_orders as so')
            ->join('sellers as s', 's.id', '=', 'so.seller_id')
            ->where('so.seller_status', 'delivered')
            ->whereDate('so.created_at', '>=', $data['from'])
            ->whereDate('so.created_at', '<=', $data['to'])
            ->when(! empty($data['seller_id']), function ($builder) use ($data) {
                $builder->where('so.seller_id', $data['seller_id']);
            })
            ->groupBy('s.id')
            ->select([
                's.id as seller_id',
            ])
            ->selectRaw('COUNT(so.id) as orders_count')
            ->selectRaw('COALESCE(SUM(so.total_after_discount), 0) as gross_amount')
            ->get();

        $created = [];

        foreach ($rows as $row) {
            $grossAmount = (float) $row->gross_amount;
            $commissionAmount = round(($grossAmount * $commissionPercent) / 100, 2);
            $netAmount = round($grossAmount - $commissionAmount, 2);

            $settlement = SellerSettlement::query()->updateOrCreate(
                [
                    'seller_id' => (int) $row->seller_id,
                    'period_from' => $data['from'],
                    'period_to' => $data['to'],
                    'status' => 'pending',
                ],
                [
                    'orders_count' => (int) $row->orders_count,
                    'gross_amount' => round($grossAmount, 2),
                    'commission_percent' => $commissionPercent,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $netAmount,
                    'payment_method' => null,
                    'trx_id' => null,
                    'paid_at' => null,
                    'notes' => null,
                ]
            );

            $created[] = $settlement->load('seller:id,name,type');
        }

        return response()->json([
            'commission_percent' => $commissionPercent,
            'items' => $created,
        ]);
    }

    public function markPaid(Request $request, SellerSettlement $sellerSettlement)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:50'],
            'trx_id' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string'],
        ]);

        $sellerSettlement->update([
            'status' => 'paid',
            'payment_method' => $data['payment_method'],
            'trx_id' => $data['trx_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'paid_at' => now(),
        ]);

        return response()->json([
            'settlement' => $sellerSettlement->fresh()->load('seller:id,name,type'),
        ]);
    }
}

