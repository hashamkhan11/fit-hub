<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetLinkMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $resetUrl,
        public string $panelLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Reset your {$this->panelLabel} password",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-reset-link',
        );
    }
}
