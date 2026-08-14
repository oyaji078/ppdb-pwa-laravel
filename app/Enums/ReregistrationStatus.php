<?php

namespace App\Enums;

enum ReregistrationStatus: string
{
    case NotRequired = 'not_required';
    case Pending = 'pending';
    case Completed = 'completed';
    case Expired = 'expired';
    case Withdrawn = 'withdrawn';

    public function label(): string
    {
        return match ($this) {
            self::NotRequired => 'Tidak Diperlukan',
            self::Pending => 'Menunggu Daftar Ulang',
            self::Completed => 'Daftar Ulang Selesai',
            self::Expired => 'Kedaluwarsa',
            self::Withdrawn => 'Mengundurkan Diri',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::NotRequired => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Pending => 'bg-blue-50 text-blue-700 ring-blue-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Expired => 'bg-rose-50 text-rose-700 ring-rose-200',
            self::Withdrawn => 'bg-slate-200 text-slate-700 ring-slate-300',
        };
    }

    /**
     * Statuses an admin may set on an active reregistration record.
     *
     * @return array<string, string>
     */
    public static function manageableOptions(): array
    {
        return [
            self::Pending->value => self::Pending->label(),
            self::Completed->value => self::Completed->label(),
            self::Withdrawn->value => self::Withdrawn->label(),
            self::Expired->value => self::Expired->label(),
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
