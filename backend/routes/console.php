<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-class-reminders')->everyFiveMinutes();
Schedule::command('app:send-progress-reminders')->weekly();
Schedule::command('app:send-renewal-reminders')->daily();
Schedule::command('app:send-trial-ending-reminders')->daily();

// Nightly backup of the database and uploads, then delete old backups and check the new one.
Schedule::command('backup:run')->daily()->at('01:30')->withoutOverlapping();
Schedule::command('backup:clean')->daily()->at('02:15')->withoutOverlapping();
Schedule::command('backup:monitor')->daily()->at('02:30');
