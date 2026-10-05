<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    public function test_valid_submission_sends_mail_and_redirects_with_status(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Jane Owner',
            'email' => 'jane@example.com',
            'gym_name' => 'Iron Gym',
            'message' => 'What plans support multiple locations?',
        ]);

        $response->assertRedirect('/#contact');
        $response->assertSessionHas('status');

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) {
            return $mail->senderEmail === 'jane@example.com' && $mail->gymName === 'Iron Gym';
        });
    }

    public function test_missing_required_fields_are_rejected(): void
    {
        Mail::fake();

        $response = $this->post('/contact', ['name' => 'Jane']);

        $response->assertSessionHasErrors(['email', 'message']);
        Mail::assertNothingSent();
    }

    public function test_honeypot_field_silently_drops_the_submission(): void
    {
        Mail::fake();

        $response = $this->post('/contact', [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'message' => 'spam',
            'website' => 'http://spam.example',
        ]);

        $response->assertRedirect('/#contact');
        Mail::assertNothingSent();
    }
}
