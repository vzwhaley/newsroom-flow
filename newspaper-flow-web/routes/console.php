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
Schedule::command('newspaperflow:refresh --due')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Hourly NewsroomFlow refresh for users due this hour.');

/*
|--------------------------------------------------------------------------
| Daily global refresh — 4 AM Eastern, every topic + area, every user
|--------------------------------------------------------------------------
|
| A guaranteed once-a-day sweep that grabs the newest articles for ALL
| categories and local areas across ALL users, applying the same
| "keep 12, prepend new, drop oldest" rotation. This runs regardless of each
| user's per-user refresh hour above, so everyone always has a fresh top-12
| every morning. Idempotent: only genuinely new stories are added.
|
*/
Schedule::command('newspaperflow:refresh')
    ->timezone('America/New_York')
    ->dailyAt('04:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Daily 4 AM ET global refresh — newest top-12 for every topic and area, all users.');

/*
| Daily digest email — runs 5 minutes after the refresh so opted-in users get
| an email containing the morning's freshly-gathered stories.
*/
Schedule::command('newspaperflow:digest --due')
    ->hourlyAt(5)
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Hourly "Your NewsroomFlow is ready" digest for due, opted-in users.');

/*
| Daily push notification — runs a few minutes after the refresh, alongside the
| email digest, for users who opted into push and have a registered device.
*/
Schedule::command('newspaperflow:push --due')
    ->hourlyAt(7)
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Hourly "Your NewsroomFlow is ready" push for due, opted-in users.');

/*
|--------------------------------------------------------------------------
| Local-source discovery — daily safety-net sweep
|--------------------------------------------------------------------------
|
| The create-time job handles new areas, but this catches the stragglers:
| areas created while discovery was disabled, ones whose discovery failed, and
| learned records that have aged past their re-verify TTL (outlets rebrand). It
| only targets areas that actually need it (uncovered / stale), dispatches them
| to the queue, and caps the burst so a backlog drains gradually. A near-no-op
| on most days, and a clean no-op when discovery is disabled.
|
| Requires a queue worker in production to process the dispatched jobs.
|
*/
Schedule::command('newspaperflow:discover-sources --reverify --queue --limit=50')
    ->dailyAt('03:20')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Daily safety-net sweep to discover/re-verify local sources for areas that need it.');
