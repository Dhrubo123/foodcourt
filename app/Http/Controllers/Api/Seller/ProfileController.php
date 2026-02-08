<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        return response()->json([
            'seller' => $seller->load('owner:id,name,email,phone'),
        ]);
    }

    public function update(Request $request)
    {
        $seller = $request->user()->seller;

        if (! $seller) {
            return response()->json(['message' => 'Seller profile not found.'], 404);
        }

        $owner = $seller->owner;

        $data = $request->validate([
            'owner_name' => ['nullable', 'string', 'max:150'],
            'owner_email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($owner ? $owner->id : null),
            ],
            'owner_phone' => [
                'nullable',
                'string',
                'max:30',
                Rule::unique('users', 'phone')->ignore($owner ? $owner->id : null),
            ],
            'name' => ['nullable', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'open_time' => ['nullable', 'date_format:H:i'],
            'close_time' => ['nullable', 'date_format:H:i'],
            'is_open' => ['nullable', 'boolean'],
            'default_prep_time_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if ($owner) {
            $ownerData = [];
            if (array_key_exists('owner_name', $data)) {
                $ownerData['name'] = $data['owner_name'];
            }
            if (array_key_exists('owner_email', $data)) {
                $ownerData['email'] = $data['owner_email'];
            }
            if (array_key_exists('owner_phone', $data)) {
                $ownerData['phone'] = $data['owner_phone'];
            }
            if (! empty($ownerData)) {
                $owner->update($ownerData);
            }
        }

        if ($request->hasFile('logo')) {
            $this->deleteLocalImage($seller->logo_path);
            $path = $request->file('logo')->store('seller-logos', 'public');
            $data['logo_path'] = Storage::disk('public')->url($path);
        }

        unset($data['logo'], $data['owner_name'], $data['owner_email'], $data['owner_phone']);

        $seller->update($data);

        return response()->json([
            'seller' => $seller->fresh()->load('owner:id,name,email,phone'),
        ]);
    }

    private function deleteLocalImage(?string $imagePath): void
    {
        if (! $imagePath || ! str_starts_with($imagePath, '/storage/')) {
            return;
        }

        $relativePath = ltrim(str_replace('/storage/', '', $imagePath), '/');
        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}

