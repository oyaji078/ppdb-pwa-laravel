<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolProfile;
use App\Support\ImageUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SchoolProfileController extends Controller
{
    public function __construct(private readonly ImageUploader $images) {}

    public function index(): View
    {
        return view('admin.school-profile.index', [
            'sections' => SchoolProfile::query()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, SchoolProfile $profile): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'content' => ['nullable', 'string', 'max:20000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'image.max' => 'Ukuran gambar maksimal 3 MB.',
        ]);

        unset($validated['image']);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $this->images->replace($request->file('image'), 'profile', $profile->image_path);
        }

        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? $profile->sort_order;

        $profile->update($validated);

        return back()->with('success', sprintf('Bagian "%s" berhasil diperbarui.', $profile->title));
    }
}
