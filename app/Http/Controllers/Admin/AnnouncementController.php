<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementAudience;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function __construct(private readonly ActivityLogger $activity) {}

    public function index(Request $request): View
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::query()
                ->with(['academicYear', 'author'])
                ->when($request->filled('audience'), fn ($q) => $q->where('audience', $request->string('audience')))
                ->when($request->filled('q'), fn ($q) => $q->whereLike('title', '%'.$request->string('q').'%'))
                ->latest('created_at')
                ->paginate(15)
                ->withQueryString(),
            'audiences' => AnnouncementAudience::options(),
        ]);
    }

    public function create(): View
    {
        return view('admin.announcements.form', [
            'announcement' => new Announcement([
                'academic_year_id' => AcademicYear::current()?->id,
                'audience' => AnnouncementAudience::Public,
            ]),
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'audiences' => AnnouncementAudience::options(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;

        $announcement = Announcement::query()->create($data);

        if ($announcement->is_published) {
            $this->activity->log(
                ActivityLogger::ANNOUNCEMENT_PUBLISHED,
                sprintf('Pengumuman "%s" dipublikasikan.', $announcement->title),
                $announcement
            );
        }

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil dibuat.');
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.form', [
            'announcement' => $announcement,
            'academicYears' => AcademicYear::query()->orderByDesc('start_year')->get(),
            'audiences' => AnnouncementAudience::options(),
        ]);
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $wasPublished = $announcement->is_published;

        $announcement->update($this->validated($request, $announcement));

        if (! $wasPublished && $announcement->is_published) {
            $this->activity->log(
                ActivityLogger::ANNOUNCEMENT_PUBLISHED,
                sprintf('Pengumuman "%s" dipublikasikan.', $announcement->title),
                $announcement
            );
        }

        return redirect()->route('admin.announcements.index')
            ->with('success', 'Pengumuman berhasil diperbarui.');
    }

    public function togglePublish(Announcement $announcement): RedirectResponse
    {
        $announcement->update([
            'is_published' => ! $announcement->is_published,
            'published_at' => $announcement->is_published ? $announcement->published_at : now(),
        ]);

        if ($announcement->is_published) {
            $this->activity->log(
                ActivityLogger::ANNOUNCEMENT_PUBLISHED,
                sprintf('Pengumuman "%s" dipublikasikan.', $announcement->title),
                $announcement
            );
        }

        return back()->with('success', $announcement->is_published
            ? 'Pengumuman dipublikasikan.'
            : 'Pengumuman disembunyikan.');
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $title = $announcement->title;
        $announcement->delete();

        return redirect()->route('admin.announcements.index')
            ->with('success', sprintf('Pengumuman "%s" dihapus.', $title));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Announcement $announcement = null): array
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'nullable', 'string', 'max:220',
                Rule::unique('announcements', 'slug')->whereNull('deleted_at')->ignore($announcement),
            ],
            'content' => ['required', 'string'],
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'published_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after:published_at'],
        ], [
            'expires_at.after' => 'Tanggal kedaluwarsa harus setelah tanggal publikasi.',
        ]);

        $validated['slug'] = $this->uniqueSlug(
            $validated['slug'] ?: $validated['title'],
            $announcement
        );

        $validated['is_published'] = $request->boolean('is_published');
        $validated['published_at'] = $validated['published_at']
            ?? ($validated['is_published'] ? now() : null);

        return $validated;
    }

    /**
     * Slugs are the public URL, so collisions get a numeric suffix rather than
     * failing the save.
     */
    private function uniqueSlug(string $source, ?Announcement $announcement = null): string
    {
        $base = Str::slug($source) ?: 'pengumuman';
        $slug = $base;
        $suffix = 1;

        while (Announcement::query()
            ->where('slug', $slug)
            ->when($announcement, fn ($q) => $q->whereKeyNot($announcement->id))
            ->withTrashed()
            ->exists()
        ) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
