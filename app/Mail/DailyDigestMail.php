<?php
// app/Mail/DailyDigestMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $summary,
        public bool $healthy,
        public int $incidents,
    ) {}

    public function envelope(): Envelope
    {
        $icon = $this->healthy ? '✅' : '⚠️';

        return new Envelope(
            subject: "{$icon} College Portal daily status — {$this->incidents} incident(s)",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.daily-digest');
    }
}
