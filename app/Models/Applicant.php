<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nisn', 'nik', 'family_card_number', 'full_name', 'gender', 'birth_place',
    'birth_date', 'religion', 'child_order', 'siblings_count', 'phone', 'email',
])]
class Applicant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'child_order' => 'integer',
            'siblings_count' => 'integer',
            'email_verified_at' => 'datetime',
        ];
    }

    /**
     * Deliberately not fillable: verification is set by following the emailed
     * link, never by submitting a form.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null && filled($this->email);
    }

    public function markEmailAsVerified(): void
    {
        $this->forceFill(['email_verified_at' => now()])->save();
    }

    /** @return HasOne<ApplicantAddress, $this> */
    public function address(): HasOne
    {
        return $this->hasOne(ApplicantAddress::class);
    }

    /** @return HasMany<ParentGuardian, $this> */
    public function parentGuardians(): HasMany
    {
        return $this->hasMany(ParentGuardian::class);
    }

    /** @return HasOne<PreviousSchool, $this> */
    public function previousSchool(): HasOne
    {
        return $this->hasOne(PreviousSchool::class);
    }

    /** @return HasMany<Registration, $this> */
    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class);
    }

    public function genderLabel(): string
    {
        return config('ppdb.reference.genders')[$this->gender] ?? '-';
    }

    /**
     * "Tempat, d MMMM YYYY" as printed on the receipt.
     */
    public function birthInfo(): string
    {
        if (! $this->birth_place && ! $this->birth_date) {
            return '-';
        }

        return trim(sprintf(
            '%s, %s',
            $this->birth_place ?? '-',
            $this->birth_date?->translatedFormat('d F Y') ?? '-'
        ), ', ');
    }

    public function father(): ?ParentGuardian
    {
        return $this->parentGuardians->firstWhere('relationship', 'father');
    }

    public function mother(): ?ParentGuardian
    {
        return $this->parentGuardians->firstWhere('relationship', 'mother');
    }

    public function guardian(): ?ParentGuardian
    {
        return $this->parentGuardians->firstWhere('relationship', 'guardian');
    }
}
