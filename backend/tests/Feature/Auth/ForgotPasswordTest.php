<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetLinkMail;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email = 'owner@ironpeak.test'): User
    {
        $gym = Gym::factory()->create();

        return User::factory()->create([
            'gym_id' => $gym->id,
            'email' => $email,
            'password' => Hash::make('old-password123'),
        ]);
    }

    private function extractToken(string $resetUrl): string
    {
        $path = parse_url($resetUrl, PHP_URL_PATH);

        return basename($path);
    }

    public function test_request_gives_a_generic_response_for_an_existing_email(): void
    {
        Mail::fake();
        $this->makeUser();

        $response = $this->post('/forgot-password', ['email' => 'owner@ironpeak.test']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertSent(PasswordResetLinkMail::class);
    }

    public function test_request_gives_the_same_generic_response_for_an_unknown_email(): void
    {
        Mail::fake();

        $response = $this->post('/forgot-password', ['email' => 'nobody@ironpeak.test']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_a_valid_token_resets_the_password_and_allows_login(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $this->post('/forgot-password', ['email' => $user->email]);

        $sentUrl = null;
        Mail::assertSent(PasswordResetLinkMail::class, function (PasswordResetLinkMail $mail) use (&$sentUrl) {
            $sentUrl = $mail->resetUrl;

            return true;
        });

        $token = $this->extractToken($sentUrl);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('login'));
        Mail::assertSent(PasswordChangedMail::class);

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));

        $login = $this->post('/login', [
            'email' => $user->email,
            'password' => 'new-password123',
        ]);
        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        Mail::fake();
        $user = $this->makeUser();

        $response = $this->from('/reset-password/bad-token')->post('/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $user->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password123', $user->fresh()->password));
    }

    public function test_reset_requests_are_rate_limited(): void
    {
        Mail::fake();
        $this->makeUser();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/forgot-password', ['email' => 'owner@ironpeak.test']);
        }

        $response = $this->post('/forgot-password', ['email' => 'owner@ironpeak.test']);

        $response->assertStatus(429);
    }
}
