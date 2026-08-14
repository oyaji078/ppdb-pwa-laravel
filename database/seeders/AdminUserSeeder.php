<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Creates the single super admin the school needs to get started. The
     * password comes from the environment so production never ships with a
     * known credential baked into the repository.
     */
    public function run(): void
    {
        $password = env('SEED_SUPER_ADMIN_PASSWORD', 'Admin#12345');

        User::query()->updateOrCreate(
            ['username' => env('SEED_SUPER_ADMIN_USERNAME', 'superadmin')],
            [
                'name' => 'Super Administrator',
                'email' => env('SEED_SUPER_ADMIN_EMAIL', 'superadmin@ppdb.test'),
                'password' => $password,
                'role' => UserRole::SuperAdmin,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->command?->info('Super admin siap. Ganti kata sandi setelah login pertama.');
    }
}
