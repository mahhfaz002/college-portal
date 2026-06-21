<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// --- Auto-heal pipeline: daily heartbeat (appended by the autoheal bundle) ---
// Laravel Cloud runs the scheduler once you enable a scheduled task
// (Cloud dashboard -> Environment -> Scheduler), which invokes `schedule:run`.
// Fully-qualified name is used on purpose so this block is safe to append at the
// bottom of an existing routes/console.php without needing a top-of-file `use`.
\Illuminate\Support\Facades\Schedule::command('report:daily')
    ->dailyAt('07:00')
    ->timezone('Africa/Lagos');
