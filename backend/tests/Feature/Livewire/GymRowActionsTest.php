<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Platform\Gyms;
use App\Models\Gym;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GymRowActionsTest extends TestCase
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

    public function test_platform_admin_can_render_gyms_list_page(): void
    {
        $admin = $this->makeAdmin();
        Gym::factory()->create(['subscription_status' => 'trial']);

        Livewire::actingAs($admin, 'platform')
            ->test(Gyms::class)
            ->assertOk()
            ->assertSee('Trial');
    }

    public function test_row_action_suspends_a_gym(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::factory()->create(['subscription_status' => 'active']);

        Livewire::actingAs($admin, 'platform')
            ->test(Gyms::class)
            ->call('suspend', $gym->id, 'Non-payment')
            ->assertOk();

        $gym->refresh();
        $this->assertTrue($gym->isSuspended());
        $this->assertSame('Non-payment', $gym->suspended_reason);
    }

    public function test_row_action_ignores_suspend_with_no_reason(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::factory()->create(['subscription_status' => 'active']);

        Livewire::actingAs($admin, 'platform')
            ->test(Gyms::class)
            ->call('suspend', $gym->id, null)
            ->assertOk();

        $this->assertFalse($gym->refresh()->isSuspended());
    }

    public function test_row_action_reactivates_a_suspended_gym(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::factory()->create([
            'subscription_status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => 'Non-payment',
        ]);

        Livewire::actingAs($admin, 'platform')
            ->test(Gyms::class)
            ->call('activate', $gym->id)
            ->assertOk();

        $gym->refresh();
        $this->assertFalse($gym->isSuspended());
        $this->assertNull($gym->suspended_reason);
    }

    public function test_row_action_permanently_deletes_a_gym(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::factory()->create();

        Livewire::actingAs($admin, 'platform')
            ->test(Gyms::class)
            ->call('deleteGym', $gym->id)
            ->assertOk();

        $this->assertDatabaseMissing('gyms', ['id' => $gym->id]);
        $this->assertDatabaseHas('platform_activity_logs', [
            'action' => 'gym.deleted',
            'gym_id' => null,
        ]);
    }
}
