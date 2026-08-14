<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Base seed, safe for production. Sample applicants live in DemoSeeder and are
 * never run from here.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            AdminUserSeeder::class,
            PpdbConfigurationSeeder::class,
            CmsSeeder::class,
        ]);
    }
}
