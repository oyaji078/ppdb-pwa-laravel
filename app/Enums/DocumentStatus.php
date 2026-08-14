<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Verified = 'verified';
    case RevisionRequired = 'revision_required';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Verified => 'Terverifikasi',
            self::RevisionRequired => 'Perlu Perbaikan',
            self::Rejected => 'Ditolak',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::Verified => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::RevisionRequired => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Rejected => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
    }

    /**
     * Whether the applicant is allowed to replace a document in this state.
     */
    public function isReplaceable(): bool
    {
        return $this !== self::Verified;
    }

    /**
     * Statuses an admin may assign while verifying.
     *
     * @return array<string, string>
     */
    public static function verificationOptions(): array
    {
        return [
            self::Verified->value => self::Verified->label(),
            self::RevisionRequired->value => self::RevisionRequired->label(),
            self::Rejected->value => self::Rejected->label(),
        ];
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
