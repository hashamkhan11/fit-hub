<?php

namespace App\Mail;

use App\Models\Gym;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Gym $gym,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Payment failed for {$this->gym->name} — action needed",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.payment-failed',
        );
    }
}
