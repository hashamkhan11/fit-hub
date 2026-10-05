<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Gym;
use App\Models\GymClass;
use App\Models\Measurement;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Creates a brand-new, isolated demo gym (its own tenant, its own owner
 * dashboard) on a subscription plan with no hardware/lock access, and one
 * richly-seeded showcase member for Play Store screenshots. Deliberately
 * separate from gym #2's `demo:seed-fithub` data — that gym has a real
 * Front Door lock device wired to physical ESP32 hardware, so it's the
 * wrong tenant for a "no lock feature" screenshot account.
 *
 * Safe to run more than once.
 */
class SeedShowcaseGym extends Command
{
    protected $signature = 'demo:seed-showcase';

    protected $description = 'Seed an isolated demo gym (no hardware/lock plan) with one showcase member for Play Store screenshots';

    public function handle(): void
    {
        $plan = $this->hardwareFreeSubscriptionPlan();
        $gym = $this->createGym($plan);
        $this->createOwner($gym);
        $trainer = $this->createTrainer($gym);
        $memberPlan = $this->createMemberPlan($gym);
        $classes = $this->createClasses($gym);
        $member = $this->createShowcaseMember($gym, $trainer, $memberPlan);
        $this->createMembershipHistory($member, $memberPlan);
        $this->createMeasurementTrend($member);
        $this->createAttendanceHistory($member, $gym);
        $this->createBookings($member, $classes);

        $this->info("Showcase gym '{$gym->name}' seeded. Log in to the mobile app as {$member->email} / password");
    }

    private function hardwareFreeSubscriptionPlan(): SubscriptionPlan
    {
        return SubscriptionPlan::firstOrCreate(
            ['slug' => 'digital'],
            [
                'name' => 'Digital',
                'description' => 'For gyms that don\'t use FitHub\'s door-lock hardware.',
                'monthly_price' => 19.00,
                'yearly_price' => 182.00,
                'member_limit' => 10,
                'staff_limit' => 3,
                'features' => [
                    'Members, Attendance, Classes & Bookings',
                    'Payments & Billing',
                    'Staff & Trainer Access',
                ],
                'has_hardware_access' => false,
                'is_active' => true,
                'sort_order' => 0,
            ]
        );
    }

    private function createGym(SubscriptionPlan $plan): Gym
    {
        return Gym::firstOrCreate(
            ['email' => 'hello@elevatefitness.pk'],
            [
                'name' => 'Elevate Fitness Studio',
                'phone' => '0321-4456781',
                'currency_code' => 'USD',
                'subscription_status' => 'active',
                'subscription_plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'plan_price' => $plan->monthly_price,
                'billing_cycle' => 'monthly',
                'trial_ends_at' => null,
            ]
        );
    }

    private function createOwner(Gym $gym): User
    {
        return User::firstOrCreate(
            ['email' => 'owner@elevatefitness.pk'],
            [
                'gym_id' => $gym->id,
                'name' => 'Kamran Sheikh',
                'password' => 'password',
                'role' => 'owner',
                'email_verified_at' => now(),
            ]
        );
    }

    private function createTrainer(Gym $gym): User
    {
        return User::firstOrCreate(
            ['email' => 'trainer@elevatefitness.pk'],
            [
                'gym_id' => $gym->id,
                'name' => 'Adeel Farooq',
                'password' => 'password',
                'role' => 'trainer',
                'email_verified_at' => now(),
            ]
        );
    }

    private function createMemberPlan(Gym $gym): Plan
    {
        return Plan::firstOrCreate(
            ['gym_id' => $gym->id, 'name' => 'Premium'],
            [
                'duration_days' => 90,
                'price' => 149.00,
                'features' => ['Unlimited classes', 'Personal trainer', 'Progress tracking'],
                'is_active' => true,
            ]
        );
    }

    /**
     * @return array<int, GymClass>
     */
    private function createClasses(Gym $gym): array
    {
        $schedule = [
            ['Sunrise HIIT', 'Adeel Farooq', 2, 7, 14],
            ['Strength & Sculpt', 'Adeel Farooq', 3, 18, 12],
            ['Mobility & Stretch', 'Nida Rehman', 5, 8, 16],
            ['Power Yoga', 'Nida Rehman', 6, 19, 10],
        ];

        $classes = [];

        foreach ($schedule as [$name, $instructor, $daysFromNow, $hour, $capacity]) {
            $classes[] = GymClass::firstOrCreate(
                [
                    'gym_id' => $gym->id,
                    'name' => $name,
                    'start_time' => now()->addDays($daysFromNow)->setTime($hour, 0),
                ],
                [
                    'instructor_name' => $instructor,
                    'duration_minutes' => 60,
                    'capacity' => $capacity,
                    'is_active' => true,
                ]
            );
        }

        return $classes;
    }

