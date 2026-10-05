<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\Membership;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'plan_id' => Plan::factory(),
            'start_date' => now()->subDays(10)->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'payment_status' => 'paid',
            'price_paid' => fake()->randomFloat(2, 20, 100),
            'renewal_reminder_sent_at' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'start_date' => now()->subDays(40)->toDateString(),
            'end_date' => now()->subDays(10)->toDateString(),
        ]);
    }
}
