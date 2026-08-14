<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'academic_year_id', 'title', 'slug', 'content', 'audience',
    'published_at', 'expires_at', 'is_published', 'created_by',
])]
class Announcement extends Model
{
    use SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    /** @return BelongsTo<AcademicYear, $this> */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Published, past its publish date and not expired.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>=', now()));
    }

    /**
     * Announcements an applicant may see, given whether they were accepted.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeForApplicant(Builder $query, bool $isAccepted): Builder
    {
        $audiences = [
            AnnouncementAudience::Public->value,
            AnnouncementAudience::Applicants->value,
        ];

        if ($isAccepted) {
            $audiences[] = AnnouncementAudience::Accepted->value;
        }

        return $query->whereIn('audience', $audiences);
    }
}
