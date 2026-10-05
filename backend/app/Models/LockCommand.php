<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LockCommand extends Model
{
    protected $fillable = [
        'lock_device_id',
        'action',
        'payload',
        'status',
        'progress_message',
        'requester_type',
        'requester_id',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(LockDevice::class, 'lock_device_id');
    }

    public function requester(): MorphTo
    {
        return $this->morphTo();
    }

    public function markInProgress(): void
    {
        $this->update(['status' => 'in_progress']);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => 'completed', 'completed_at' => now()]);
        $this->applyFingerprintEffect(true);
    }

    public function markFailed(): void
    {
        $this->update(['status' => 'failed', 'completed_at' => now()]);
        $this->applyFingerprintEffect(false);
    }

    public function markExpired(): void
    {
        $this->update(['status' => 'expired', 'completed_at' => now()]);
    }

    /**
     * On success, saves the fingerprint ID back onto the Member so the
     * dashboard updates right away. The device itself never knows the member.
     */
    private function applyFingerprintEffect(bool $succeeded): void
    {
        if (! $succeeded || ! in_array($this->action, ['enroll', 'delete_fingerprint'], true)) {
            return;
        }

        $memberId = $this->payload['member_id'] ?? null;

        if (! $memberId) {
            return;
        }

        $member = Member::find($memberId);

        if (! $member) {
            return;
        }

        if ($this->action === 'enroll') {
            $member->update([
                'fingerprint_id' => $this->payload['fingerprint_id'] ?? null,
                'fingerprint_device_id' => $this->lock_device_id,
            ]);
        } else {
            $member->update(['fingerprint_id' => null, 'fingerprint_device_id' => null]);
        }
    }
}
