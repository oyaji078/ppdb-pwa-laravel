<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'applicant_id', 'relationship', 'name', 'nik', 'birth_year', 'education',
    'occupation', 'monthly_income', 'phone', 'address', 'is_alive',
])]
class ParentGuardian extends Model
{
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_year' => 'integer',
            'is_alive' => 'boolean',
        ];
    }

    /** @return BelongsTo<Applicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function relationshipLabel(): string
    {
        return config('ppdb.reference.relationships')[$this->relationship] ?? $this->relationship;
    }
}