    private function createShowcaseMember(Gym $gym, User $trainer, Plan $plan): Member
    {
        $member = Member::firstOrCreate(
            ['email' => 'zara.ahmed.demo@gmail.com'],
            [
                'gym_id' => $gym->id,
                'trainer_id' => $trainer->id,
                'name' => 'Zara Ahmed',
                'phone' => '0333-7789012',
                'height_cm' => 165,
                'password' => Hash::make('password'),
                'join_date' => now()->subDays(240)->toDateString(),
            ]
        );

        if (! $member->photo_path) {
            $response = Http::timeout(10)->get('https://randomuser.me/api/portraits/women/50.jpg');

            if ($response->successful()) {
                $path = "member-photos/demo-{$member->id}.jpg";
                Storage::disk('public')->put($path, $response->body());
                $member->update(['photo_path' => $path]);
            }
        }

        return $member;
    }

    /**
     * A few past renewal cycles plus the current active one, each with its
     * own payment, so Payment History shows more than a single row.
     */
    private function createMembershipHistory(Member $member, Plan $plan): void
    {
        if ($member->memberships()->exists()) {
            return;
        }

        $cycles = 3;
        $start = now()->subDays(240);

        for ($i = 0; $i < $cycles; $i++) {
            $cycleStart = $start->copy()->addDays($i * $plan->duration_days);
            $cycleEnd = $cycleStart->copy()->addDays($plan->duration_days);

            $membership = Membership::create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'start_date' => $cycleStart->toDateString(),
                'end_date' => $cycleEnd->toDateString(),
                'payment_status' => 'paid',
                'price_paid' => $plan->price,
            ]);

            Payment::create([
                'gym_id' => $member->gym_id,
                'membership_id' => $membership->id,
                'amount' => $plan->price,
                'method' => $i % 2 === 0 ? 'card' : 'cash',
                'paid_at' => $cycleStart->toDateString(),
            ]);
        }
    }

    /**
     * A gently improving weight/body-fat trend over several months, for the
     * Progress and BMI screens.
     */
    private function createMeasurementTrend(Member $member): void
    {
        $trend = [
            [210, 74.0, 29.0],
            [160, 72.5, 27.5],
            [110, 70.0, 25.5],
            [60, 68.5, 24.0],
            [20, 67.5, 23.0],
            [3, 67.0, 22.5],
        ];

        foreach ($trend as [$daysAgo, $weight, $bodyFat]) {
            Measurement::firstOrCreate(
                [
                    'member_id' => $member->id,
                    'recorded_at' => now()->subDays($daysAgo)->toDateString(),
                ],
                [
                    'gym_id' => $member->gym_id,
                    'weight_kg' => $weight,
                    'body_fat_percentage' => $bodyFat,
                ]
            );
        }
    }

    /**
     * A clean 7-day check-in streak plus sparser history stretching back a
     * couple of months, so the Home streak and Attendance list both look
     * like a real, long-time member rather than a brand-new account.
     */
    private function createAttendanceHistory(Member $member, Gym $gym): void
    {
        if ($member->attendances()->exists()) {
            return;
        }

        for ($daysAgo = 0; $daysAgo < 7; $daysAgo++) {
            $checkIn = now()->subDays($daysAgo)->setTime(7, random_int(0, 45));

            Attendance::create([
                'gym_id' => $gym->id,
                'member_id' => $member->id,
                'checked_in_at' => $checkIn,
                'checked_out_at' => $checkIn->copy()->addMinutes(random_int(45, 90)),
            ]);
        }

        foreach ([12, 16, 21, 28, 35, 42, 50, 58, 67, 75, 84, 95, 110, 130, 155, 180] as $daysAgo) {
            $checkIn = now()->subDays($daysAgo)->setTime(7, random_int(0, 45));

            Attendance::create([
                'gym_id' => $gym->id,
                'member_id' => $member->id,
                'checked_in_at' => $checkIn,
                'checked_out_at' => $checkIn->copy()->addMinutes(random_int(45, 90)),
            ]);
        }
    }

    /**
     * @param  array<int, GymClass>  $classes
     */
    private function createBookings(Member $member, array $classes): void
    {
        foreach (array_slice($classes, 0, 3) as $class) {
            try {
                $class->book($member);
            } catch (\DomainException) {
                // already booked
            }
        }
    }
}
