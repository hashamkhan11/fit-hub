<div class="max-w-[1400px] mx-auto space-y-6">
    <div>
        <p class="pf-eyebrow">RankSol Platform</p>
        <h1 class="pf-heading text-2xl">Business</h1>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">MRR</p>
            <p class="pf-stat-value" x-data="countUp({{ $mrr }}, { decimals: 2, prefix: '$' })" x-text="display">${{ number_format($mrr, 2) }}</p>
            <p class="text-xs text-mist mt-1">{{ $activeCount }} paying {{ \Illuminate\Support\Str::plural('gym', $activeCount) }}</p>
        </div>
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">Trial &rarr; Paid conversion</p>
            @if ($conversion === null)
                <p class="pf-stat-value text-mist">&mdash;</p>
                <p class="text-xs text-mist mt-1">No trials have finished yet</p>
            @else
                <p class="pf-stat-value text-teal" x-data="countUp({{ $conversion }}, { suffix: '%' })" x-text="display">{{ $conversion }}%</p>
                <p class="text-xs text-mist mt-1">of gyms that finished their trial</p>
            @endif
        </div>
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">Churn — last 30 days</p>
            @if ($churn['rate'] === null)
                <p class="pf-stat-value text-mist">&mdash;</p>
                <p class="text-xs text-mist mt-1">No paying gyms yet</p>
            @else
                <p class="pf-stat-value {{ $churn['rate'] > 0 ? 'text-tape' : '' }}" x-data="countUp({{ $churn['rate'] }}, { suffix: '%' })" x-text="display">{{ $churn['rate'] }}%</p>
                <p class="text-xs text-mist mt-1">{{ $churn['churned'] }} canceled subscription{{ $churn['churned'] === 1 ? '' : 's' }}</p>
            @endif
        </div>
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">On trial now</p>
            <p class="pf-stat-value text-[#B9862E]" x-data="countUp({{ $trialCount }})" x-text="display">{{ $trialCount }}</p>
            <p class="text-xs text-mist mt-1">{{ $suspendedCount }} suspended</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="pf-card"
             x-data="barChart(
                {{ Js::from(array_keys($signupsByMonth)) }},
                {{ Js::from(array_values($signupsByMonth)) }},
                'Signups',
                '#3DD6D0',
                true
             )">
            <h3 class="pf-heading text-sm mb-4">Signups by month</h3>
            <div wire:ignore>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div class="pf-card"
             x-data="horizontalBarChart(
                {{ Js::from($planMix->pluck('name')) }},
                {{ Js::from($planMix->pluck('mrr')) }},
                {{ Js::from($planMix->pluck('color')) }}
             )">
            <h3 class="pf-heading text-sm mb-4">MRR by plan</h3>
            <div style="height: {{ max(140, $planMix->count() * 42) }}px">
                <div wire:ignore class="h-full">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="pf-card">
        <h3 class="pf-heading text-sm mb-4">Plan mix</h3>
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="pf-th">Plan</th>
                    <th class="pf-th font-mono normal-case tracking-normal">Paying gyms</th>
                    <th class="pf-th font-mono normal-case tracking-normal">MRR</th>
                    <th class="pf-th font-mono normal-case tracking-normal">Share of MRR</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($planMix as $row)
                    <tr class="pf-tr">
                        <td class="pf-td font-medium">{{ $row['name'] }}</td>
                        <td class="pf-td-mono">{{ $row['count'] }}</td>
                        <td class="pf-td-mono">${{ number_format($row['mrr'], 2) }}</td>
                        <td class="pf-td-mono">{{ $row['share'] }}%</td>
                    </tr>
                @empty
                    <tr>
                        <td class="pf-td text-mist" colspan="4">No paying gyms yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
