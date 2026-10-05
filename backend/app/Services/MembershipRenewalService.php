<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Membership;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Renews a member onto a new plan. Pulled out of the Members Livewire
 * component because the conflict-resolution transaction below is the real
 * business rule, not UI glue.
 */
class MembershipRenewalService
{
    /**
     * @return array{membership: Membership, removed: Collection}
     */
    public function renew(Member $member, Plan $plan, string $startDate): array
    {
        $newStart = Carbon::parse($startDate);
        $conflicting = collect();

        $membership = DB::transaction(function () use ($member, $plan, $startDate, $newStart, &$conflicting) {
            // Lock this check so two renewal submissions at once can't both
            // pass the conflict check. An unpaid overlapping membership is
            // likely a duplicate and gets auto-removed; a paid one is a real
            // record, so we refuse instead of deleting it.
            $conflicting = $member->memberships()->where('end_date', '>=', $newStart)->lockForUpdate()->get();

            foreach ($conflicting as $existing) {
                if ($existing->amount_paid > 0) {
                    throw new \DomainException('This member already has a paid membership running '.$existing->start_date->format('M j, Y').' – '.$existing->end_date->format('M j, Y').'. Resolve or remove that renewal before starting one on this date.');
                }
            }

            foreach ($conflicting as $existing) {
                $existing->delete();
            }

            return $member->memberships()->create([
                'plan_id' => $plan->id,
                'start_date' => $startDate,
                'end_date' => $newStart->copy()->addDays($plan->duration_days),
                'payment_status' => 'pending',
                'price_paid' => $plan->price,
            ]);
        });

        return ['membership' => $membership, 'removed' => $conflicting];
    }
}
