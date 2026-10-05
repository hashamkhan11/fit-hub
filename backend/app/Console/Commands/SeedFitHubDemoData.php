<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Gym;
use App\Models\GymClass;
use App\Models\Measurement;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Adds demo data to the FitHub gym for demo purposes. Run with `php artisan demo:seed-fithub`.
 * Safe to run more than once.
 */
class SeedFitHubDemoData extends Command
{
    protected $signature = 'demo:seed-fithub';

    protected $description = 'Seed realistic Pakistani demo data into the FitHub gym for mobile app demoing';

    public function handle(): void
    {
        $gym = Gym::find(2);

        if (! $gym) {
            $this->error('Gym id 2 (FitHub) not found.');

            return;
        }

        $this->renameExistingTestAccounts();
        $members = $this->createMembers($gym);
        $classes = $this->createUpcomingClasses($gym);
        $this->createBookings($classes, $members);
        $this->extendAdminMeasurements();

        $this->info('Demo data seeded for gym #2 (FitHub).');
    }

    private function renameExistingTestAccounts(): void
    {
        User::where('email', 'trainer1@fithub.test')->update(['name' => 'Usman Tariq']);
        User::where('email', 'staff1@fithub.test')->update(['name' => 'Ayesha Siddiqui']);
        User::where('email', 'staff2@fithub.test')->update(['name' => 'Bilal Ahmed']);
        Member::where('email', 'test1@fithub.com')->update(['name' => 'Fatima Noor']);
        Member::where('email', 'test2@fithub.test')->update(['name' => 'Zainab Malik']);

        $this->assignPhoto(Member::where('email', 'test1@fithub.com')->first(), 'women', 28);
        $this->assignPhoto(Member::where('email', 'test2@fithub.test')->first(), 'women', 71);

        // Give the admin's demo member a trainer so the Home screen shows one.
        $trainer = User::where('email', 'trainer1@fithub.test')->first();
        if ($trainer) {
            Member::where('email', 'admin@fithub.test')->update(['trainer_id' => $trainer->id]);
        }
    }

    /**
     * Downloads a stock portrait photo for a demo member so the dashboard
     * looks realistic in screenshots. Skips if the member already has one.
     */
    private function assignPhoto(?Member $member, string $gender, int $index): void
    {
        if (! $member || $member->photo_path) {
            return;
        }

        $response = Http::timeout(10)->get("https://randomuser.me/api/portraits/{$gender}/{$index}.jpg");

        if (! $response->successful()) {
            return;
        }

        $path = "member-photos/demo-{$member->id}.jpg";
        Storage::disk('public')->put($path, $response->body());
        $member->update(['photo_path' => $path]);
    }

    /**
     * @return array<int, Member>
     */
    private function createMembers(Gym $gym): array
    {
        $roster = [
            ['Ahmed Raza', '0301', 'men', 32],
            ['Sana Malik', '0302', 'women', 33],
            ['Bilal Hussain', '0303', 'men', 45],
            ['Mahnoor Fatima', '0304', 'women', 44],
            ['Hamza Sheikh', '0305', 'men', 22],
            ['Iqra Yousaf', '0306', 'women', 21],
            ['Waqas Ahmed', '0307', 'men', 11],
            ['Aiza Khan', '0308', 'women', 12],
            ['Danish Iqbal', '0309', 'men', 52],
            ['Rabia Chaudhry', '0310', 'women', 55],
            ['Faizan Butt', '0311', 'men', 61],
            ['Hira Aslam', '0312', 'women', 65],
        ];

        $plans = Plan::where('gym_id', $gym->id)->get()->keyBy('name');
        $basic = $plans->get('Basic');
        $special = $plans->get('Special Plan');

        $created = [];

        foreach ($roster as $i => [$name, $prefix, $gender, $photoIndex]) {
            $email = strtolower(str_replace(' ', '.', $name)).'@example.pk';

            $member = Member::firstOrCreate(
                ['email' => $email],
                [
                    'gym_id' => $gym->id,
                    'name' => $name,
                    'phone' => $prefix.'-'.str_pad((string) random_int(1000000, 9999999), 7, '0'),
                    'password' => Hash::make('password'),
                    'join_date' => now()->subDays(60 - $i * 4)->toDateString(),
                ]
            );

            $this->assignPhoto($member, $gender, $photoIndex);

            if (! $member->wasRecentlyCreated) {
                $created[] = $member;

                continue;
            }

            $plan = $i % 3 === 0 ? $special : $basic;
            $startDate = now()->subDays(60 - $i * 4);
            $endDate = $startDate->copy()->addDays($plan->duration_days);

            // A few lapsed/partial-paid memberships so the data looks realistic.
            $paymentStatus = match (true) {
                $i === 2 => 'partial',
                $i === 5 => 'pending',
                default => 'paid',
            };

            if ($i === 8) {
                $endDate = now()->subDays(5); // lapsed membership
            }

            $membership = Membership::create([
                'member_id' => $member->id,
                'plan_id' => $plan->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'payment_status' => $paymentStatus,
                'price_paid' => $plan->price,
            ]);

            $amountPaid = match ($paymentStatus) {
                'paid' => $plan->price,
                'partial' => round($plan->price * 0.5, 2),
                default => 0,
            };

            if ($amountPaid > 0) {
                Payment::create([
                    'gym_id' => $gym->id,
                    'membership_id' => $membership->id,
                    'amount' => $amountPaid,
                    'method' => 'cash',
                    'paid_at' => $startDate->toDateString(),
                ]);
            }

            $created[] = $member;
        }

        return $created;
    }

