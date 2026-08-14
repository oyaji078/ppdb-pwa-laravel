<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Support\ImageUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GalleryController extends Controller
{
    public function __construct(private readonly ImageUploader $images) {}

    public function index(): View
    {
        return view('admin.galleries.index', [
            'galleries' => Gallery::query()
                ->withCount('images')
                ->orderBy('sort_order')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.galleries.form', ['gallery' => new Gallery(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $gallery = Gallery::query()->create($this->validated($request));

        return redirect()->route('admin.galleries.edit', $gallery)
            ->with('success', 'Album berhasil dibuat. Silakan unggah foto.');
    }

    public function edit(Gallery $gallery): View
    {
        return view('admin.galleries.form', ['gallery' => $gallery->load('images')]);
    }

    public function update(Request $request, Gallery $gallery): RedirectResponse
    {
        $gallery->update($this->validated($request, $gallery));

        return redirect()->route('admin.galleries.index')->with('success', 'Album berhasil diperbarui.');
    }

    public function destroy(Gallery $gallery): RedirectResponse
    {
        foreach ($gallery->images as $image) {
            $this->images->delete($image->image_path);
        }

        $title = $gallery->title;
        $gallery->delete();

        return redirect()->route('admin.galleries.index')
            ->with('success', sprintf('Album "%s" dihapus.', $title));
    }

    public function storeImage(Request $request, Gallery $gallery): RedirectResponse
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'caption' => ['nullable', 'string', 'max:200'],
        ], [
            'images.required' => 'Pilih minimal satu foto.',
            'images.*.max' => 'Ukuran setiap foto maksimal 3 MB.',
        ]);

        $order = (int) $gallery->images()->max('sort_order');

        foreach ($validated['images'] as $file) {
            $gallery->images()->create([
                'image_path' => $this->images->store($file, 'gallery'),
                'caption' => $validated['caption'] ?? null,
                'sort_order' => ++$order,
            ]);
        }

        return back()->with('success', sprintf('%d foto berhasil diunggah.', count($validated['images'])));
    }

    public function destroyImage(GalleryImage $image): RedirectResponse
    {
        $this->images->delete($image->image_path);
        $image->delete();

        return back()->with('success', 'Foto dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Gallery $gallery = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('galleries', 'slug')->ignore($gallery)],
            'description' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ]);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['title']) ?: 'album-'.now()->timestamp;
        $validated['is_published'] = $request->boolean('is_published');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
