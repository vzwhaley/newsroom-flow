<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigest extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<int, array{name:string, articles:array<int, \App\Models\Article>}>  $topics
     */
    public function __construct(
        public User $user,
        public array $topics,
        public bool $newOnly = false,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->newOnly ? 'What’s new on NewsFlow 📰' : 'Your NewsFlow is ready 📰',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-digest',
            with: [
                'user'    => $this->user,
                'topics'  => $this->topics,
                'newOnly' => $this->newOnly,
                'url'     => route('dashboard'),
            ],
        );
    }
}
