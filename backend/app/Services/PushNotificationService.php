<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Notification as NotificationRecord;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Kreait\Laravel\Firebase\Facades\Firebase;

/**
 * Notifies a member: always records it in their in-app notification inbox,
 * and additionally pushes it via FCM if they have a registered device token.
 * Shared by every place a member needs to be notified (self-service booking
 * API, admin-initiated booking actions, reminders) so they all behave
 * identically and all show up in the inbox.
 */
class PushNotificationService
{
    public function send(Member $member, string $title, string $body, array $data = ['type' => 'general']): void
    {
        NotificationRecord::create([
            'gym_id' => $member->gym_id,
            'member_id' => $member->id,
            'title' => $title,
            'body' => $body,
            'type' => $data['type'] ?? 'general',
            'data' => $data,
        ]);

        if (! $member->fcm_token) {
            return;
        }

        try {
            Firebase::messaging()->send(
                CloudMessage::new()
                    ->withToken($member->fcm_token)
                    ->withNotification(FcmNotification::create($title, $body))
                    ->withData($data)
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
