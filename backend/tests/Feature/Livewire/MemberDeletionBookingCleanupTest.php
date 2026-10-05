<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Bookings;
use App\Livewire\Members;
use App\Models\Booking;
use App\Models\Gym;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberDeletionBookingCleanupTest extends TestCase
{
    use RefreshDatabase;

    private function staffUser(Gym $gym): User
    {
        return User::factory()->create(['gym_id' => $gym->id, 'role' => 'owner']);
    }

    public function test_deleting_a_member_cancels_their_active_booking_and_promotes_a_waitlisted_member(): void
    {
        $gym = Gym::factory()->create();
        $class = GymClass::factory()->for($gym)->create(['capacity' => 1]);
        $bookedMember = Member::factory()->for($gym)->create();
        $waitlistedMember = Member::factory()->for($gym)->create();
        $booking = Booking::factory()->for($class, 'gymClass')->for($bookedMember)->create(['status' => 'booked']);
        $waitlisted = Booking::factory()->for($class, 'gymClass')->for($waitlistedMember)->create(['status' => 'waitlisted']);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('deleteMember', $bookedMember->id)
            ->assertHasNoErrors();

        $this->assertSame('cancelled', $booking->fresh()->status);
        $this->assertSame('booked', $waitlisted->fresh()->status);
    }

    public function test_deleting_a_member_cancels_their_waitlisted_booking(): void
    {
        $gym = Gym::factory()->create();
        $class = GymClass::factory()->for($gym)->create(['capacity' => 1]);
        $member = Member::factory()->for($gym)->create();
        $booking = Booking::factory()->for($class, 'gymClass')->for($member)->create(['status' => 'waitlisted']);
        $this->actingAs($this->staffUser($gym));

        Livewire::test(Members::class)
            ->call('deleteMember', $member->id)
            ->assertHasNoErrors();

        $this->assertSame('cancelled', $booking->fresh()->status);
    }

    public function test_the_bookings_page_does_not_crash_after_the_booked_members_deletion(): void
    {
        $gym = Gym::factory()->create();
        $class = GymClass::factory()->for($gym)->create(['capacity' => 1, 'is_active' => true]);
        $member = Member::factory()->for($gym)->create();
        Booking::factory()->for($class, 'gymClass')->for($member)->create(['status' => 'booked']);
        $owner = $this->staffUser($gym);
        $this->actingAs($owner);

        Livewire::test(Members::class)->call('deleteMember', $member->id)->assertHasNoErrors();

        Livewire::test(Bookings::class)->call('selectClass', $class->id)->assertOk();
    }
}
