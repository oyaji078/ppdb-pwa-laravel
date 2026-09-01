<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Support\ImageUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $attributes = $this->validated($request);

        // Photos may be chosen while the album is being created. They are
        // checked before it exists, so a rejected file does not leave an empty
        // album behind for someone to clean up.
        $files = $this->validatedImages($request);

        $gallery = Gallery::query()->create($attributes);
        $saved = $this->storeImages($gallery, $files, $request->input('caption'));

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $gallery->id,
                'editUrl' => route('admin.galleries.edit', $gallery),
            ], 201);
        }

        return redirect()->route('admin.galleries.edit', $gallery)->with(
            'success',
            $saved > 0
                ? sprintf('Album dibuat dengan %d foto.', $saved)
                : 'Album berhasil dibuat. Silakan unggah foto.'
        );
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

    /**
     * Adds photos to an album that already exists.
     *
     * The page calls this once per photo rather than sending the whole batch,
     * because a serverless host rejects a request body over 4.5 MB before PHP
     * runs: two photos at the 3 MB ceiling would exceed it together while
     * neither does alone.
     */
    public function storeImage(Request $request, Gallery $gallery): RedirectResponse|JsonResponse
    {
        $saved = $this->storeImages(
            $gallery,
            $this->validatedImages($request, required: true),
            $request->input('caption'),
        );

        $message = sprintf('%d foto berhasil diunggah.', $saved);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'saved' => $saved]);
        }

        return back()->with('success', $message);
    }

    /**
     * The photos in the request, once they are known to be usable.
     *
     * @return array<int, \Illuminate\Http\UploadedFile>
     */
    private function validatedImages(Request $request, bool $required = false): array
    {
        $validated = $request->validate([
            'images' => [$required ? 'required' : 'nullable', 'array', 'max:20'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'caption' => ['nullable', 'string', 'max:200'],
        ], [
            'images.required' => 'Pilih minimal satu foto.',
            'images.*.max' => 'Ukuran setiap foto maksimal 3 MB.',
            'images.*.image' => 'Setiap berkas harus berupa gambar.',
            'images.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            // Fires when PHP itself rejected the upload (upload_max_filesize /
            // post_max_size), so Laravel never receives a usable file. The raw
            // key would read "images.0", which means nothing to an operator.
            'images.*.uploaded' => 'Foto gagal diunggah. Ukuran melebihi batas server, hubungi pengelola untuk menaikkan upload_max_filesize.',
        ]);

        return $validated['images'] ?? [];
    }

    /**
     * @param  array<int, \Illuminate\Http\UploadedFile>  $files
     * @return int how many were saved
     */
    private function storeImages(Gallery $gallery, array $files, ?string $caption): int
    {
        $order = (int) $gallery->images()->max('sort_order');

        foreach ($files as $file) {
            $gallery->images()->create([
                'image_path' => $this->images->store($file, 'gallery'),
                'caption' => $caption ?: null,
                'sort_order' => ++$order,
            ]);
        }

        return count($files);
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

        // The form has no slug field, so the key is absent rather than empty:
        // "?:" alone would read an undefined index.
        $validated['slug'] = Str::slug(($validated['slug'] ?? null) ?: $validated['title']) ?: 'album-'.now()->timestamp;
        $validated['is_published'] = $request->boolean('is_published');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
