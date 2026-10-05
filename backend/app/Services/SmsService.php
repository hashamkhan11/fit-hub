<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;

/**
 * Sends SMS via Twilio if it's set up in .env, else just logs the message.
 */
class SmsService
{
    public function send(string $to, string $message): void
    {
        $sid = config('services.twilio.sid');
        $authToken = config('services.twilio.auth_token');
        $from = config('services.twilio.from');

        if (! $sid || ! $authToken || ! $from) {
            Log::info("[SMS stub] would send to {$to}: {$message}");

            return;
        }

        (new Client($sid, $authToken))->messages->create($to, [
            'from' => $from,
            'body' => $message,
        ]);
    }
}
