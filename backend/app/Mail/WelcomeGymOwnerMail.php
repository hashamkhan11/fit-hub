<?php

namespace App\Mail;

use App\Models\Gym;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeGymOwnerMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Gym $gym,
        public string $ownerEmail,
        public string $temporaryPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Welcome to FitHub — {$this->gym->name} is ready",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.welcome-gym-owner',
        );
    }
}
