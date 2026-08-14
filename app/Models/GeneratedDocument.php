<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'registration_id', 'type', 'document_number', 'storage_path', 'checksum', 'generated_at',
])]
class GeneratedDocument extends Model
{
    public const TYPE_RECEIPT = 'registration_receipt';

    public const TYPE_ACCESS_CODE_RECOVERY = 'access_code_recovery';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Registration, $this> */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }
}
