<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Article refresh — hourly, honoring each user's chosen hour + timezone
|--------------------------------------------------------------------------
|
| Runs every hour and refreshes only the users whose chosen refresh hour
| matches the current hour in their own timezone (default 6 AM). This lets a
| user in any timezone get fresh stories at the time they picked, applying the
| "keep 12, prepend new, drop oldest" rule.
|
| Requires the system cron to invoke `php artisan schedule:run` every minute
| (on Windows, a Task Scheduler entry). See README for setup.
|
*/
Schedule::command('newsflow:refresh --due')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Hourly NewsFlow refresh for users due this hour.');

/*
| Daily digest email — runs 5 minutes after the refresh so opted-in users get
| an email containing the morning's freshly-gathered stories.
*/
Schedule::command('newsflow:digest --due')
    ->hourlyAt(5)
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Hourly "Your NewsFlow is ready" digest for due, opted-in users.');

/*
| Daily push notification — runs a few minutes after the refresh, alongside the
| email digest, for users who opted into push and have a registered device.
*/
Schedule::command('newsflow:push --due')
    ->hourlyAt(7)
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Hourly "Your NewsFlow is ready" push for due, opted-in users.');
