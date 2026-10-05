<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Member;
use App\Models\Membership;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public string $checkInsWeekStart;

    public function mount(): void
    {
        Gate::authorize('view-dashboard');

        $this->checkInsWeekStart = $this->currentWeekStart();
    }

    public function checkInsPrevWeek(): void
    {
        $this->checkInsWeekStart = Carbon::parse($this->checkInsWeekStart)->subWeek()->toDateString();
        $this->dispatchCheckIns();
    }

    public function checkInsNextWeek(): void
    {
        $this->checkInsWeekStart = $this->clampToCurrentWeek($this->checkInsWeekStart);
        $this->dispatchCheckIns();
    }

    private function dispatchCheckIns(): void
    {
        $data = $this->dailyCheckInsForWeek($this->checkInsWeekStart);

        $this->dispatch(
            'weekly-checkins-updated',
            labels: array_map(fn ($d) => Carbon::parse($d)->format('D'), array_keys($data)),
            data: array_values($data),
        );
    }

    private function currentWeekStart(): string
    {
        return now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    // Can't step forward past the current week — no future data yet.
    private function clampToCurrentWeek(string $weekStart): string
    {
        $next = Carbon::parse($weekStart)->addWeek()->toDateString();

        return $next <= $this->currentWeekStart() ? $next : $weekStart;
    }

    public function render()
    {
        $membershipStatus = $this->membershipStatusCounts();
        $byPlan = $this->membersByPlan();

        return view('livewire.dashboard', [
            'totalMembers' => Member::count(),
            'activeMembersCount' => $membershipStatus['active'],
            'checkInsThisWeek' => $this->checkInsThisWeek(),
            'revenueThisWeek' => $this->revenueThisWeek(),
            'checkInsWeekEnd' => Carbon::parse($this->checkInsWeekStart)->addDays(6)->toDateString(),
            'dailyCheckIns' => $this->dailyCheckInsForWeek($this->checkInsWeekStart),
            'byPlan' => $byPlan,
            'planColors' => $this->planColors($byPlan),
            'membershipStatus' => $membershipStatus,
            'recentMembers' => $this->recentMembers(),
        ]);
    }

    private function currentWeekBounds(): array
    {
        $start = now()->startOfWeek(Carbon::MONDAY);

        return [$start, $start->copy()->endOfWeek(Carbon::SUNDAY)];
    }

    private function checkInsThisWeek(): int
    {
        [$start, $end] = $this->currentWeekBounds();

        return Attendance::whereBetween('checked_in_at', [$start, $end])->count();
    }

    private function revenueThisWeek(): float
    {
        [$start, $end] = $this->currentWeekBounds();

        return (float) Payment::whereBetween('paid_at', [$start, $end])->sum('amount');
    }

    private function dailyCheckInsForWeek(string $weekStart): array
    {
        $start = Carbon::parse($weekStart)->startOfDay();
        $end = $start->copy()->addDays(6)->endOfDay();

        $rows = Attendance::query()
            ->selectRaw('DATE(checked_in_at) as day, COUNT(*) as total')
            ->whereBetween('checked_in_at', [$start, $end])
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, 6))->mapWithKeys(function ($i) use ($start, $rows) {
            $date = $start->copy()->addDays($i)->toDateString();

            return [$date => (int) ($rows[$date] ?? 0)];
        })->all();
    }

    // Counts only members currently active on each plan, not revenue totals.
    private function membersByPlan(): Collection
    {
        return Member::query()
            ->with('latestMembership.plan')
            ->get()
            ->filter(fn (Member $member) => $member->latestMembership?->isActive())
            ->groupBy(fn (Member $member) => $member->latestMembership->plan_id)
            ->map(fn ($members) => [
                'name' => $members->first()->latestMembership->plan->name,
                'count' => $members->count(),
            ])
            ->values()
            ->sortByDesc('count')
            ->values();
    }

    // Same colors as Insight::planColors(), so a plan looks the same on both pages.
    private function planColors($byPlan): array
    {
        $palette = ['#2F5D50', '#FF2F66', '#B23A2E', '#155EA3', '#C2004A', '#9C9080'];

        return $byPlan->values()->map(fn ($plan, $i) => $palette[$i % count($palette)])->all();
    }

    /**
     * Active/Expired/Pending status — different from payment_status (billing).
     * "Pending" means the membership hasn't started yet.
     */
    private function classifyMembershipStatus(?Membership $membership): string
    {
        if ($membership === null) {
            return 'expired';
        }

        if ($membership->start_date !== null && $membership->start_date->isFuture()) {
            return 'pending';
        }

        return $membership->isActive() ? 'active' : 'expired';
    }

    private function membershipStatusCounts(): array
    {
        $counts = ['active' => 0, 'expired' => 0, 'pending' => 0];

        Member::query()->with('latestMembership')->get()->each(
            function (Member $member) use (&$counts) {
                $counts[$this->classifyMembershipStatus($member->latestMembership)]++;
            }
        );

        return $counts;
    }

    private function recentMembers(int $limit = 8)
    {
        return Member::query()
            ->with('latestMembership.plan')
            ->withCount('attendances as checkins_count')
            ->latest('join_date')
            ->take($limit)
            ->get()
            ->each(function (Member $member) {
                $member->status = $this->classifyMembershipStatus($member->latestMembership);
            });
    }
}
