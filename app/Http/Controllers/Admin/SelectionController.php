<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\SelectionStatus;
use App\Http\Controllers\Admin\Concerns\FiltersRegistrations;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\SelectionService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SelectionController extends Controller
{
    use FiltersRegistrations;

    public function __construct(private readonly SelectionService $selection) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Registration::class);

        // Only verified registrations are selectable; the filter bar can still
        // narrow further by track, program or decision.
        $registrations = $this->filteredRegistrations($request)
            ->where('registration_status', RegistrationStatus::Verified)
            ->when($request->query('decision') === 'undecided',
                fn (Builder $q) => $q->whereDoesntHave('selectionResult'))
            ->when($request->query('decision') === 'unpublished',
                fn (Builder $q) => $q->whereHas('selectionResult', fn (Builder $r) => $r->whereNull('published_at')))
            ->when($request->query('decision') === 'published',
                fn (Builder $q) => $q->whereHas('selectionResult', fn (Builder $r) => $r->whereNotNull('published_at')))
            ->orderBy('registration_number')
            ->paginate($this->perPage($request))
            ->withQueryString();

        $scope = $this->filteredRegistrations($request)
            ->where('registration_status', RegistrationStatus::Verified);

        return view('admin.selection.index', array_merge(
            $this->filterOptions($request),
            [
                'registrations' => $registrations,
                'statusOptions' => SelectionStatus::decidableOptions(),
                'summary' => [
                    'verified' => (clone $scope)->count(),
                    'undecided' => (clone $scope)->whereDoesntHave('selectionResult')->count(),
                    'unpublished' => (clone $scope)
                        ->whereHas('selectionResult', fn (Builder $r) => $r->whereNull('published_at'))->count(),
                    'published' => (clone $scope)
                        ->whereHas('selectionResult', fn (Builder $r) => $r->whereNotNull('published_at'))->count(),
                ],
            ]
        ));
    }

    public function store(Request $request, Registration $registration): RedirectResponse
    {
        $this->authorize('select', $registration);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(SelectionStatus::decidableOptions()))],
            'score' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],
            'rank' => ['nullable', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->selection->decide(
            $registration,
            SelectionStatus::from($validated['status']),
            $request->user(),
            isset($validated['score']) ? (float) $validated['score'] : null,
            $validated['rank'] ?? null,
            $validated['note'] ?? null,
        );

        return back()->with('success', sprintf(
            'Hasil seleksi %s disimpan sebagai draft. Hasil belum terlihat oleh pendaftar sampai dipublikasikan.',
            $registration->registration_number
        ));
    }

    public function publish(Registration $registration, Request $request): RedirectResponse
    {
        $this->authorize('publishResult', $registration);

        $this->selection->publish($registration, $request->user());

        return back()->with('success', sprintf(
            'Hasil seleksi %s dipublikasikan dan sudah dapat dilihat pendaftar.',
            $registration->registration_number
        ));
    }

    /**
     * Publish every decided-but-unpublished result matching the current filter.
     */
    public function publishBulk(Request $request): RedirectResponse
    {
        $this->authorize('viewAny', Registration::class);

        abort_unless($request->user()->managesPpdb(), 403);

        $request->validate([
            'confirm' => ['required', 'accepted'],
        ], [
            'confirm.accepted' => 'Centang konfirmasi sebelum mempublikasikan hasil secara massal.',
        ]);

        $registrations = $this->filteredRegistrations($request)
            ->where('registration_status', RegistrationStatus::Verified)
            ->whereHas('selectionResult', fn (Builder $r) => $r->whereNull('published_at'))
            ->with('selectionResult')
            ->get();

        $published = $this->selection->publishMany($registrations, $request->user());

        return back()->with(
            $published > 0 ? 'success' : 'info',
            $published > 0
                ? sprintf('%d hasil seleksi dipublikasikan.', $published)
                : 'Tidak ada hasil seleksi yang perlu dipublikasikan.'
        );
    }

    public function revoke(Registration $registration, Request $request): RedirectResponse
    {
        $this->authorize('select', $registration);

        $this->selection->revoke($registration, $request->user());

        return back()->with('success', 'Draft hasil seleksi dibatalkan.');
    }
}
