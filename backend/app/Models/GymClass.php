<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class GymClass extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = [
        'gym_id',
        'name',
        'instructor_name',
        'start_time',
        'duration_minutes',
        'capacity',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function book(Member $member): Booking
    {
        return DB::transaction(function () use ($member) {
            $class = static::where('id', $this->id)->lockForUpdate()->firstOrFail();

            $alreadyActive = $class->bookings()
                ->where('member_id', $member->id)
                ->whereIn('status', ['booked', 'waitlisted'])
                ->exists();

            if ($alreadyActive) {
                throw new \DomainException('This member already has a booking for this class.');
            }

            $bookedCount = $class->bookings()->where('status', 'booked')->count();
            $status = $bookedCount < $class->capacity ? 'booked' : 'waitlisted';

            return $class->bookings()->create([
                'gym_id' => $class->gym_id,
                'member_id' => $member->id,
                'status' => $status,
            ]);
        });
    }

    public function cancelBooking(Booking $booking): ?Booking
    {
        $wasBooked = $booking->status === 'booked';

        $booking->update(['status' => 'cancelled']);

        if (! $wasBooked) {
            return null;
        }

        $promoted = $this->bookings()->where('status', 'waitlisted')->oldest()->first();
        $promoted?->update(['status' => 'booked']);

        return $promoted;
    }
}
