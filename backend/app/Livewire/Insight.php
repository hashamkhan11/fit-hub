<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\GymClass;
use App\Models\Membership;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Insight extends Component
{
    // Each chart moves through months on its own, starting at the current month.
    public string $checkInsMonth;

    public string $peakHoursMonth;

    public string $revenueMonth;

    public function mount(): void
    {
        Gate::authorize('view-insight');

        $current = now()->format('Y-m');
        $this->checkInsMonth = $current;
        $this->peakHoursMonth = $current;
        $this->revenueMonth = $current;
    }

    public function checkInsPrevMonth(): void
    {
        $this->checkInsMonth = Carbon::createFromFormat('Y-m', $this->checkInsMonth)->subMonth()->format('Y-m');
        $this->dispatchCheckIns();
    }

    public function checkInsNextMonth(): void
    {
        $this->checkInsMonth = $this->clampToCurrentMonth($this->checkInsMonth);
        $this->dispatchCheckIns();
    }

    public function peakHoursPrevMonth(): void
    {
        $this->peakHoursMonth = Carbon::createFromFormat('Y-m', $this->peakHoursMonth)->subMonth()->format('Y-m');
        $this->dispatchPeakHours();
    }

    public function peakHoursNextMonth(): void
    {
        $this->peakHoursMonth = $this->clampToCurrentMonth($this->peakHoursMonth);
        $this->dispatchPeakHours();
    }

    public function revenuePrevMonth(): void
    {
        $this->revenueMonth = Carbon::createFromFormat('Y-m', $this->revenueMonth)->subMonth()->format('Y-m');
        $this->dispatchRevenue();
    }

    public function revenueNextMonth(): void
    {
        $this->revenueMonth = $this->clampToCurrentMonth($this->revenueMonth);
        $this->dispatchRevenue();
    }

    // Charts are built once and kept alive, not rebuilt each time you change
    // months — new data is just pushed into the existing chart (see app.js).
    private function dispatchCheckIns(): void
    {
        $gymId = auth()->user()->gym_id;
        $data = $this->dailyCheckIns($gymId, $this->checkInsMonth);

        $this->dispatch(
            'daily-checkins-updated',
            labels: array_map(fn ($d) => (int) Carbon::parse($d)->format('j'), array_keys($data)),
            data: array_values($data),
        );
    }

    private function dispatchPeakHours(): void
    {
        $gymId = auth()->user()->gym_id;
        $data = $this->peakHours($gymId, $this->peakHoursMonth);

        $this->dispatch(
            'peak-hours-updated',
            labels: array_map(fn ($h) => sprintf('%02d:00', $h), array_keys($data)),
            data: array_values($data),
        );
    }

    private function dispatchRevenue(): void
    {
        $gymId = auth()->user()->gym_id;
        $result = $this->revenueByPlan($gymId, $this->revenueMonth);

        $this->dispatch(
            'revenue-by-plan-updated',
            labels: $result['byPlan']->pluck('name')->all(),
            data: $result['byPlan']->pluck('total')->all(),
            colors: $this->planColors($result['byPlan']),
        );
    }

    // One brand color per plan, so the revenue-by-plan chart bars look distinct.
    private function planColors($byPlan): array
    {
        $palette = ['#2F5D50', '#FF2F66', '#B23A2E', '#155EA3', '#C2004A', '#9C9080'];

        return $byPlan->values()->map(fn ($plan, $i) => $palette[$i % count($palette)])->all();
    }

    // Can't step forward past the current month — no future data yet.
    private function clampToCurrentMonth(string $ym): string
    {
        $next = Carbon::createFromFormat('Y-m', $ym)->addMonth()->format('Y-m');

        return $next <= now()->format('Y-m') ? $next : $ym;
    }

    public function render()
    {
        $gymId = auth()->user()->gym_id;
        $revenueByPlan = $this->revenueByPlan($gymId, $this->revenueMonth);

        return view('livewire.insight', [
            'today' => $this->today($gymId),
            'dailyCheckIns' => $this->dailyCheckIns($gymId, $this->checkInsMonth),
            'peakHours' => $this->peakHours($gymId, $this->peakHoursMonth),
            'membershipCounts' => $this->membershipCounts($gymId),
            'renewalRate' => $this->renewalRateWithDelta($gymId),
            'classStats' => $this->classStats($gymId),
            'noShowRate' => $this->noShowRateWithDelta($gymId),
            'revenueByPlan' => $revenueByPlan,
            'planColors' => $this->planColors($revenueByPlan['byPlan']),
            'outstandingBalances' => $this->outstandingBalances($gymId),
        ]);
    }

    private function today(int $gymId): array
    {
        return [
            'checkIns' => Attendance::where('gym_id', $gymId)->whereDate('checked_in_at', today())->count(),
            'revenue' => (float) DB::table('payments')->where('gym_id', $gymId)->whereDate('paid_at', today())->sum('amount'),
            'classesRemaining' => GymClass::where('gym_id', $gymId)
                ->whereDate('start_time', today())
                ->where('start_time', '>=', now())
                ->count(),
        ];
    }

    private function dailyCheckIns(int $gymId, string $ym): array
    {
        $start = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = DB::table('attendances')
            ->selectRaw('DATE(checked_in_at) as day, COUNT(*) as total')
            ->where('gym_id', $gymId)
            ->whereBetween('checked_in_at', [$start, $end])
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $days = collect(range(0, $start->daysInMonth - 1))->map(fn ($i) => $start->copy()->addDays($i)->toDateString());

        return $days->mapWithKeys(fn ($day) => [$day => (int) ($rows[$day] ?? 0)])->all();
    }

    private function peakHours(int $gymId, string $ym): array
    {
        $start = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = DB::table('attendances')
            ->selectRaw('HOUR(checked_in_at) as hour, COUNT(*) as total')
            ->where('gym_id', $gymId)
            ->whereBetween('checked_in_at', [$start, $end])
            ->groupBy('hour')
            ->pluck('total', 'hour');

        return collect(range(0, 23))->mapWithKeys(fn ($hour) => [$hour => (int) ($rows[$hour] ?? 0)])->all();
    }

    private function membershipCounts(int $gymId): array
    {
        $latestPerMember = Membership::query()
            ->whereIn('id', function ($query) use ($gymId) {
                $query->selectRaw('MAX(memberships.id)')
                    ->from('memberships')
                    ->join('members', 'members.id', '=', 'memberships.member_id')
                    ->where('members.gym_id', $gymId)
                    ->groupBy('memberships.member_id');
            })
            ->get();

        return [
            'active' => $latestPerMember->filter->isActive()->count(),
            'expired' => $latestPerMember->reject->isActive()->count(),
        ];
    }

    private function renewalRateWithDelta(int $gymId): array
    {
        $current = $this->renewalRateForWindow($gymId, 30, 0);
        $previous = $this->renewalRateForWindow($gymId, 60, 30);

        $current['delta'] = ($current['rate'] !== null && $previous['rate'] !== null)
            ? $current['rate'] - $previous['rate']
            : null;

        return $current;
    }

    private function renewalRateForWindow(int $gymId, int $startDaysAgo, int $endDaysAgo): array
    {
        $expiredInWindow = Membership::query()
            ->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('members.gym_id', $gymId)
            ->whereBetween('memberships.end_date', [
                now()->subDays($startDaysAgo)->toDateString(),
                now()->subDays($endDaysAgo)->toDateString(),
            ])
            ->select('memberships.*')
            ->get();

        $laterStartsByMember = Membership::query()
            ->whereIn('member_id', $expiredInWindow->pluck('member_id')->unique())
            ->get(['member_id', 'start_date'])
            ->groupBy('member_id');

        $renewed = $expiredInWindow->filter(function ($membership) use ($laterStartsByMember) {
            return ($laterStartsByMember->get($membership->member_id) ?? collect())
                ->contains(fn ($m) => $m->start_date >= $membership->end_date);
        })->count();

        $expiredCount = $expiredInWindow->count();

        return [
            'expired' => $expiredCount,
            'renewed' => $renewed,
            'rate' => $expiredCount > 0 ? round(($renewed / $expiredCount) * 100) : null,
        ];
    }

    private function classStats(int $gymId): array
    {
        $classes = DB::table('gym_classes')
            ->leftJoin('bookings', function ($join) {
                $join->on('bookings.gym_class_id', '=', 'gym_classes.id')
                    ->where('bookings.status', '=', 'booked');
            })
            ->where('gym_classes.gym_id', $gymId)
            ->groupBy('gym_classes.id', 'gym_classes.name', 'gym_classes.capacity', 'gym_classes.start_time')
            ->select('gym_classes.id', 'gym_classes.name', 'gym_classes.capacity', 'gym_classes.start_time')
            ->selectRaw('COUNT(bookings.id) as booked_count')
            ->orderByDesc('booked_count')
            ->get();

        $fillRates = $classes->map(fn ($class) => $class->capacity > 0
            ? round(($class->booked_count / $class->capacity) * 100)
            : 0);

        // Fill rate only looks at the last 30 days, so it's comparable over time.
        $currentFillRate = $this->averageFillRateForWindow($gymId, 30, 0);
        $previousFillRate = $this->averageFillRateForWindow($gymId, 60, 30);

        return [
            'averageFillRate' => $currentFillRate,
            'fillRateDelta' => ($currentFillRate !== null && $previousFillRate !== null)
                ? $currentFillRate - $previousFillRate
                : null,
            'mostPopular' => $classes->take(5),
        ];
    }

    private function averageFillRateForWindow(int $gymId, int $startDaysAgo, int $endDaysAgo): ?float
    {
        $classes = DB::table('gym_classes')
            ->leftJoin('bookings', function ($join) {
                $join->on('bookings.gym_class_id', '=', 'gym_classes.id')
                    ->where('bookings.status', '=', 'booked');
            })
            ->where('gym_classes.gym_id', $gymId)
            ->whereBetween('gym_classes.start_time', [now()->subDays($startDaysAgo), now()->subDays($endDaysAgo)])
            ->groupBy('gym_classes.id', 'gym_classes.capacity')
            ->select('gym_classes.capacity')
            ->selectRaw('COUNT(bookings.id) as booked_count')
            ->get();

        if ($classes->isEmpty()) {
            return null;
        }

        $fillRates = $classes->map(fn ($class) => $class->capacity > 0
            ? ($class->booked_count / $class->capacity) * 100
            : 0);

        return round($fillRates->avg());
    }

    private function noShowRateWithDelta(int $gymId): array
    {
        $current = $this->noShowRateForWindow($gymId, 30, 0);
        $previous = $this->noShowRateForWindow($gymId, 60, 30);

        $current['delta'] = ($current['rate'] !== null && $previous['rate'] !== null)
            ? $current['rate'] - $previous['rate']
            : null;

        return $current;
    }

    /**
     * A booked member counts as attended if they checked in that day (no per-class scan).
     */
    private function noShowRateForWindow(int $gymId, int $startDaysAgo, int $endDaysAgo): array
    {
        $classes = GymClass::where('gym_id', $gymId)
            ->whereBetween('start_time', [now()->subDays($startDaysAgo), now()->subDays($endDaysAgo)])
            ->with(['bookings' => fn ($query) => $query->where('status', 'booked')])
            ->get();

        $memberIds = $classes->flatMap->bookings->pluck('member_id')->unique();

        $attendedDatesByMember = Attendance::where('gym_id', $gymId)
            ->whereIn('member_id', $memberIds)
            ->get()
            ->groupBy('member_id')
            ->map(fn ($rows) => $rows->pluck('checked_in_at')->map->toDateString()->unique());

        $booked = 0;
        $noShows = 0;

        foreach ($classes as $class) {
            $classDate = $class->start_time->toDateString();

            foreach ($class->bookings as $booking) {
                $booked++;

                if (! $attendedDatesByMember->get($booking->member_id, collect())->contains($classDate)) {
                    $noShows++;
                }
            }
        }

        return [
            'booked' => $booked,
            'noShows' => $noShows,
            'rate' => $booked > 0 ? round(($noShows / $booked) * 100) : null,
        ];
    }

    private function revenueByPlan(int $gymId, string $ym): array
    {
        $start = Carbon::createFromFormat('Y-m', $ym)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $rows = DB::table('payments')
            ->join('memberships', 'memberships.id', '=', 'payments.membership_id')
            ->join('plans', 'plans.id', '=', 'memberships.plan_id')
            ->where('payments.gym_id', $gymId)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->groupBy('plans.id', 'plans.name')
            ->select('plans.name')
            ->selectRaw('SUM(payments.amount) as total')
            ->orderByDesc('total')
            ->get();

        return [
            'byPlan' => $rows,
            'total' => (float) $rows->sum('total'),
        ];
    }

    private function outstandingBalances(int $gymId)
    {
        return Membership::query()
            ->join('members', 'members.id', '=', 'memberships.member_id')
            ->where('members.gym_id', $gymId)
            ->whereIn('memberships.payment_status', ['pending', 'partial'])
            ->select('memberships.*')
            ->with('member', 'plan')
            ->orderBy('memberships.end_date')
            ->get();
    }
}
