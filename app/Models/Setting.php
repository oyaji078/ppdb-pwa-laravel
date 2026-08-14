<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value', 'type', 'group', 'label'])]
class Setting extends Model
{
    /**
     * Casting happens in the repository because the target type is stored
     * per-row, not per-column.
     */
    public function typedValue(): string|bool|int|null
    {
        return match ($this->type) {
            'boolean' => (bool) $this->value,
            'integer' => $this->value === null ? null : (int) $this->value,
            default => $this->value,
        };
    }
}
