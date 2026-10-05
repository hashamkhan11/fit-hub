<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\LockCommand;
use App\Models\LockDevice;
use App\Models\Member;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Fingerprints extends Component
{
    public string $search = '';

    public ?int $enrollPickerMemberId = null;

    public ?int $activeCommandId = null;

    public ?int $activeMemberId = null;

    public function mount(): void
    {
        Gate::authorize('manage-fingerprints');
    }

    public function render()
    {
        $gymId = auth()->user()->gym_id;

        $this->expireStaleCommands($gymId);

        $members = Member::where('gym_id', $gymId)
            ->when($this->search !== '', fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->with('fingerprintDevice:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'fingerprint_id', 'fingerprint_device_id']);

        $devices = LockDevice::where('gym_id', $gymId)->orderBy('name')->get();

        return view('livewire.fingerprints', [
            'members' => $members,
            'devices' => $devices,
            'activeCommand' => $this->activeCommandId
                ? LockCommand::whereHas('device', fn ($q) => $q->where('gym_id', $gymId))->find($this->activeCommandId)
                : null,
        ]);
    }

    /**
     * Marks stuck/expired commands as failed, so the dashboard doesn't show
     * "waiting..." forever and the owner can just try again.
     */
    private function expireStaleCommands(int $gymId): void
    {
        LockCommand::whereHas('device', fn ($q) => $q->where('gym_id', $gymId))
            ->whereIn('action', ['enroll', 'delete_fingerprint'])
            ->where('status', 'pending')
            ->where('expires_at', '<=', now())
            ->update(['status' => 'failed', 'completed_at' => now(), 'progress_message' => 'Timed out waiting for the device to pick this up.']);

        LockCommand::whereHas('device', fn ($q) => $q->where('gym_id', $gymId))
            ->whereIn('action', ['enroll', 'delete_fingerprint'])
            ->where('status', 'in_progress')
            ->where('updated_at', '<=', now()->subMinutes(2))
            ->update(['status' => 'failed', 'completed_at' => now(), 'progress_message' => 'Timed out.']);
    }

    public function openEnrollPicker(int $memberId): void
    {
        Gate::authorize('manage-fingerprints');

        $devices = LockDevice::where('gym_id', auth()->user()->gym_id)->get();

        if ($devices->isEmpty()) {
            $this->addError('enroll', 'Register a lock device with a fingerprint sensor first, under the Lock page.');

            return;
        }

        if ($devices->count() === 1) {
            $this->confirmEnroll($memberId, $devices->first()->id);

            return;
        }

        $this->enrollPickerMemberId = $memberId;
    }

    public function closeEnrollPicker(): void
    {
        $this->enrollPickerMemberId = null;
    }

    public function confirmEnroll(int $memberId, int $deviceId): void
    {
        Gate::authorize('manage-fingerprints');

        $gymId = auth()->user()->gym_id;
        $member = Member::where('gym_id', $gymId)->findOrFail($memberId);
        $device = LockDevice::where('gym_id', $gymId)->findOrFail($deviceId);

        $nextFingerprintId = (int) (Member::where('gym_id', $gymId)->max('fingerprint_id') ?? 0) + 1;

        $command = $device->commands()->create([
            'action' => 'enroll',
            'status' => 'pending',
            'payload' => ['member_id' => $member->id, 'fingerprint_id' => $nextFingerprintId],
            'requester_type' => User::class,
            'requester_id' => auth()->id(),
            'expires_at' => now()->addMinutes(3),
        ]);

        ActivityLog::record('fingerprint.enroll_requested', "Started fingerprint enrollment for {$member->name} on \"{$device->name}\".");

        $this->enrollPickerMemberId = null;
        $this->activeCommandId = $command->id;
        $this->activeMemberId = $member->id;
    }

    public function removeFingerprint(int $memberId): void
    {
        Gate::authorize('manage-fingerprints');

        $gymId = auth()->user()->gym_id;
        $member = Member::where('gym_id', $gymId)->findOrFail($memberId);

        if (! $member->fingerprint_id) {
            return;
        }

        $device = $member->fingerprint_device_id
            ? LockDevice::where('gym_id', $gymId)->find($member->fingerprint_device_id)
            : null;

        // Device is gone, so just clear the record instead of trying to notify it.
        if (! $device) {
            $member->update(['fingerprint_id' => null, 'fingerprint_device_id' => null]);
            ActivityLog::record('fingerprint.removed', "Cleared fingerprint record for {$member->name} (device no longer registered).");

            return;
        }

        $command = $device->commands()->create([
            'action' => 'delete_fingerprint',
            'status' => 'pending',
            'payload' => ['member_id' => $member->id, 'fingerprint_id' => $member->fingerprint_id],
            'requester_type' => User::class,
            'requester_id' => auth()->id(),
            'expires_at' => now()->addMinutes(2),
        ]);

        ActivityLog::record('fingerprint.remove_requested', "Requested fingerprint removal for {$member->name}.");

        $this->activeCommandId = $command->id;
        $this->activeMemberId = $member->id;
    }

    public function dismissActive(): void
    {
        $this->reset(['activeCommandId', 'activeMemberId']);
    }
}
