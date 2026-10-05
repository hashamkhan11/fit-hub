<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\GymClass;
use App\Models\Member;
use App\Services\PushNotificationService;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

#[Layout('layouts.app')]
class Bookings extends Component
{
    public ?int $classId = null;

    #[Validate('required|exists:members,id')]
    public ?int $memberId = null;

    public function mount(): void
    {
        Gate::authorize('view-bookings');

        $this->classId = GymClass::where('gym_id', auth()->user()->gym_id)
            ->where('is_active', true)
            ->orderBy('start_time')
            ->value('id');
    }

    public function render()
    {
        $gymId = auth()->user()->gym_id;

        $selectedClass = $this->classId
            ? GymClass::where('gym_id', $gymId)->find($this->classId)
            : null;

        return view('livewire.bookings', [
            'classes' => GymClass::where('gym_id', $gymId)->orderBy('start_time')->get(),
            'selectedClass' => $selectedClass,
            'bookings' => $selectedClass
                ? $selectedClass->bookings()
                    ->with(['member' => fn ($q) => $q->withTrashed()])
                    ->where('status', '!=', 'cancelled')
                    ->orderByRaw("status = 'waitlisted'")
                    ->oldest()
                    ->get()
                : collect(),
            'members' => Member::where('gym_id', $gymId)->orderBy('name')->get(),
        ]);
    }

    public function selectClass(int $classId): void
    {
        $this->classId = $classId;
    }

    public function addBooking(): void
    {
        Gate::authorize('manage-bookings');

        $this->validate();

        $class = GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($this->classId);
        $member = Member::where('gym_id', auth()->user()->gym_id)->findOrFail($this->memberId);

        try {
            $booking = $class->book($member);
        } catch (\DomainException $e) {
            $this->addError('memberId', $e->getMessage());

            return;
        }

        ActivityLog::record(
            'booking.added',
            "Booked {$member->name} into {$class->name} ({$booking->status})."
        );

        app(PushNotificationService::class)->send(
            $member,
            $booking->status === 'booked' ? 'Booking confirmed' : 'Added to waitlist',
            $booking->status === 'booked'
                ? "You're booked for {$class->name} at ".$class->start_time->format('g:i A, M j').'.'
                : "{$class->name} is full — you're on the waitlist and will be booked automatically if a spot opens up.",
            ['type' => 'class']
        );

        $this->reset('memberId');
    }

    public function cancelBooking(int $bookingId): void
    {
        Gate::authorize('manage-bookings');

        $class = GymClass::where('gym_id', auth()->user()->gym_id)->findOrFail($this->classId);
        $booking = $class->bookings()->with(['member' => fn ($q) => $q->withTrashed()])->findOrFail($bookingId);
        $memberName = $booking->member->name;

        $promoted = $class->cancelBooking($booking);

        ActivityLog::record('booking.cancelled', "Cancelled {$memberName}'s booking for {$class->name}.");

        if ($promoted) {
            app(PushNotificationService::class)->send(
                $promoted->member,
                'Booking confirmed',
                "A spot opened up — you're now booked for {$class->name} at ".$class->start_time->format('g:i A, M j').'.',
                ['type' => 'class']
            );
        }
    }
}
