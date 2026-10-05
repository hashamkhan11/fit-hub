<?php

namespace App\Services;

use App\Models\Member;
use App\Models\Plan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Creates a new member together with their first membership and an optional
 * initial payment, all in one transaction. Form validation and the
 * member-limit/payment-amount checks stay in the Members Livewire component
 * since they map directly to specific form fields; this only owns the
 * actual writes.
 */
class MemberEnrollmentService
{
    /**
     * @param  array{trainer_id: ?int, name: string, email: string, phone: string, password: string, start_date: string, payment_amount: float, payment_method: string, payment_note: string}  $data
     */
    public function enroll(int $gymId, array $data, Plan $plan): Member
    {
        return DB::transaction(function () use ($gymId, $data, $plan) {
            $member = Member::create([
                'gym_id' => $gymId,
                'trainer_id' => $data['trainer_id'],
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'join_date' => now(),
            ]);

            $membership = $member->memberships()->create([
                'plan_id' => $plan->id,
                'start_date' => $data['start_date'],
                'end_date' => Carbon::parse($data['start_date'])->addDays($plan->duration_days),
                'payment_status' => 'pending',
                'price_paid' => $plan->price,
            ]);

            if ($data['payment_amount'] > 0) {
                $membership->payments()->create([
                    'gym_id' => $gymId,
                    'amount' => $data['payment_amount'],
                    'method' => $data['payment_method'],
                    'paid_at' => now(),
                    'note' => $data['payment_note'] ?: null,
                ]);

                $membership->syncPaymentStatus();
            }

            return $member;
        });
    }
}
