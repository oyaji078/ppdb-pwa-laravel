<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use App\Enums\RegistrationStatus;
use App\Enums\ReregistrationStatus;
use App\Enums\SelectionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'applicant_id', 'user_id', 'academic_year_id', 'registration_wave_id', 'admission_track_id',
    'program_id', 'registration_status', 'current_step', 'statement_agreed',
])]
class Registration extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registration_status' => RegistrationStatus::class,
            'selection_status' => SelectionStatus::class,
            'reregistration_status' => ReregistrationStatus::class,
            'statement_agreed' => 'boolean',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'selected_at' => 'datetime',
            'reregistered_at' => 'datetime',
            'session_version' => 'integer',
        ];
    }

    /** @return BelongsTo<Applicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * The account this applicant signs in with. Applicants are ordinary users
     * with the applicant role, so one login can own registrations across years.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<RegistrationWave, $this> */
    public function wave(): BelongsTo
    {
        return $this->belongsTo(RegistrationWave::class, 'registration_wave_id');
    }

    /** @return BelongsTo<AdmissionTrack, $this> */
    public function admissionTrack(): BelongsTo
    {
        return $this->belongsTo(AdmissionTrack::class);
    }

    /** @return BelongsTo<Program, $this> */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /** @return BelongsTo<User, $this> */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /** @return HasMany<RegistrationDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class);
    }

    /** @return HasOne<SelectionResult, $this> */
    public function selectionResult(): HasOne
    {
        return $this->hasOne(SelectionResult::class);
    }

    /** @return HasOne<Reregistration, $this> */
    public function reregistration(): HasOne
    {
        return $this->hasOne(Reregistration::class);
    }

    /** @return HasMany<Notification, $this> */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /** @return HasMany<GeneratedDocument, $this> */
    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function isDraft(): bool
    {
        return $this->registration_status === RegistrationStatus::Draft;
    }

    public function isVerified(): bool
    {
        return $this->registration_status === RegistrationStatus::Verified;
    }

    public function isAccepted(): bool
    {
        return $this->selection_status === SelectionStatus::Accepted;
    }

    /**
     * A selection outcome is only visible to the applicant once published.
     */
    public function hasPublishedResult(): bool
    {
        return $this->selectionResult !== null
            && $this->selectionResult->published_at !== null;
    }

    /**
     * Document types this registration must supply, resolved from its track.
     *
     * @return Collection<int, DocumentType>
     */
    public function requiredDocumentTypes(): Collection
    {
        return $this->admissionTrack
            ->documentTypes()
            ->where('document_types.is_active', true)
            ->wherePivot('is_required', true)
            ->get();
    }

    /**
     * Every document type offered by the track, required or optional.
     *
     * @return Collection<int, DocumentType>
     */
    public function applicableDocumentTypes(): Collection
    {
        return $this->admissionTrack
            ->documentTypes()
            ->where('document_types.is_active', true)
            ->get();
    }

    /**
     * Required documents that have not been uploaded at all.
     *
     * @return Collection<int, DocumentType>
     */
    public function missingRequiredDocuments(): Collection
    {
        $uploadedTypeIds = $this->documents->pluck('document_type_id')->all();

        return $this->requiredDocumentTypes()
            ->reject(fn (DocumentType $type) => in_array($type->id, $uploadedTypeIds, true))
            ->values();
    }

    /**
     * Required documents still waiting on a "verified" decision.
     *
     * @return Collection<int, DocumentType>
     */
    public function unverifiedRequiredDocuments(): Collection
    {
        $verifiedTypeIds = $this->documents
            ->where('verification_status', DocumentStatus::Verified)
            ->pluck('document_type_id')
            ->all();

        return $this->requiredDocumentTypes()
            ->reject(fn (DocumentType $type) => in_array($type->id, $verifiedTypeIds, true))
            ->values();
    }

    /**
     * Ratio of uploaded required documents, for the applicant dashboard card.
     *
     * @return array{uploaded: int, total: int, percent: int}
     */
    public function documentProgress(): array
    {
        $required = $this->requiredDocumentTypes();
        $total = $required->count();

        if ($total === 0) {
            return ['uploaded' => 0, 'total' => 0, 'percent' => 100];
        }

        $uploadedTypeIds = $this->documents->pluck('document_type_id')->all();
        $uploaded = $required
            ->filter(fn (DocumentType $type) => in_array($type->id, $uploadedTypeIds, true))
            ->count();

        return [
            'uploaded' => $uploaded,
            'total' => $total,
            'percent' => (int) round($uploaded / $total * 100),
        ];
    }

    /**
     * Completeness of the applicant's own data, for the dashboard card.
     *
     * @return array{filled: int, total: int, percent: int}
     */
    public function biodataProgress(): array
    {
        $applicant = $this->applicant;

        $checks = [
            filled($applicant->nisn),
            filled($applicant->nik),
            filled($applicant->full_name),
            filled($applicant->gender),
            filled($applicant->birth_place),
            filled($applicant->birth_date),
            filled($applicant->phone),
            $applicant->address !== null && filled($applicant->address->address),
            $applicant->parentGuardians->isNotEmpty(),
            $applicant->previousSchool !== null,
            $this->program_id !== null,
        ];

        $total = count($checks);
        $filled = count(array_filter($checks));

        return [
            'filled' => $filled,
            'total' => $total,
            'percent' => (int) round($filled / $total * 100),
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeSubmitted(Builder $query): Builder
    {
        return $query->whereNotNull('submitted_at');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForYear(Builder $query, ?int $academicYearId): Builder
    {
        return $query->when($academicYearId, fn (Builder $q) => $q->where('academic_year_id', $academicYearId));
    }
}
