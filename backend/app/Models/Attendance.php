<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Attendance extends Model
{
    use BelongsToGym, HasFactory;

    protected $fillable = [
        'gym_id',
        'member_id',
        'checked_in_at',
        'checked_out_at',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'checked_out_at' => 'datetime',
    ];

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Toggles check-in/check-out. Used by QR scan and fingerprint scanner both.
     *
     * @return array{success: bool, unlock: bool, message: string}
     */
    public static function recordScan(Member $member): array
    {
        return DB::transaction(function () use ($member) {
            // Lock the member row so two scans at once can't both create a check-in.
            $member = Member::whereKey($member->id)->lockForUpdate()->firstOrFail();

            // Checks all days, not just today, so an old forgotten checkout gets closed.
            $openAttendance = $member->attendances()
                ->whereNull('checked_out_at')
                ->latest('checked_in_at')
                ->first();

            if ($openAttendance) {
                $openAttendance->update(['checked_out_at' => now()]);

                return [
                    'success' => true,
                    'unlock' => true,
                    'message' => "Checked out: {$member->name}",
                ];
            }

            if (! $member->activeMembership()) {
                return [
                    'success' => false,
                    'unlock' => false,
                    'message' => "{$member->name} has no active membership.",
                ];
            }

            $member->attendances()->create([
                'gym_id' => $member->gym_id,
                'checked_in_at' => now(),
            ]);

            return [
                'success' => true,
                'unlock' => true,
                'message' => "Checked in: {$member->name}",
            ];
        });
    }
}
