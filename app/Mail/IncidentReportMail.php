<?php
// app/Mail/IncidentReportMail.php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public int $prNumber,
        public string $summary,
        public string $prUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "✅ College Portal: an issue was detected and fixed (#{$this->prNumber})",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.incident-report');
    }
}
