<?php

namespace App\Enums;

enum SelectionStatus: string
{
    case Pending = 'pending';
    case Reserve = 'reserve';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Belum Diseleksi',
            self::Reserve => 'Cadangan',
            self::Accepted => 'Diterima',
            self::Rejected => 'Tidak Diterima',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Pending => 'bg-slate-100 text-slate-700 ring-slate-200',
            self::Reserve => 'bg-amber-50 text-amber-800 ring-amber-200',
            self::Accepted => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Rejected => 'bg-rose-50 text-rose-700 ring-rose-200',
        };
    }

    /**
     * Statuses an admin may assign as a selection decision.
     *
     * @return array<int, self>
     */
    public static function decidable(): array
    {
        return [self::Accepted, self::Reserve, self::Rejected];
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

    /**
     * @return array<string, string>
     */
    public static function decidableOptions(): array
    {
        $options = [];

        foreach (self::decidable() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
