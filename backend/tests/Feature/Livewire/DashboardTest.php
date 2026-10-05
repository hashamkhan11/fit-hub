<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Dashboard;
use App\Models\Attendance;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_can_view_the_dashboard(): void
    {
        $gym = Gym::factory()->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'trainer']));

        Livewire::test(Dashboard::class)->assertOk();
    }

    public function test_guest_is_redirected_away_from_the_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_stat_cards_reflect_real_data(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']));

        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->for($plan)->create();

        Attendance::factory()->for($member)->create(['gym_id' => $gym->id, 'checked_in_at' => now()]);
        Payment::create([
            'gym_id' => $gym->id,
            'membership_id' => $member->memberships->first()->id,
            'amount' => 50,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        Livewire::test(Dashboard::class)
            ->assertViewHas('totalMembers', 1)
            ->assertViewHas('activeMembersCount', 1)
            ->assertViewHas('checkInsThisWeek', 1)
            ->assertViewHas('revenueThisWeek', 50.0);
    }

    public function test_membership_status_classifies_active_expired_and_pending(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create();
        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']));

        $activeMember = Member::factory()->for($gym)->create();
        Membership::factory()->for($activeMember)->for($plan)->create();

        $expiredMember = Member::factory()->for($gym)->create();
        Membership::factory()->for($expiredMember)->for($plan)->expired()->create();

        $pendingMember = Member::factory()->for($gym)->create();
        Membership::factory()->for($pendingMember)->for($plan)->create([
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(35)->toDateString(),
        ]);

        $noMembershipMember = Member::factory()->for($gym)->create();

        Livewire::test(Dashboard::class)
            ->assertViewHas('membershipStatus', [
                'active' => 1,
                'expired' => 2,
                'pending' => 1,
            ]);
    }

    public function test_a_gyms_dashboard_never_counts_another_gyms_members(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        Member::factory()->for($otherGym)->create();
        Member::factory()->for($otherGym)->create();
        Member::factory()->for($otherGym)->create();

        $this->actingAs(User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']));
        Member::factory()->for($gym)->create();

        Livewire::test(Dashboard::class)->assertViewHas('totalMembers', 1);
    }
}
