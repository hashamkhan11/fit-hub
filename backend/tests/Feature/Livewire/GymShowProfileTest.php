<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Platform\GymShow;
use App\Models\Gym;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class GymShowProfileTest extends TestCase
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

    public function test_admin_can_update_a_gyms_profile_details(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::factory()->create();

        Livewire::actingAs($admin, 'platform')
            ->test(GymShow::class, ['gym' => $gym])
            ->set('gym_name', 'Renamed Gym')
            ->set('gym_email', 'renamed-'.uniqid().'@example.test')
            ->set('gym_phone', '555-1234')
            ->call('updateGymProfile')
            ->assertHasNoErrors();

        $gym->refresh();
        $this->assertSame('Renamed Gym', $gym->name);
        $this->assertSame('555-1234', $gym->phone);
    }

    public function test_profile_update_rejects_duplicate_email(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::factory()->create();
        $other = Gym::factory()->create();

        Livewire::actingAs($admin, 'platform')
            ->test(GymShow::class, ['gym' => $gym])
            ->set('gym_email', $other->email)
            ->call('updateGymProfile')
            ->assertHasErrors(['gym_email']);
    }
}
