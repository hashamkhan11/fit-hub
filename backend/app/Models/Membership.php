<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'plan_id',
        'start_date',
        'end_date',
        'paused_at',
        'resumes_at',
        'payment_status',
        'price_paid',
        'renewal_reminder_sent_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'paused_at' => 'datetime',
        'resumes_at' => 'date',
        'price_paid' => 'decimal:2',
        'renewal_reminder_sent_at' => 'datetime',
    ];

    protected $appends = [
        'amount_paid',
        'balance_due',
    ];

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        if ($this->isPaused()) {
            return false;
        }

        $started = $this->start_date !== null && ! $this->start_date->isFuture();

        // Compare against the end of end_date's day, so coverage lasts the whole day.
        return $started && ($this->end_date?->copy()->endOfDay()->isFuture() ?? false);
    }

    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    public function freeze(?string $resumesAt = null): void
    {
        if ($this->isPaused()) {
            return;
        }

        $this->update([
            'paused_at' => now(),
            'resumes_at' => $resumesAt,
        ]);
    }

    /**
     * Resumes a paused membership, extending end_date by however long it was paused.
     */
    public function resume(): void
    {
        if (! $this->isPaused()) {
            return;
        }

        $pausedDays = (int) round($this->paused_at->diffInHours(now()) / 24);

        $this->update([
            'end_date' => $this->end_date->addDays($pausedDays),
            'paused_at' => null,
            'resumes_at' => null,
        ]);
    }

    /**
     * True if not fully paid and the term has ended. Paused memberships are never overdue.
     */
    public function isOverdue(): bool
    {
        return ! $this->isPaused() && $this->payment_status !== 'paid' && ($this->end_date?->copy()->endOfDay()->isPast() ?? false);
    }

    protected function amountPaid(): Attribute
    {
        return Attribute::get(fn () => round((float) $this->payments()->sum('amount'), 2));
    }

    protected function balanceDue(): Attribute
    {
        return Attribute::get(fn () => max(0, round((float) ($this->price_paid ?? 0) - $this->amount_paid, 2)));
    }

    /**
     * Updates payment_status based on payments recorded so far.
     */
    public function syncPaymentStatus(): void
    {
        $paid = (float) $this->payments()->sum('amount');
        $total = (float) ($this->price_paid ?? 0);

        $status = match (true) {
            $paid <= 0 => 'pending',
            $paid < $total => 'partial',
            default => 'paid',
        };

        if ($status !== $this->payment_status) {
            $this->update(['payment_status' => $status]);
        }
    }
}
