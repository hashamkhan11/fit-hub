<?php

namespace Tests\Feature\Api;

use App\Models\Gym;
use App\Models\LockCommand;
use App\Models\LockDevice;
use App\Models\Member;
use App\Models\SubscriptionPlan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LockCommandStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createCommand(Gym $gym, Member $requester): LockCommand
    {
        [$device] = LockDevice::issueToken($gym->id, 'Front Door');

        return $device->commands()->create([
            'action' => 'open',
            'status' => 'pending',
            'requester_type' => Member::class,
            'requester_id' => $requester->id,
        ]);
    }

    private function gymWithHardwareAccess(): Gym
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Hardware Plan',
            'slug' => 'hardware-plan-'.uniqid(),
            'monthly_price' => 49,
            'has_hardware_access' => true,
        ]);

        return Gym::factory()->create(['subscription_plan_id' => $plan->id]);
    }

    public function test_member_can_poll_the_status_of_their_own_gyms_command(): void
    {
        $gym = $this->gymWithHardwareAccess();
        $member = Member::factory()->for($gym)->create();
        $command = $this->createCommand($gym, $member);

        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson("/api/lock/commands/{$command->id}/status");

        $response->assertOk();
        $this->assertSame('pending', $response->json('status'));
    }

    public function test_member_sees_the_updated_status_once_the_device_acknowledges(): void
    {
        $gym = $this->gymWithHardwareAccess();
        $member = Member::factory()->for($gym)->create();
        $command = $this->createCommand($gym, $member);
        $command->markCompleted();

        Sanctum::actingAs($member, ['*']);

        $response = $this->getJson("/api/lock/commands/{$command->id}/status");

        $response->assertOk();
        $this->assertSame('completed', $response->json('status'));
    }

    public function test_member_cannot_poll_a_commands_status_from_another_gym(): void
    {
        $gym = $this->gymWithHardwareAccess();
        $owner = Member::factory()->for($gym)->create();
        $command = $this->createCommand($gym, $owner);

        $otherMember = Member::factory()->for($this->gymWithHardwareAccess())->create();
        Sanctum::actingAs($otherMember, ['*']);

        $response = $this->getJson("/api/lock/commands/{$command->id}/status");

        $response->assertNotFound();
    }
}
