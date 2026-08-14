<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use App\Support\ImageUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class FacilityController extends Controller
{
    public function __construct(private readonly ImageUploader $images) {}

    public function index(): View
    {
        return view('admin.facilities.index', [
            'facilities' => Facility::query()->orderBy('sort_order')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.facilities.form', ['facility' => new Facility(['is_active' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->images->store($request->file('image'), 'facilities');
        }

        Facility::query()->create($data);

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas berhasil ditambahkan.');
    }

    public function edit(Facility $facility): View
    {
        return view('admin.facilities.form', ['facility' => $facility]);
    }

    public function update(Request $request, Facility $facility): RedirectResponse
    {
        $data = $this->validated($request, $facility);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->images->replace($request->file('image'), 'facilities', $facility->image_path);
        }

        $facility->update($data);

        return redirect()->route('admin.facilities.index')->with('success', 'Fasilitas berhasil diperbarui.');
    }

    public function destroy(Facility $facility): RedirectResponse
    {
        $this->images->delete($facility->image_path);

        $name = $facility->name;
        $facility->delete();

        return redirect()->route('admin.facilities.index')
            ->with('success', sprintf('Fasilitas "%s" dihapus.', $name));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Facility $facility = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:180', Rule::unique('facilities', 'slug')->ignore($facility)],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:60'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
        ], [
            'image.max' => 'Ukuran gambar maksimal 3 MB.',
        ]);

        unset($validated['image']);

        $validated['slug'] = Str::slug($validated['slug'] ?: $validated['name']) ?: 'fasilitas-'.now()->timestamp;
        $validated['is_active'] = $request->boolean('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        return $validated;
    }
}
