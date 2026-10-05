<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Billing;
use App\Livewire\Platform\GymCreate;
use App\Livewire\Platform\GymShow;
use App\Livewire\Platform\SubscriptionPlans;
use App\Models\Gym;
use App\Models\PlatformAdmin;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class BillingSmokeTest extends TestCase
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
            'name' => 'Growth',
            'slug' => 'growth-'.uniqid(),
            'monthly_price' => 49,
            'yearly_price' => 470,
            'is_active' => true,
        ]);
    }

    public function test_platform_admin_can_render_subscription_plans_page(): void
    {
        $admin = $this->makeAdmin();

        Livewire::actingAs($admin, 'platform')
            ->test(SubscriptionPlans::class)
            ->assertOk();
    }

    public function test_platform_admin_can_render_gym_create_page(): void
    {
        $admin = $this->makeAdmin();
        $this->makePlan();

        Livewire::actingAs($admin, 'platform')
            ->test(GymCreate::class)
            ->assertOk();
    }

    public function test_platform_admin_can_render_gym_show_page(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::factory()->create();

        Livewire::actingAs($admin, 'platform')
            ->test(GymShow::class, ['gym' => $gym])
            ->assertOk();
    }

    public function test_owner_can_render_billing_page(): void
    {
        $gym = Gym::factory()->create();
        $owner = User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);

        Livewire::actingAs($owner)
            ->test(Billing::class)
            ->assertOk();
    }

    public function test_owner_can_attempt_subscribe_without_synced_plan(): void
    {
        $gym = Gym::factory()->create();
        $owner = User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
        $plan = $this->makePlan();

        Livewire::actingAs($owner)
            ->test(Billing::class)
            ->call('subscribe', $plan->id, 'monthly')
            ->assertHasErrors('subscribe');
    }

    public function test_owner_with_unverified_email_cannot_subscribe(): void
    {
        $gym = Gym::factory()->create();
        $owner = User::factory()->unverified()->create(['gym_id' => $gym->id, 'role' => 'owner']);
        $plan = $this->makePlan();

        Livewire::actingAs($owner)
            ->test(Billing::class)
            ->call('subscribe', $plan->id, 'monthly')
            ->assertHasErrors('subscribe');
    }

    public function test_staff_cannot_render_billing_page(): void
    {
        $gym = Gym::factory()->create();
        $staff = User::factory()->create(['gym_id' => $gym->id, 'role' => 'staff']);

        $this->actingAs($staff)->get('/dashboard/billing')->assertForbidden();
    }
}
