<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\LockCommand;
use App\Models\LockDevice;
use App\Models\Member;
use Illuminate\Http\Request;

class LockController extends Controller
{
    /**
     * Lists every lock device at the member's own gym.
     */
    public function devices(Request $request)
    {
        $devices = LockDevice::where('gym_id', $request->user()->gym_id)
            ->orderBy('name')
            ->get(['id', 'name', 'last_seen_at']);

        return response()->json(['devices' => $devices]);
    }

    public function unlock(Request $request, LockDevice $device)
    {
        return $this->sendCommand($request, $device, 'open', 'unlocked');
    }

    public function lock(Request $request, LockDevice $device)
    {
        return $this->sendCommand($request, $device, 'close', 'locked');
    }

    private function sendCommand(Request $request, LockDevice $device, string $action, string $verb)
    {
        abort_unless($device->gym_id === $request->user()->gym_id, 404);

        if (! $request->user()->hasActiveMembership()) {
            return response()->json(['message' => 'Your membership is not active.'], 403);
        }

        $command = $device->commands()->create([
            'action' => $action,
            'status' => 'pending',
            'requester_type' => Member::class,
            'requester_id' => $request->user()->id,
            'expires_at' => now()->addMinutes(2),
        ]);

        ActivityLog::create([
            'gym_id' => $device->gym_id,
            'user_id' => null,
            'action' => "lock.{$action}_requested",
            'description' => "{$request->user()->name} {$verb} {$device->name} via the mobile app.",
            'created_at' => now(),
        ]);

        return response()->json(['command_id' => $command->id, 'status' => $command->status]);
    }

    /**
     * Mobile app polls this to show "unlocked"/"failed" as soon as the device replies.
     */
    public function commandStatus(Request $request, LockCommand $command)
    {
        abort_unless($command->device->gym_id === $request->user()->gym_id, 404);

        return response()->json(['status' => $command->status]);
    }

    /**
     * The ESP32 polls this every few seconds, using its own device token
     * (in the X-Device-Token header) instead of a normal user login.
     */
    public function pollCommands(Request $request)
    {
        $device = LockDevice::findByToken((string) $request->header('X-Device-Token'));

        abort_unless($device !== null, 401);

        $device->update(['last_seen_at' => now()]);

        // Expire old queued commands first, so a device that just reconnected
        // doesn't run stale commands from while it was offline.
        $device->commands()
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'expired', 'completed_at' => now()]);

        $commands = $device->commands()
            ->where('status', 'pending')
            ->where(function ($query) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderBy('created_at')
            ->get(['id', 'action', 'payload', 'created_at']);

        return response()->json(['commands' => $commands]);
    }

    public function ackCommand(Request $request, int $command)
    {
        $device = LockDevice::findByToken((string) $request->header('X-Device-Token'));

        // Check the device token first, so a bad token always gets 401 —
        // this stops someone guessing real command IDs vs fake ones.
        abort_unless($device !== null, 401);

        $command = $device->commands()->find($command);

        abort_unless($command !== null, 404);

        $validated = $request->validate([
            'status' => ['required', 'in:completed,failed,in_progress'],
        ]);

        match ($validated['status']) {
            'completed' => $command->markCompleted(),
            'failed' => $command->markFailed(),
            'in_progress' => $command->markInProgress(),
        };

        return response()->json(['message' => 'Acknowledged.']);
    }

    /**
     * Lets the ESP32 send a status update (e.g. "Place finger again") while
     * a multi-step command like fingerprint enrollment is still running.
     */
    public function progressCommand(Request $request, int $command)
    {
        $device = LockDevice::findByToken((string) $request->header('X-Device-Token'));

        abort_unless($device !== null, 401);

        $command = $device->commands()->find($command);

        abort_unless($command !== null, 404);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:255'],
        ]);

        $command->update(['progress_message' => $validated['message']]);

        return response()->json(['message' => 'ok']);
    }
}
