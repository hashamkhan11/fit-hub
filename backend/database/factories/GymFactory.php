<?php

namespace Database\Factories;

use App\Models\Gym;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gym>
 */
class GymFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Gym',
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'billing_cycle' => 'monthly',
        ];
    }
}
