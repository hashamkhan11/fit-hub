<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendClassReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-class-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notify members whose booked class starts within the next hour';

    /**
     * Execute the console command.
     */
    public function handle(PushNotificationService $push): void
    {
        $bookings = Booking::query()
            ->where('status', 'booked')
            ->whereNull('reminder_sent_at')
            ->whereHas('gymClass', function ($query) {
                $query->whereBetween('start_time', [now(), now()->addHour()]);
            })
            ->with(['gymClass', 'member'])
            ->get();

        foreach ($bookings as $booking) {
            $push->send(
                $booking->member,
                'Upcoming class reminder',
                "{$booking->gymClass->name} starts at ".$booking->gymClass->start_time->format('g:i A'),
                ['type' => 'class']
            );

            $booking->update(['reminder_sent_at' => now()]);
            $this->info("Reminder sent for booking #{$booking->id}");
        }
    }
}
