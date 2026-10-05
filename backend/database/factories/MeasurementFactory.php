<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Measurement;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Measurement>
 */
class MeasurementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'member_id' => Member::factory(),
            'recorded_at' => now()->toDateString(),
            'weight_kg' => fake()->randomFloat(2, 55, 100),
            'body_fat_percentage' => fake()->randomFloat(2, 10, 30),
            'chest_cm' => fake()->randomFloat(2, 80, 120),
            'waist_cm' => fake()->randomFloat(2, 60, 100),
            'hips_cm' => fake()->randomFloat(2, 80, 120),
            'arms_cm' => fake()->randomFloat(2, 25, 45),
            'notes' => null,
        ];
    }
}