    /**
     * @return array<int, GymClass>
     */
    private function createUpcomingClasses(Gym $gym): array
    {
        $schedule = [
            ['Subah Fitness Bootcamp', 'Usman Tariq', 1, 7, 15],
            ['Zumba Dance Blast', 'Ayesha Siddiqui', 2, 18, 12],
            ['Iron Circuit Strength', 'Bilal Ahmed', 3, 7, 10],
            ['Evening Yoga & Recovery', 'Sana Malik', 3, 19, 4],
            ['Boxing Fundamentals', 'Ali Raza', 4, 8, 10],
            ['Subah Fitness Bootcamp', 'Usman Tariq', 5, 7, 15],
            ['Spin & Burn', 'Hamza Sheikh', 6, 17, 10],
            ['Zumba Dance Blast', 'Ayesha Siddiqui', 8, 18, 12],
        ];

        $classes = [];

        foreach ($schedule as [$name, $instructor, $daysFromNow, $hour, $capacity]) {
            $startTime = now()->addDays($daysFromNow)->setTime($hour, 0);

            $classes[] = GymClass::firstOrCreate(
                [
                    'gym_id' => $gym->id,
                    'name' => $name,
                    'start_time' => $startTime,
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

    /**
     * @param  array<int, GymClass>  $classes
     * @param  array<int, Member>  $members
     */
    private function createBookings(array $classes, array $members): void
    {
        $admin = Member::where('email', 'admin@fithub.test')->first();

        // Book members into a few classes, not all. One class is filled up on
        // purpose so the admin's own booking shows the waitlist.
        $adminBooksIndexes = [0, 1, 3];

        foreach ($classes as $index => $class) {
            $alreadySeeded = $class->bookings()->exists();

            if (! $alreadySeeded) {
                $toBook = $members;
                shuffle($toBook);
                $inAdminSlot = in_array($index, $adminBooksIndexes, true);
                $fillCount = $inAdminSlot
                    ? min(count($toBook), $class->capacity) // fill fully so admin waitlists where relevant
                    : min(count($toBook), max(1, $class->capacity - 2));

                foreach (array_slice($toBook, 0, $fillCount) as $member) {
                    try {
                        $class->book($member);
                    } catch (\DomainException) {
                        // already booked at this time — skip
                    }
                }
            }

            if ($admin && in_array($index, $adminBooksIndexes, true)) {
                try {
                    $class->book($admin);
                } catch (\DomainException) {
                    // admin already booked this class
                }
            }
        }
    }

    private function extendAdminMeasurements(): void
    {
        $admin = Member::where('email', 'admin@fithub.test')->first();

        if (! $admin) {
            return;
        }

        $trend = [
            [20, 88.0, 24.0],
            [10, 86.5, 23.0],
            [1, 85.0, 21.5],
        ];

        foreach ($trend as [$daysAgo, $weight, $bodyFat]) {
            Measurement::firstOrCreate(
                [
                    'member_id' => $admin->id,
                    'recorded_at' => now()->subDays($daysAgo)->toDateString(),
                ],
                [
                    'gym_id' => $admin->gym_id,
                    'weight_kg' => $weight,
                    'body_fat_percentage' => $bodyFat,
                ]
            );
        }
    }
}
