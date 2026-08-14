<?php

namespace App\Models;

use App\Enums\DocumentStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['registration_document_id', 'admin_id', 'old_status', 'new_status', 'note'])]
class DocumentVerificationLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_status' => DocumentStatus::class,
            'new_status' => DocumentStatus::class,
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<RegistrationDocument, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(RegistrationDocument::class, 'registration_document_id');
    }

    /** @return BelongsTo<User, $this> */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
