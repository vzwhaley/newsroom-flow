<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

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
        public ?string $briefing = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->newOnly ? 'What’s new on NewsroomFlow 📰' : 'Your NewsroomFlow is ready 📰',
        );
    }

    /**
     * RFC 8058 one-click unsubscribe headers — mailbox providers (Gmail,
     * Apple Mail…) surface their own "Unsubscribe" button from these.
     */
    public function headers(): Headers
    {
        return new Headers(text: [
            'List-Unsubscribe'      => '<'.$this->unsubscribeUrl().'>',
            'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
        ]);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-digest',
            with: [
                'user'           => $this->user,
                'topics'         => $this->topics,
                'newOnly'        => $this->newOnly,
                'briefing'       => $this->briefing,
                'url'            => route('dashboard'),
                'unsubscribeUrl' => $this->unsubscribeUrl(),
            ],
        );
    }

    /** Signed unsubscribe link — works without a login. */
    private function unsubscribeUrl(): string
    {
        return URL::signedRoute('digest.unsubscribe', ['user' => $this->user->id]);
    }
}
