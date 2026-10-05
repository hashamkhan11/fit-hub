<?php

namespace App\Mail;

use App\Models\Gym;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrialEndingReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Gym $gym,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Your FitHub trial for {$this->gym->name} ends soon",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.trial-ending-reminder',
        );
    }
}
