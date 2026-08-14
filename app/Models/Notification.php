<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_id', 'type', 'title', 'message', 'url', 'read_at'])]
class Notification extends Model
{
    public const UPDATED_AT = null;

    public const TYPE_DOCUMENT_REVISION = 'document_revision';

    public const TYPE_DOCUMENT_VERIFIED = 'document_verified';

    public const TYPE_VERIFICATION_COMPLETE = 'verification_complete';

    public const TYPE_SELECTION_PUBLISHED = 'selection_published';

    public const TYPE_REREGISTRATION = 'reregistration';

    public const TYPE_REGISTRATION_SUBMITTED = 'registration_submitted';

    public const TYPE_ACCESS_CODE_RESET = 'access_code_reset';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Registration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    /**
     * Lucide icon name matching this notification type.
     */
    public function icon(): string
    {
        return match ($this->type) {
            self::TYPE_DOCUMENT_REVISION => 'triangle-alert',
            self::TYPE_DOCUMENT_VERIFIED, self::TYPE_VERIFICATION_COMPLETE => 'circle-check',
            self::TYPE_SELECTION_PUBLISHED => 'award',
            self::TYPE_REREGISTRATION => 'clipboard-check',
            self::TYPE_ACCESS_CODE_RESET => 'key-round',
            default => 'bell',
        };
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
