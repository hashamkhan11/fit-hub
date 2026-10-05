<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Platform\SubscriptionPlans;
use App\Models\Gym;
use App\Models\PlatformAdmin;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class SubscriptionPlansDeleteTest extends TestCase
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

    private function makePlan(): SubscriptionPlan
    {
        return SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter-'.uniqid(),
            'monthly_price' => 29,
        ]);
    }

    public function test_admin_can_delete_a_plan_not_assigned_to_any_gym(): void
    {
        $admin = $this->makeAdmin();
        $plan = $this->makePlan();

        Livewire::actingAs($admin, 'platform')
            ->test(SubscriptionPlans::class)
            ->call('delete', $plan->id)
            ->assertOk();

        $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
    }

    public function test_delete_is_blocked_when_a_gym_is_assigned_to_the_plan(): void
    {
        $admin = $this->makeAdmin();
        $plan = $this->makePlan();
        Gym::factory()->create(['subscription_plan_id' => $plan->id]);

        Livewire::actingAs($admin, 'platform')
            ->test(SubscriptionPlans::class)
            ->call('delete', $plan->id)
            ->assertOk();

        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }
}
