<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'academic_year_id', 'registration_wave_id', 'title', 'description',
    'start_at', 'end_at', 'is_public', 'sort_order',
])]
class PpdbSchedule extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
        ];
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

    /**
     * One of: upcoming, ongoing, finished.
     */
    public function phase(): string
    {
        if ($this->start_at->isFuture()) {
            return 'upcoming';
        }

        return $this->end_at->isFuture() ? 'ongoing' : 'finished';
    }

    public function phaseLabel(): string
    {
        return match ($this->phase()) {
            'upcoming' => 'Akan Datang',
            'ongoing' => 'Sedang Berlangsung',
            default => 'Selesai',
        };
    }

    public function phaseBadge(): string
    {
        return match ($this->phase()) {
            'upcoming' => 'bg-slate-100 text-slate-700 ring-slate-200',
            'ongoing' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            default => 'bg-slate-100 text-slate-500 ring-slate-200',
        };
    }

    public function dateRange(): string
    {
        if ($this->start_at->isSameDay($this->end_at)) {
            return $this->start_at->translatedFormat('d F Y');
        }

        return $this->start_at->translatedFormat('d F Y').' - '.$this->end_at->translatedFormat('d F Y');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }
}
