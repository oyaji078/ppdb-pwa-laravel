<?php

namespace App\Enums;

enum UserRole: string
{
    case SuperAdmin = 'super_admin';
    case AdminPpdb = 'admin_ppdb';
    case Verifier = 'verifier';
    case Applicant = 'applicant';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::AdminPpdb => 'Admin PPDB',
            self::Verifier => 'Verifikator',
            self::Applicant => 'Calon Peserta Didik',
        };
    }

    /**
     * Everyone signs in through the same form, so the role decides where they
     * land and which menus exist.
     */
    public function isStaff(): bool
    {
        return $this !== self::Applicant;
    }

    /**
     * @return array<int, self>
     */
    public static function staffCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case) => $case->isStaff()));
    }

    /**
     * Roles a Super Admin may assign from the admin account screen. Applicant
     * accounts are created by registering, never by hand.
     *
     * @return array<string, string>
     */
    public static function staffOptions(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            if ($case->isStaff()) {
                $options[$case->value] = $case->label();
            }
        }

        return $options;
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
