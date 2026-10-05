<?php

namespace App\Console\Commands;

use App\Mail\RenewalReminderMail;
use App\Models\Membership;
use App\Services\PushNotificationService;
use App\Services\SmsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendRenewalReminders extends Command
{
    protected $signature = 'app:send-renewal-reminders';

    protected $description = 'Notify members whose membership expires within the next 3 days';

    public function handle(SmsService $sms, PushNotificationService $push): void
    {
        $memberships = Membership::query()
            ->whereNull('renewal_reminder_sent_at')
            ->whereBetween('end_date', [now()->toDateString(), now()->addDays(3)->toDateString()])
            ->with('member', 'plan')
            ->get();

        foreach ($memberships as $membership) {
            $member = $membership->member;
            $expiryText = "Your {$membership->plan->name} plan expires on ".$membership->end_date->format('M j, Y').'. Renew to keep your access.';

            try {
                Mail::to($member->email)->send(new RenewalReminderMail($membership));
            } catch (\Throwable $e) {
                $this->error("Failed to email renewal reminder for membership #{$membership->id}: {$e->getMessage()}");

                continue;
            }

            if ($member->phone) {
                try {
                    $sms->send($member->phone, $expiryText);
                } catch (\Throwable $e) {
                    $this->error("Failed to SMS renewal reminder for membership #{$membership->id}: {$e->getMessage()}");
                }
            }

            $push->send($member, 'Your membership is expiring soon', $expiryText, ['type' => 'renewal']);

            $membership->update(['renewal_reminder_sent_at' => now()]);
            $this->info("Renewal reminder sent for membership #{$membership->id}");
        }
    }
}
