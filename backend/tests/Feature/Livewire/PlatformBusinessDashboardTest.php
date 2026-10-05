<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Platform\Business;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformBusinessDashboardTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): PlatformAdmin
    {
        return PlatformAdmin::create([
            'name' => 'Test Admin',
            'email' => 'admin-'.uniqid().'@ranksol.test',
            'password' => Hash::make('password'),
        ]);
    }

    public function test_it_computes_mrr_conversion_and_plan_mix(): void
    {
        $admin = $this->makeAdmin();

        Gym::factory()->create([
            'subscription_status' => 'active',
            'plan_name' => 'Growth',
            'plan_price' => 48,
            'billing_cycle' => 'monthly',
        ]);
        Gym::factory()->create([
            'subscription_status' => 'active',
            'plan_name' => 'Unlimited',
            'plan_price' => 1176,
            'billing_cycle' => 'yearly',
        ]);
        Gym::factory()->create(['subscription_status' => 'trial']);
        Gym::factory()->create(['subscription_status' => 'suspended']);

        $response = Livewire::actingAs($admin, 'platform')->test(Business::class);

        $response->assertOk();
        // (48 monthly) + (1176 / 12 yearly) = 48 + 98 = 146
        $response->assertViewHas('mrr', 146.0);
        // 2 active + 1 suspended have decided; 2 of 3 is active => 67%
        $response->assertViewHas('conversion', 67);
        $response->assertViewHas('activeCount', 2);
        $response->assertViewHas('trialCount', 1);
        $response->assertViewHas('suspendedCount', 1);
    }

    public function test_churn_counts_recent_cancellation_activity_logs(): void
    {
        $admin = $this->makeAdmin();

        $gym = Gym::factory()->create(['subscription_status' => 'active', 'plan_price' => 29]);

        PlatformActivityLog::create([
            'platform_admin_id' => null,
            'gym_id' => $gym->id,
            'action' => 'gym.subscription_updated',
            'description' => "Stripe subscription for {$gym->name} is now 'canceled'.",
            'created_at' => now()->subDays(5),
        ]);

        $response = Livewire::actingAs($admin, 'platform')->test(Business::class);

        $response->assertOk();
        $response->assertViewHas('churn', fn ($churn) => $churn['churned'] === 1);
    }

    public function test_guest_cannot_access_the_business_dashboard(): void
    {
        $this->get('/ranksol/business')->assertRedirect('/login');
    }
}
