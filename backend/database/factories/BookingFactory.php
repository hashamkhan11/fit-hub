<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\GymClass;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gym_class_id' => GymClass::factory(),
            'member_id' => Member::factory(),
            'status' => 'booked',
            'reminder_sent_at' => null,
        ];
    }

    public function configure(): static
    {
        // gym_id is derived from gym_class_id, which may be set after definition()
        // resolves (e.g. via Factory::for()) - resolving it here, once the model
        // has every attribute, avoids depending on attribute-resolution order.
        return $this->afterMaking(function (Booking $booking) {
            if (! $booking->gym_id && $booking->gym_class_id) {
                $booking->gym_id = GymClass::find($booking->gym_class_id)?->gym_id;
            }
        });
    }
}
