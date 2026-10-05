<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Gym;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_id' => Gym::factory(),
            'member_id' => Member::factory(),
            'checked_in_at' => now(),
            'checked_out_at' => null,
        ];
    }
}
