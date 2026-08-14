<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'academic_year_id', 'name', 'code', 'description', 'is_required', 'requires_verification',
    'allowed_extensions', 'max_size_kb', 'sort_order', 'is_active',
])]
class DocumentType extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'requires_verification' => 'boolean',
            'allowed_extensions' => 'array',
            'max_size_kb' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsToMany<AdmissionTrack, $this> */
    public function admissionTracks(): BelongsToMany
    {
        return $this->belongsToMany(AdmissionTrack::class, 'admission_track_document_requirements')
            ->withPivot('is_required')
            ->withTimestamps();
    }

    /** @return HasMany<RegistrationDocument, $this> */
    public function registrationDocuments(): HasMany
    {
        return $this->hasMany(RegistrationDocument::class);
    }

    /**
     * Extensions this type accepts, falling back to the global default.
     *
     * @return array<int, string>
     */
    public function extensions(): array
    {
        $extensions = $this->allowed_extensions;

        if (! is_array($extensions) || $extensions === []) {
            return config('ppdb.uploads.allowed_extensions');
        }

        return array_values(array_map('strtolower', $extensions));
    }

    /**
     * MIME types matching this type's allowed extensions.
     *
     * @return array<int, string>
     */
    public function mimeTypes(): array
    {
        $map = config('ppdb.uploads.mime_map');
        $mimes = [];

        foreach ($this->extensions() as $extension) {
            foreach ($map[$extension] ?? [] as $mime) {
                $mimes[] = $mime;
            }
        }

        return array_values(array_unique($mimes));
    }

    /**
     * Effective size cap in KB, never above the application-wide ceiling.
     */
    public function maxSizeKb(): int
    {
        return min(
            $this->max_size_kb ?: config('ppdb.uploads.default_max_size_kb'),
            config('ppdb.uploads.absolute_max_size_kb')
        );
    }

    public function humanMaxSize(): string
    {
        $kb = $this->maxSizeKb();

        return $kb >= 1024
            ? rtrim(rtrim(number_format($kb / 1024, 1, ',', '.'), '0'), ',').' MB'
            : $kb.' KB';
    }

    public function extensionLabel(): string
    {
        return strtoupper(implode(', ', $this->extensions()));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
