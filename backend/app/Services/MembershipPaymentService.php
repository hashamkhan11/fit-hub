<?php

namespace App\Services;

use App\Models\Membership;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

/**
 * Records a payment against a membership and re-syncs its payment status.
 * Pulled out of the Members Livewire component so the balance-locking
 * transaction isn't tangled up with form state.
 */
class MembershipPaymentService
{
    /**
     * @param  array{amount: float|string, method: string, paid_at: string, note: string}  $data
     */
    public function record(int $membershipId, int $gymId, array $data): Payment
    {
        return DB::transaction(function () use ($membershipId, $gymId, $data) {
            $membership = Membership::whereHas('member', fn ($q) => $q->where('gym_id', $gymId))
                ->lockForUpdate()
                ->findOrFail($membershipId);

            if ((float) $data['amount'] > $membership->balance_due + 0.01) {
                throw new \DomainException('Amount exceeds the remaining balance of '.number_format($membership->balance_due, 2).'.');
            }

            $payment = $membership->payments()->create([
                'gym_id' => $gymId,
                'amount' => $data['amount'],
                'method' => $data['method'],
                'paid_at' => $data['paid_at'],
                'note' => $data['note'] ?: null,
            ]);

            $membership->syncPaymentStatus();

            return $payment;
        });
    }
}
