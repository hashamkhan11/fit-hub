<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Members;
use App\Models\Gym;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MembershipRenewalTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_staff_can_renew_an_expired_members_membership_without_creating_a_new_member(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['price' => 50, 'duration_days' => 30]);
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'end_date' => now()->subDay()->toDateString(),
            'payment_status' => 'paid',
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->assertSet('renew_start_date', now()->toDateString())
            ->set('renew_plan_id', $plan->id)
            ->call('renewMembership')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseCount('memberships', 2);
        $this->assertDatabaseHas('memberships', [
            'member_id' => $member->id,
            'plan_id' => $plan->id,
            'payment_status' => 'pending',
            'price_paid' => 50,
        ]);
    }

    public function test_renewing_an_active_membership_defaults_the_start_date_to_the_day_after_it_ends(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create();
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'end_date' => now()->addDays(5)->toDateString(),
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->assertSet('renew_start_date', now()->addDays(6)->toDateString());
    }

    public function test_renewal_sets_the_end_date_based_on_the_selected_plans_duration(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['duration_days' => 90]);
        $member = Member::factory()->for($gym)->create();
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $plan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('memberships', [
            'member_id' => $member->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(90)->toDateString(),
        ]);
    }

    public function test_a_staff_member_cannot_renew_using_another_gyms_plan(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        $foreignPlan = Plan::factory()->for($otherGym)->create();
        $member = Member::factory()->for($gym)->create();
        $this->actingAs($this->staffUser($gym));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $foreignPlan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership');
    }

    public function test_a_staff_member_cannot_renew_another_gyms_member(): void
    {
        $gym = Gym::factory()->create();
        $otherGym = Gym::factory()->create();
        $member = Member::factory()->for($otherGym)->create();
        $this->actingAs($this->staffUser($gym));

        $this->expectException(ModelNotFoundException::class);

        Livewire::test(Members::class)->call('startRenewal', $member->id);
    }

    public function test_renewing_with_a_start_date_that_overlaps_an_unpaid_membership_retires_it(): void
    {
        $gym = Gym::factory()->create();
        $oldPlan = Plan::factory()->for($gym)->create();
        $newPlan = Plan::factory()->for($gym)->create(['duration_days' => 30]);
        $member = Member::factory()->for($gym)->create();
        $stray = Membership::factory()->for($member)->create([
            'plan_id' => $oldPlan->id,
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(35)->toDateString(),
            'payment_status' => 'pending',
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $newPlan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('memberships', ['id' => $stray->id]);
        $this->assertDatabaseHas('memberships', [
            'member_id' => $member->id,
            'plan_id' => $newPlan->id,
            'start_date' => now()->toDateString(),
        ]);
    }

    public function test_renewing_with_a_start_date_that_overlaps_a_paid_membership_is_blocked(): void
    {
        $gym = Gym::factory()->create();
        $existingPlan = Plan::factory()->for($gym)->create();
        $newPlan = Plan::factory()->for($gym)->create();
        $member = Member::factory()->for($gym)->create();
        $paid = Membership::factory()->for($member)->create([
            'plan_id' => $existingPlan->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'payment_status' => 'paid',
            'price_paid' => 100,
        ]);
        $paid->payments()->create([
            'gym_id' => $gym->id,
            'amount' => 100,
            'method' => 'cash',
            'paid_at' => now(),
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $newPlan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership')
            ->assertHasErrors('renew_start_date');

        $this->assertDatabaseCount('memberships', 1);
        $this->assertNotSoftDeleted('memberships', ['id' => $paid->id]);
    }

    public function test_renewing_with_a_start_date_that_does_not_overlap_anything_leaves_existing_memberships_untouched(): void
    {
        $gym = Gym::factory()->create();
        $currentPlan = Plan::factory()->for($gym)->create();
        $newPlan = Plan::factory()->for($gym)->create();
        $member = Member::factory()->for($gym)->create();
        $current = Membership::factory()->for($member)->create([
            'plan_id' => $currentPlan->id,
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'payment_status' => 'paid',
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $newPlan->id)
            ->set('renew_start_date', now()->addDays(11)->toDateString())
            ->call('renewMembership')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('memberships', 2);
        $this->assertNotSoftDeleted('memberships', ['id' => $current->id]);
    }

    public function test_renewing_twice_with_overlapping_unpaid_attempts_leaves_only_the_final_one(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['duration_days' => 30]);
        $member = Member::factory()->for($gym)->create();
        $this->actingAs($this->staffUser($gym));

        $component = Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $plan->id)
            ->set('renew_start_date', now()->addDays(1)->toDateString())
            ->call('renewMembership')
            ->assertHasNoErrors();

        $firstAttempt = Membership::where('member_id', $member->id)->first();

        $component->call('startRenewal', $member->id)
            ->set('renew_plan_id', $plan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership')
            ->assertHasNoErrors();

        $this->assertSoftDeleted('memberships', ['id' => $firstAttempt->id]);
        $this->assertDatabaseHas('memberships', [
            'member_id' => $member->id,
            'start_date' => now()->toDateString(),
        ]);
    }

    public function test_a_member_can_be_deleted_after_an_overlapping_stray_renewal_is_auto_retired_and_the_final_one_is_paid(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['duration_days' => 30, 'price' => 100]);
        $member = Member::factory()->for($gym)->create();
        $this->actingAs($this->staffUser($gym));

        $component = Livewire::test(Members::class)
            ->call('startRenewal', $member->id)
            ->set('renew_plan_id', $plan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership')
            ->assertHasNoErrors();

        $component->call('startRenewal', $member->id)
            ->set('renew_plan_id', $plan->id)
            ->set('renew_start_date', now()->toDateString())
            ->call('renewMembership')
            ->assertHasNoErrors();

        $final = Membership::withTrashed()->where('member_id', $member->id)->whereNull('deleted_at')->sole();
        $final->payments()->create([
            'gym_id' => $gym->id,
            'amount' => 100,
            'method' => 'cash',
            'paid_at' => now(),
        ]);

        $component->call('deleteMember', $member->id)->assertHasNoErrors();

        $this->assertSoftDeleted('members', ['id' => $member->id]);
    }

    public function test_deleting_a_member_is_still_blocked_when_a_genuinely_unpaid_membership_remains(): void
    {
        $gym = Gym::factory()->create();
        $plan = Plan::factory()->for($gym)->create(['duration_days' => 30, 'price' => 100]);
        $member = Member::factory()->for($gym)->create();
        Membership::factory()->for($member)->create([
            'plan_id' => $plan->id,
            'start_date' => now()->subDays(30)->toDateString(),
            'end_date' => now()->subDay()->toDateString(),
            'payment_status' => 'pending',
            'price_paid' => 100,
        ]);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('deleteMember', $member->id)
            ->assertHasErrors('deleteMember');

        $this->assertDatabaseHas('members', ['id' => $member->id, 'deleted_at' => null]);
    }
}
