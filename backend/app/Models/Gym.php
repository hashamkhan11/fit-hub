<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Gym extends Model
{
    use Billable, HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'logo_path',
        'tax_id',
        'website',
        'currency_code',
        'receipt_footer',
        'subscription_status',
        'plan_name',
        'plan_price',
        'billing_cycle',
        'trial_ends_at',
        'trial_reminder_sent_at',
        'suspended_at',
        'suspended_reason',
        'subscription_plan_id',
    ];

    protected $appends = ['logo_url', 'currency_symbol'];

    protected function casts(): array
    {
        return [
            'plan_price' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'trial_reminder_sent_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }

    public function getCurrencySymbolAttribute(): string
    {
        $currency = collect(config('currencies'))->firstWhere('code', $this->currency_code);

        return $currency['symbol'] ?? '$';
    }

    public function staff(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function owner(): ?User
    {
        return $this->staff()->where('role', 'owner')->first();
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    public function lockDevices(): HasMany
    {
        return $this->hasMany(LockDevice::class);
    }

    public function platformActivityLogs(): HasMany
    {
        return $this->hasMany(PlatformActivityLog::class);
    }

    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    public function stripeName(): string
    {
        return $this->name;
    }

    public function stripeEmail(): ?string
    {
        return $this->email;
    }

    public function isSuspended(): bool
    {
        return $this->subscription_status === 'suspended';
    }

    public function isOnTrial(): bool
    {
        return $this->subscription_status === 'trial';
    }

    public function isActive(): bool
    {
        return ! $this->isSuspended();
    }

    public function trialDaysRemaining(): ?int
    {
        if (! $this->isOnTrial() || ! $this->trial_ends_at) {
            return null;
        }

        return max(0, now()->diffInDays($this->trial_ends_at, false) + 1);
    }

    public function isTrialExpired(): bool
    {
        return $this->isOnTrial() && $this->trial_ends_at && $this->trial_ends_at->isPast();
    }

    public function hasHardwareAccess(): bool
    {
        return (bool) $this->subscriptionPlan?->has_hardware_access;
    }

    public function canAddMember(): bool
    {
        $limit = $this->subscriptionPlan?->member_limit;

        return $limit === null || $this->members()->count() < $limit;
    }

    public function canAddStaff(): bool
    {
        $limit = $this->subscriptionPlan?->staff_limit;

        return $limit === null || $this->staff()->count() < $limit;
    }
}
