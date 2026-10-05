<?php

namespace App\Models;

use App\Models\Concerns\BelongsToGym;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class Member extends Authenticatable
{
    use BelongsToGym, HasApiTokens, HasFactory, SoftDeletes;

    protected $fillable = [
        'gym_id',
        'trainer_id',
        'name',
        'email',
        'phone',
        'height_cm',
        'password',
        'photo_path',
        'join_date',
        'fcm_token',
        'fingerprint_id',
        'fingerprint_device_id',
        'reset_otp',
        'reset_otp_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'reset_otp',
        'reset_otp_expires_at',
    ];

    protected $appends = ['display_code', 'photo_url', 'initials'];

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo_path ? asset('storage/'.$this->photo_path) : null;
    }

    /**
     * Initials shown as avatar text (e.g. "JS") when no photo is uploaded.
     */
    public function getInitialsAttribute(): string
    {
        $words = collect(explode(' ', trim($this->name)))->filter();

        return $words->take(2)->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))->join('');
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'join_date' => 'date',
            'reset_otp_expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Member $member) {
            $member->qr_code ??= (string) Str::uuid();

            if (empty($member->member_code)) {
                $member->member_code = (int) static::withTrashed()->where('gym_id', $member->gym_id)->max('member_code') + 1;
            }
        });
    }

    /**
     * Member ID shown to people, e.g. "M-0007", to tell same-named members apart.
     */
    public function getDisplayCodeAttribute(): string
    {
        return 'M-'.str_pad((string) $this->member_code, 4, '0', STR_PAD_LEFT);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_id');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(Membership::class);
    }

    /**
     * The newest membership, any status — use when you need more than just active/not.
     */
    public function latestMembership(): HasOne
    {
        return $this->hasOne(Membership::class)->latestOfMany('id');
    }

    /**
     * The membership currently granting access, if any (not always the one with
     * the latest end_date).
     */
    public function activeMembership(): ?Membership
    {
        return $this->memberships->first(fn (Membership $membership) => $membership->isActive());
    }

    public function hasActiveMembership(): bool
    {
        return $this->activeMembership() !== null;
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function measurements(): HasMany
    {
        return $this->hasMany(Measurement::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function fingerprintDevice(): BelongsTo
    {
        return $this->belongsTo(LockDevice::class, 'fingerprint_device_id');
    }
}
