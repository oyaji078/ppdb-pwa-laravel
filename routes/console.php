<?php

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Requires a cron entry running `php artisan schedule:run` every minute.
|
*/

// Clear abandoned wizard drafts so their NISN and uploaded files are released.
Schedule::command('ppdb:prune-drafts --days=30')->dailyAt('02:00');
