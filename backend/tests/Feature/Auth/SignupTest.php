<?php

namespace Tests\Feature\Auth;

use App\Models\Gym;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SignupTest extends TestCase
{
    use RefreshDatabase;

    private function makePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter-'.uniqid(),
            'monthly_price' => 29,
            'yearly_price' => 290,
            'is_active' => true,
        ]);
    }

    public function test_signup_creates_a_gym_and_owner_and_logs_them_in(): void
    {
        Mail::fake();
        Notification::fake();
        $plan = $this->makePlan();

        $response = $this->post('/start-trial', [
            'gym_name' => 'Iron Peak Fitness',
            'owner_name' => 'Jamie Rivera',
            'email' => 'jamie@ironpeak.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subscription_plan_id' => $plan->id,
        ]);

        $response->assertRedirect('/dashboard');

        $gym = Gym::where('email', 'jamie@ironpeak.test')->first();
        $this->assertNotNull($gym);
        $this->assertSame('Iron Peak Fitness', $gym->name);
        $this->assertTrue($gym->isOnTrial());
        $this->assertSame('USD', $gym->currency_code);

        $owner = User::where('email', 'jamie@ironpeak.test')->first();
        $this->assertNotNull($owner);
        $this->assertSame('owner', $owner->role);
        $this->assertSame($gym->id, $owner->gym_id);

        $this->assertAuthenticatedAs($owner);
        $this->assertFalse($owner->hasVerifiedEmail());
        Notification::assertSentTo($owner, VerifyEmail::class);
    }

    public function test_signup_rejects_a_duplicate_email(): void
    {
        $plan = $this->makePlan();
        $gym = Gym::factory()->create(['email' => 'taken@ironpeak.test']);
        User::factory()->create(['gym_id' => $gym->id, 'email' => 'taken@ironpeak.test']);

        $response = $this->from('/start-trial')->post('/start-trial', [
            'gym_name' => 'Another Gym',
            'owner_name' => 'Someone Else',
            'email' => 'taken@ironpeak.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subscription_plan_id' => $plan->id,
        ]);

        $response->assertRedirect('/start-trial');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_signup_honeypot_silently_blocks_bots(): void
    {
        $plan = $this->makePlan();

        $response = $this->post('/start-trial', [
            'gym_name' => 'Bot Gym',
            'owner_name' => 'Bot',
            'email' => 'bot@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'subscription_plan_id' => $plan->id,
            'website' => 'http://spam.example',
        ]);

        $response->assertRedirect('/start-trial');
        $this->assertGuest();
        $this->assertNull(Gym::where('email', 'bot@example.test')->first());
    }
}
