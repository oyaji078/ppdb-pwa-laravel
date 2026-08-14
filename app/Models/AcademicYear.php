<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'start_year', 'end_year', 'is_active', 'registration_open'])]
class AcademicYear extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_year' => 'integer',
            'end_year' => 'integer',
            'is_active' => 'boolean',
            'registration_open' => 'boolean',
        ];
    }

    /** @return HasMany<RegistrationWave, $this> */
    public function waves(): HasMany
    {
        return $this->hasMany(RegistrationWave::class);
    }

    /** @return HasMany<AdmissionTrack, $this> */
    public function admissionTracks(): HasMany
    {
        return $this->hasMany(AdmissionTrack::class);
    }

    /** @return HasMany<Program, $this> */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /** @return HasMany<DocumentType, $this> */
    public function documentTypes(): HasMany
    {
        return $this->hasMany(DocumentType::class);
    }

    /** @return HasMany<PpdbSchedule, $this> */
    public function schedules(): HasMany
    {
        return $this->hasMany(PpdbSchedule::class);
    }

    /** @return HasMany<Registration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /** @return HasMany<Announcement, $this> */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    /**
     * Last two digits of the intake year, used as the YY part of a registration number.
     */
    public function yearCode(): string
    {
        return str_pad((string) ($this->start_year % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function current(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }
}
