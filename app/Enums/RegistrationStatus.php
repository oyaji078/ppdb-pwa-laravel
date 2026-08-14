<?php

namespace App\Enums;

enum RegistrationStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case RevisionRequired = 'revision_required';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Menunggu Verifikasi',
            self::UnderReview => 'Sedang Diverifikasi',
            self::RevisionRequired => 'Perlu Perbaikan',
            self::Verified => 'Terverifikasi',
        };
    }

    /**
     * Tailwind badge classes for this status.
     */
    public function badge(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Submitted => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::UnderReview => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            self::RevisionRequired => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Verified => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        };
    }

    /**
     * Statuses this status is allowed to move to.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Submitted],
            self::Submitted => [self::UnderReview, self::RevisionRequired, self::Verified],
            self::UnderReview => [self::RevisionRequired, self::Verified],
            self::RevisionRequired => [self::Submitted, self::UnderReview],
            self::Verified => [self::UnderReview],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    /**
     * Registration has been submitted by the applicant (no longer editable as a draft).
     */
    public function isSubmitted(): bool
    {
        return $this !== self::Draft;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
