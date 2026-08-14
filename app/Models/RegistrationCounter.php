<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['academic_year_id', 'registration_wave_id', 'last_sequence'])]
class RegistrationCounter extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_sequence' => 'integer',
        ];
    }
}
