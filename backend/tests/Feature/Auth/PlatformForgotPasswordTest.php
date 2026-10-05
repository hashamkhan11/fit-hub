<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetLinkMail;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PlatformForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(string $email = 'admin@ranksol.test'): PlatformAdmin
    {
        return PlatformAdmin::create([
            'name' => 'Test Admin',
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
        $this->makeAdmin();

        $response = $this->post('/ranksol/forgot-password', ['email' => 'admin@ranksol.test']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertSent(PasswordResetLinkMail::class);
    }

    public function test_request_gives_the_same_generic_response_for_an_unknown_email(): void
    {
        Mail::fake();

        $response = $this->post('/ranksol/forgot-password', ['email' => 'nobody@ranksol.test']);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Mail::assertNothingSent();
    }

    public function test_a_valid_token_resets_the_password_and_allows_login(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();

        $this->post('/ranksol/forgot-password', ['email' => $admin->email]);

        $sentUrl = null;
        Mail::assertSent(PasswordResetLinkMail::class, function (PasswordResetLinkMail $mail) use (&$sentUrl) {
            $sentUrl = $mail->resetUrl;

            return true;
        });

        $token = $this->extractToken($sentUrl);

        $response = $this->post('/ranksol/reset-password', [
            'token' => $token,
            'email' => $admin->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertRedirect(route('platform.login'));
        Mail::assertSent(PasswordChangedMail::class);

        $this->assertTrue(Hash::check('new-password123', $admin->fresh()->password));

        $this->post('/ranksol/login', [
            'email' => $admin->email,
            'password' => 'new-password123',
        ]);
        $this->assertAuthenticatedAs($admin->fresh(), 'platform');
    }

    public function test_an_invalid_token_is_rejected(): void
    {
        Mail::fake();
        $admin = $this->makeAdmin();

        $response = $this->post('/ranksol/reset-password', [
            'token' => 'not-a-real-token',
            'email' => $admin->email,
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(Hash::check('old-password123', $admin->fresh()->password));
    }

    public function test_reset_requests_are_rate_limited(): void
    {
        Mail::fake();
        $this->makeAdmin();

        for ($i = 0; $i < 3; $i++) {
            $this->post('/ranksol/forgot-password', ['email' => 'admin@ranksol.test']);
        }

        $response = $this->post('/ranksol/forgot-password', ['email' => 'admin@ranksol.test']);

        $response->assertStatus(429);
    }
}
