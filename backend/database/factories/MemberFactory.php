<?php

namespace Database\Factories;

use App\Models\Gym;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'password' => Hash::make('password'),
            'join_date' => now()->toDateString(),
            'fcm_token' => null,
        ];
    }
}
