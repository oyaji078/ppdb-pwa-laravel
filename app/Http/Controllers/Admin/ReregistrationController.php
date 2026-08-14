<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use App\Http\Controllers\Admin\Concerns\FiltersRegistrations;
use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Services\ReregistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReregistrationController extends Controller
{
    use FiltersRegistrations;

    public function __construct(private readonly ReregistrationService $reregistrations) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Registration::class);

        // Only accepted applicants whose result has actually been published.
        $base = fn () => $this->filteredRegistrations($request)
            ->where('selection_status', SelectionStatus::Accepted)
            ->whereHas('selectionResult', fn (Builder $r) => $r->whereNotNull('published_at'));

        $registrations = $base()
            ->with('reregistration.verifier')
            ->orderBy('registration_number')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return view('admin.reregistration.index', array_merge(
            $this->filterOptions($request),
            [
                'registrations' => $registrations,
                'statusOptions' => ReregistrationStatus::manageableOptions(),
                'reregistrationStatuses' => ReregistrationStatus::options(),
                'summary' => [
                    'accepted' => $base()->count(),
                    'pending' => $base()->where('reregistration_status', ReregistrationStatus::Pending)->count(),
                    'completed' => $base()->where('reregistration_status', ReregistrationStatus::Completed)->count(),
                    'withdrawn' => $base()->whereIn('reregistration_status', [
                        ReregistrationStatus::Withdrawn->value,
                        ReregistrationStatus::Expired->value,
                    ])->count(),
                ],
            ]
        ));
    }

    public function update(Request $request, Registration $registration): RedirectResponse
    {
        $this->authorize('manageReregistration', $registration);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(ReregistrationStatus::manageableOptions()))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->reregistrations->updateStatus(
            $registration,
            ReregistrationStatus::from($validated['status']),
            $request->user(),
            $validated['notes'] ?? null,
        );

        return back()->with('success', sprintf(
            'Status daftar ulang %s diperbarui.',
            $registration->registration_number
        ));
    }
}
