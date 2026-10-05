<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordChangedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $panelLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your {$this->panelLabel} password was changed",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-changed',
        );
    }
}
