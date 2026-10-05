<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'name' => fake()->randomElement(['Basic', 'Standard', 'Premium']),
            'duration_days' => 30,
            'price' => fake()->randomFloat(2, 20, 100),
            'features' => ['Access to gym floor'],
            'is_active' => true,
        ];
    }
}
