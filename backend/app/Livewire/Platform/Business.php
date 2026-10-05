<?php

namespace App\Livewire\Platform;

use App\Models\Gym;
use App\Models\PlatformActivityLog;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.platform')]
class Business extends Component
{
    public function render()
    {
        $gyms = Gym::all();

        $activeGyms = $gyms->where('subscription_status', 'active');
        $trialGyms = $gyms->where('subscription_status', 'trial');
        $suspendedGyms = $gyms->where('subscription_status', 'suspended');

        $mrr = $this->monthlyRevenue($activeGyms);

        return view('livewire.platform.business', [
            'mrr' => $mrr,
            'activeCount' => $activeGyms->count(),
            'trialCount' => $trialGyms->count(),
            'suspendedCount' => $suspendedGyms->count(),
            'conversion' => $this->conversionRate($activeGyms->count(), $suspendedGyms->count()),
            'churn' => $this->churnRate($activeGyms->count()),
            'planMix' => $this->planMix($activeGyms, $mrr),
            'signupsByMonth' => $this->signupsByMonth($gyms),
        ]);
    }

    /**
     * Converts yearly plans to a monthly figure so all plans add up to one MRR number.
     */
    private function monthlyRevenue($gyms): float
    {
        return (float) $gyms->sum(function (Gym $gym) {
            if (! $gym->plan_price) {
                return 0;
            }

            return $gym->billing_cycle === 'yearly' ? $gym->plan_price / 12 : $gym->plan_price;
        });
    }

    /**
     * Only counts gyms that have finished their trial, since trial-in-progress
     * gyms haven't decided yet.
     */
    private function conversionRate(int $activeCount, int $suspendedCount): ?int
    {
        $decided = $activeCount + $suspendedCount;

        return $decided > 0 ? (int) round(($activeCount / $decided) * 100) : null;
    }

    /**
     * Estimates churn from Stripe cancel events in the last 30 days.
     */
    private function churnRate(int $activeCount): array
    {
        $churned = PlatformActivityLog::where('action', 'gym.subscription_updated')
            ->where('description', 'like', "%is now 'canceled'%")
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        $base = $activeCount + $churned;

        return [
            'churned' => $churned,
            'rate' => $base > 0 ? round(($churned / $base) * 100, 1) : null,
        ];
    }

    private function planMix($activeGyms, float $totalMrr)
    {
        $palette = ['#3DD6D0', '#5B8DEF', '#B9862E', '#9C9080', '#C2004A', '#2F5D50'];

        return $activeGyms
            ->groupBy(fn (Gym $gym) => $gym->plan_name ?? 'No plan')
            ->map(function ($gyms, $planName) use ($totalMrr) {
                $planMrr = $this->monthlyRevenue($gyms);

                return [
                    'name' => $planName,
                    'count' => $gyms->count(),
                    'mrr' => $planMrr,
                    'share' => $totalMrr > 0 ? round(($planMrr / $totalMrr) * 100) : 0,
                ];
            })
            ->sortByDesc('mrr')
            ->values()
            ->map(fn ($row, $i) => $row + ['color' => $palette[$i % count($palette)]]);
    }

    private function signupsByMonth($gyms): array
    {
        $counts = $gyms
            ->groupBy(fn (Gym $gym) => $gym->created_at->format('Y-m'))
            ->map->count();

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'));

        return $months->mapWithKeys(fn ($ym) => [
            Carbon::createFromFormat('Y-m', $ym)->format('M') => (int) ($counts[$ym] ?? 0),
        ])->all();
    }
}
