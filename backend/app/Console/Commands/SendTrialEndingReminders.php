<?php

namespace App\Console\Commands;

use App\Mail\TrialEndingReminderMail;
use App\Models\Gym;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendTrialEndingReminders extends Command
{
    protected $signature = 'app:send-trial-ending-reminders';

    protected $description = 'Email gym owners whose free trial ends within the next 3 days';

    public function handle(): void
    {
        $gyms = Gym::query()
            ->where('subscription_status', 'trial')
            ->whereNull('trial_reminder_sent_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays(3)])
            ->get();

        foreach ($gyms as $gym) {
            if (! $gym->email) {
                continue;
            }

            try {
                Mail::to($gym->email)->send(new TrialEndingReminderMail($gym));
            } catch (\Throwable $e) {
                $this->error("Failed to email trial-ending reminder for gym #{$gym->id}: {$e->getMessage()}");

                continue;
            }

            $gym->update(['trial_reminder_sent_at' => now()]);
            $this->info("Trial-ending reminder sent for gym #{$gym->id}");
        }
    }
}
