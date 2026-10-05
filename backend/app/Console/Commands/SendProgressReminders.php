<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendProgressReminders extends Command
{
    protected $signature = 'app:send-progress-reminders';

    protected $description = 'Remind members who have not logged a measurement in the last 7 days to log their progress';

    public function handle(PushNotificationService $push): void
    {
        $members = Member::query()
            ->whereDoesntHave('measurements', function ($query) {
                $query->where('recorded_at', '>=', now()->subDays(7));
            })
            ->get();

        foreach ($members as $member) {
            $push->send(
                $member,
                'Log your progress',
                'It has been a week — log your latest measurements to track your progress.',
                ['type' => 'progress']
            );

            $this->info("Progress reminder sent to member #{$member->id}");
        }
    }
}
