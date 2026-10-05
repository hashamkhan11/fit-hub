<?php

namespace Tests\Feature\Auth;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unverified_owner_can_verify_via_signed_link(): void
    {
        Event::fake();

        $gym = Gym::factory()->create();
        $owner = User::factory()->unverified()->create(['gym_id' => $gym->id, 'role' => 'owner']);

        $url = URL::signedRoute('verification.verify', [
            'id' => $owner->id,
            'hash' => sha1($owner->email),
        ]);

        $response = $this->actingAs($owner)->get($url);

        $response->assertRedirect('/dashboard');
        $this->assertTrue($owner->fresh()->hasVerifiedEmail());
        Event::assertDispatched(Verified::class);
    }

    public function test_verification_link_with_bad_hash_is_rejected(): void
    {
        $gym = Gym::factory()->create();
        $owner = User::factory()->unverified()->create(['gym_id' => $gym->id, 'role' => 'owner']);

        $url = URL::signedRoute('verification.verify', [
            'id' => $owner->id,
            'hash' => sha1('someone-else@example.com'),
        ]);

        $this->actingAs($owner)->get($url)->assertForbidden();
        $this->assertFalse($owner->fresh()->hasVerifiedEmail());
    }

    public function test_owner_can_resend_verification_email(): void
    {
        Notification::fake();

        $gym = Gym::factory()->create();
        $owner = User::factory()->unverified()->create(['gym_id' => $gym->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->post('/email/verification-notification')
            ->assertRedirect();

        Notification::assertSentTo($owner, VerifyEmail::class);
    }

    public function test_already_verified_owner_resend_redirects_to_dashboard(): void
    {
        $gym = Gym::factory()->create();
        $owner = User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);

        $this->actingAs($owner)
            ->post('/email/verification-notification')
            ->assertRedirect('/dashboard');
    }
}
