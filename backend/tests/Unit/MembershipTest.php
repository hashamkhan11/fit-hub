<?php

namespace Tests\Unit;

use App\Models\Membership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_membership_is_active_when_end_date_is_in_the_future(): void
    {
        $membership = Membership::factory()->create(['end_date' => now()->addDay()->toDateString()]);

        $this->assertTrue($membership->isActive());
    }

    public function test_membership_is_not_active_when_end_date_has_passed(): void
    {
        $membership = Membership::factory()->create(['end_date' => now()->subDay()->toDateString()]);

        $this->assertFalse($membership->isActive());
    }

    public function test_membership_is_active_when_end_date_is_today(): void
    {
        // Coverage runs through the end of the expiry day, not from its start —
        // see the comment on Membership::isActive().
        $membership = Membership::factory()->create(['end_date' => now()->toDateString()]);

        $this->assertTrue($membership->isActive());
    }

    public function test_membership_is_not_overdue_when_paused_even_if_end_date_has_passed(): void
    {
        $membership = Membership::factory()->create([
            'end_date' => now()->subDays(10)->toDateString(),
            'payment_status' => 'pending',
            'paused_at' => now()->subDays(3),
        ]);

        $this->assertFalse($membership->isOverdue());
    }

    public function test_membership_is_overdue_when_unpaid_end_date_passed_and_not_paused(): void
    {
        $membership = Membership::factory()->create([
            'end_date' => now()->subDays(10)->toDateString(),
            'payment_status' => 'pending',
        ]);

        $this->assertTrue($membership->isOverdue());
    }
}
