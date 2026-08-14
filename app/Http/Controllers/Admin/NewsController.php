<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Support\ImageUploader;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NewsController extends Controller
{
    public function __construct(private readonly ImageUploader $images) {}

    public function index(Request $request): View
    {
        return view('admin.news.index', [
            'news' => News::query()
                ->with('author')
                ->when($request->filled('q'), fn ($q) => $q->where('title', 'like', '%'.$request->string('q').'%'))
                ->latest('created_at')
                ->paginate(15)
                ->withQueryString(),
        ]);
    }

    public function create(): View
    {
        return view('admin.news.form', ['news' => new News]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $this->images->store($request->file('cover'), 'news');
        }

        News::query()->create($data);

        return redirect()->route('admin.news.index')->with('success', 'Berita berhasil dibuat.');
    }

    public function edit(News $news): View
    {
        return view('admin.news.form', ['news' => $news]);
    }

    public function update(Request $request, News $news): RedirectResponse
    {
        $data = $this->validated($request, $news);

        if ($request->hasFile('cover')) {
            $data['cover_path'] = $this->images->replace($request->file('cover'), 'news', $news->cover_path);
        }

        $news->update($data);

        return redirect()->route('admin.news.index')->with('success', 'Berita berhasil diperbarui.');
    }

    public function destroy(News $news): RedirectResponse
    {
        $title = $news->title;
        $news->delete();

        return redirect()->route('admin.news.index')
            ->with('success', sprintf('Berita "%s" dihapus.', $title));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?News $news = null): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'nullable', 'string', 'max:220',
                Rule::unique('news', 'slug')->whereNull('deleted_at')->ignore($news),
            ],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:3072'],
            'published_at' => ['nullable', 'date'],
        ], [
            'cover.max' => 'Ukuran gambar sampul maksimal 3 MB.',
            'cover.mimes' => 'Gambar sampul harus berformat JPG, PNG, atau WEBP.',
        ]);

        unset($validated['cover']);

        $base = Str::slug($validated['slug'] ?: $validated['title']) ?: 'berita';
        $slug = $base;
        $suffix = 1;

        while (News::query()->where('slug', $slug)->when($news, fn ($q) => $q->whereKeyNot($news->id))->withTrashed()->exists()) {
            $slug = $base.'-'.(++$suffix);
        }

        $validated['slug'] = $slug;
        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $validated['published_at'] ?? ($validated['is_published'] ? now() : null);

        return $validated;
    }
}
