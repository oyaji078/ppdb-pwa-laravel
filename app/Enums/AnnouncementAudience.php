<?php

namespace App\Enums;

enum AnnouncementAudience: string
{
    case Public = 'public';
    case Applicants = 'applicants';
    case Accepted = 'accepted';

    public function label(): string
    {
        return match ($this) {
            self::Public => 'Umum (Publik)',
            self::Applicants => 'Semua Pendaftar',
            self::Accepted => 'Pendaftar Diterima',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Public => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::Applicants => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
            self::Accepted => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        };
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
