<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return response()->json([
            'commission_percent' => (float) SystemSetting::getValue('commission_percent', 10),
            'tax_percent' => (float) SystemSetting::getValue('tax_percent', 0),
            'payment_methods' => (array) SystemSetting::getValue('payment_methods', ['cash', 'sslcommerz']),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'commission_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'tax_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_methods' => ['required', 'array', 'min:1'],
            'payment_methods.*' => ['string', 'max:50'],
        ]);

        SystemSetting::putValue('commission_percent', (float) $data['commission_percent']);
        SystemSetting::putValue('tax_percent', (float) $data['tax_percent']);
        SystemSetting::putValue('payment_methods', array_values(array_unique($data['payment_methods'])));

        return $this->index();
    }
}

