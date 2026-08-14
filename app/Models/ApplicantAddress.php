<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'applicant_id', 'province', 'regency', 'district', 'village', 'postal_code', 'address',
])]
class ApplicantAddress extends Model
{
    use HasFactory;

    /** @return BelongsTo<Applicant, $this> */
    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    /**
     * Human readable one-line address for receipts and detail pages.
     */
    public function fullAddress(): string
    {
        $parts = array_filter([
            $this->address,
            $this->village ? 'Desa/Kel. '.$this->village : null,
            $this->district ? 'Kec. '.$this->district : null,
            $this->regency,
            $this->province,
            $this->postal_code,
        ]);

        return $parts === [] ? '-' : implode(', ', $parts);
    }
}
