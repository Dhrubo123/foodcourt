<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BannerController extends Controller
{
    public function index()
    {
        return Banner::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:width=1920,height=800'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_link' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->storeImage($request);
        }

        unset($data['image']);

        $banner = Banner::create($data);

        return response()->json(['banner' => $banner], 201);
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'title' => ['sometimes', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096', 'dimensions:width=1920,height=800'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'cta_link' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'start_at' => ['nullable', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
        ]);

        if ($request->hasFile('image')) {
            $this->deleteLocalImage($banner->image_path);
            $data['image_path'] = $this->storeImage($request);
        }

        unset($data['image']);

        $banner->update($data);

        return response()->json(['banner' => $banner]);
    }

    public function destroy(Banner $banner)
    {
        $this->deleteLocalImage($banner->image_path);
        $banner->delete();

        return response()->json(['message' => 'Deleted.']);
    }

    private function storeImage(Request $request): string
    {
        $path = $request->file('image')->store('banners', 'public');
        return Storage::disk('public')->url($path);
    }

    private function deleteLocalImage(?string $imagePath): void
    {
        if (! $imagePath) {
            return;
        }

        if (! str_starts_with($imagePath, '/storage/')) {
            return;
        }

        $relativePath = ltrim(str_replace('/storage/', '', $imagePath), '/');

        if ($relativePath !== '') {
            Storage::disk('public')->delete($relativePath);
        }
    }
}
