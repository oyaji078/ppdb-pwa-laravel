<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['academic_year_id', 'name', 'code', 'start_at', 'end_at', 'quota', 'is_active'])]
class RegistrationWave extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'quota' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return HasMany<Registration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Wave is active and today falls inside its window.
     */
    public function isOpen(): bool
    {
        return $this->is_active
            && $this->start_at->isPast()
            && $this->end_at->isFuture();
    }

    /**
     * Submitted registrations count against the quota; drafts do not.
     */
    public function isQuotaFull(): bool
    {
        if ($this->quota === null) {
            return false;
        }

        return $this->registrations()
            ->whereNotNull('submitted_at')
            ->count() >= $this->quota;
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('start_at', '<=', now())
            ->where('end_at', '>=', now());
    }
}
