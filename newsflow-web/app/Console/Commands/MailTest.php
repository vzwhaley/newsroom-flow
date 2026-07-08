<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Sends a quick plain-text email to verify SMTP credentials are working.
 *
 *   php artisan newsflow:mail-test you@example.com
 */
class MailTest extends Command
{
    protected $signature = 'newsflow:mail-test {email : Where to send the test message}';

    protected $description = 'Send a test email to verify mail (SMTP) configuration.';

    public function handle(): int
    {
        $to = $this->argument('email');

        $this->info("Sending test email to {$to} via ".config('mail.default').'…');

        try {
            Mail::raw(
                "This is a NewsroomFlow SMTP test. If you received this, outbound email is working.\n\n"
                .'Sent '.now()->toDayDateTimeString().' from '.config('app.name').'.',
                function ($message) use ($to) {
                    $message->to($to)->subject('NewsroomFlow — SMTP test ✅');
                }
            );

            $this->info('Sent successfully.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
