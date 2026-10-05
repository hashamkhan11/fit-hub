<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div class="fh-ticket">
            <p class="fh-eyebrow">Today — check-ins</p>
            <p class="fh-stat-value mt-2" x-data="countUp({{ $today['checkIns'] }})" x-text="display">{{ $today['checkIns'] }}</p>
        </div>
        <div class="fh-ticket">
            <p class="fh-eyebrow">Today — revenue collected</p>
            <p class="fh-stat-value mt-2 text-blue" x-data="countUp({{ $today['revenue'] }}, { decimals: 2 })" x-text="display">{{ number_format($today['revenue'], 2) }}</p>
        </div>
        <div class="fh-ticket">
            <p class="fh-eyebrow">Classes remaining today</p>
            <p class="fh-stat-value mt-2" x-data="countUp({{ $today['classesRemaining'] }})" x-text="display">{{ $today['classesRemaining'] }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="fh-card"
             x-data="barChart(
                {{ Js::from(array_map(fn ($d) => (int) \Illuminate\Support\Carbon::parse($d)->format('j'), array_keys($dailyCheckIns))) }},
                {{ Js::from(array_values($dailyCheckIns)) }},
                'Check-ins',
                '#3DD6D0',
                true
             )">
            <div class="flex items-center justify-between mb-4">
                <h3 class="fh-heading text-sm">Daily check-ins</h3>
                <div class="flex items-center gap-1.5">
                    <button type="button" wire:click="checkInsPrevMonth" class="fh-month-nav-btn" aria-label="Previous month">&#8249;</button>
                    <span class="fh-eyebrow">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $checkInsMonth)->format('M Y') }}</span>
                    <button type="button" wire:click="checkInsNextMonth" @disabled($checkInsMonth === now()->format('Y-m')) class="fh-month-nav-btn" aria-label="Next month">&#8250;</button>
                </div>
            </div>
            <div wire:ignore>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        <div class="fh-card"
             x-data="areaChart(
                {{ Js::from(array_map(fn ($h) => sprintf('%02d:00', $h), array_keys($peakHours))) }},
                {{ Js::from(array_values($peakHours)) }},
                '#5B8DEF'
             )">
            <div class="flex items-center justify-between mb-4">
                <h3 class="fh-heading text-sm">Peak hours</h3>
                <div class="flex items-center gap-1.5">
                    <button type="button" wire:click="peakHoursPrevMonth" class="fh-month-nav-btn" aria-label="Previous month">&#8249;</button>
                    <span class="fh-eyebrow">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m', $peakHoursMonth)->format('M Y') }}</span>
                    <button type="button" wire:click="peakHoursNextMonth" @disabled($peakHoursMonth === now()->format('Y-m')) class="fh-month-nav-btn" aria-label="Next month">&#8250;</button>
                </div>
            </div>
            <div class="h-56" wire:ignore>
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="fh-card">
            <p class="fh-eyebrow">Members</p>
            <div class="flex items-baseline gap-2 mt-2">
                <span class="fh-stat-value text-turf" x-data="countUp({{ $membershipCounts['active'] }})" x-text="display">{{ $membershipCounts['active'] }}</span>
                <span class="text-sm text-steel">active</span>
            </div>
            <div class="flex items-baseline gap-2 mt-1">
                <span class="fh-stat-value text-tape text-xl" x-data="countUp({{ $membershipCounts['expired'] }})" x-text="display">{{ $membershipCounts['expired'] }}</span>
                <span class="text-sm text-steel">expired</span>
            </div>
        </div>

        <div class="fh-card">
            <p class="fh-eyebrow">Renewal rate — last 30 days</p>
            @if ($renewalRate['rate'] === null)
                <p class="text-sm text-steel mt-3">No expirations in this window.</p>
            @else
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="fh-stat-value" x-data="countUp({{ $renewalRate['rate'] }}, { suffix: '%' })" x-text="display">{{ $renewalRate['rate'] }}%</span>
                    @if ($renewalRate['delta'] !== null && $renewalRate['delta'] != 0)
                        <span class="{{ $renewalRate['delta'] > 0 ? 'fh-stat-delta-up' : 'fh-stat-delta-down' }}">
                            {{ $renewalRate['delta'] > 0 ? '▲' : '▼' }} {{ abs($renewalRate['delta']) }}pt
                        </span>
                    @endif
                </div>
                <p class="text-sm text-steel mt-1">{{ $renewalRate['renewed'] }} of {{ $renewalRate['expired'] }} expired memberships renewed</p>
            @endif
        </div>

        <div class="fh-card">
            <p class="fh-eyebrow">Class fill rate — last 30 days</p>
            @if ($classStats['averageFillRate'] === null)
                <p class="text-sm text-steel mt-3">No classes yet.</p>
            @else
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="fh-stat-value" x-data="countUp({{ $classStats['averageFillRate'] }}, { suffix: '%' })" x-text="display">{{ $classStats['averageFillRate'] }}%</span>
                    @if ($classStats['fillRateDelta'] !== null && $classStats['fillRateDelta'] != 0)
                        <span class="{{ $classStats['fillRateDelta'] > 0 ? 'fh-stat-delta-up' : 'fh-stat-delta-down' }}">
                            {{ $classStats['fillRateDelta'] > 0 ? '▲' : '▼' }} {{ abs($classStats['fillRateDelta']) }}pt
                        </span>
                    @endif
                </div>
                <p class="text-sm text-steel mt-1">average across classes in this window</p>
            @endif
        </div>

        <div class="fh-card">
            <p class="fh-eyebrow">No-show rate — last 30 days</p>
            @if ($noShowRate['rate'] === null)
                <p class="text-sm text-steel mt-3">No past classes in this window.</p>
            @else
                <div class="flex items-baseline gap-2 mt-2">
                    <span class="fh-stat-value" x-data="countUp({{ $noShowRate['rate'] }}, { suffix: '%' })" x-text="display">{{ $noShowRate['rate'] }}%</span>
                    @if ($noShowRate['delta'] !== null && $noShowRate['delta'] != 0)
                        <span class="{{ $noShowRate['delta'] < 0 ? 'fh-stat-delta-up' : 'fh-stat-delta-down' }}">
                            {{ $noShowRate['delta'] < 0 ? '▼' : '▲' }} {{ abs($noShowRate['delta']) }}pt
                        </span>
                    @endif
                </div>
                <p class="text-sm text-steel mt-1">{{ $noShowRate['noShows'] }} of {{ $noShowRate['booked'] }} booked spots no-showed</p>
            @endif
        </div>
    </div>

    <div class="fh-card-flush p-0">
        <h3 class="fh-heading text-sm p-4 pb-0">Most popular classes</h3>
        <div class="overflow-x-auto">
        <table class="w-full mt-2">
            <thead>
                <tr>
                    <th class="fh-th">Class</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Start</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Booked</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Capacity</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classStats['mostPopular'] as $class)
                    <tr class="fh-tr">
                        <td class="fh-td font-medium">{{ $class->name }}</td>
                        <td class="fh-td-mono">{{ \Illuminate\Support\Carbon::parse($class->start_time)->format('M j, g:i A') }}</td>
                        <td class="fh-td-mono">{{ $class->booked_count }}</td>
                        <td class="fh-td-mono">{{ $class->capacity }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="4">No classes yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="fh-card"
             x-data="horizontalBarChart(
                {{ Js::from($revenueByPlan['byPlan']->pluck('name')) }},
                {{ Js::from($revenueByPlan['byPlan']->pluck('total')) }},
                {{ Js::from($planColors) }}
             )">
            <div class="flex items-start justify-between mb-4 gap-3">
                <h3 class="fh-heading text-sm">Revenue by plan</h3>
                <div class="flex items-start gap-3">
                    <div class="text-right">
                        <p class="fh-eyebrow">{{ $revenueMonth === now()->format('Y-m') ? 'This month' : \Illuminate\Support\Carbon::createFromFormat('Y-m', $revenueMonth)->format('M Y') }}</p>
                        <p class="font-mono font-semibold text-ink">{{ number_format($revenueByPlan['total'], 2) }}</p>
                    </div>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <button type="button" wire:click="revenuePrevMonth" class="fh-month-nav-btn" aria-label="Previous month">&#8249;</button>
                        <button type="button" wire:click="revenueNextMonth" @disabled($revenueMonth === now()->format('Y-m')) class="fh-month-nav-btn" aria-label="Next month">&#8250;</button>
                    </div>
                </div>
            </div>
            <div style="height: {{ max(140, $revenueByPlan['byPlan']->count() * 42) }}px">
                <div wire:ignore class="h-full">
                    <canvas x-ref="canvas"></canvas>
                </div>
            </div>
        </div>

        <div class="fh-card-flush p-0 flex flex-col">
            <h3 class="fh-heading text-sm p-4 pb-0">Outstanding balances</h3>
            <div class="overflow-x-auto">
            <table class="w-full mt-2">
                <thead>
                    <tr>
                        <th class="fh-th">Member</th>
                        <th class="fh-th">Plan</th>
                        <th class="fh-th font-mono normal-case tracking-normal">Amount</th>
                        <th class="fh-th">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($outstandingBalances as $membership)
                        <tr class="fh-tr">
                            <td class="fh-td font-medium">{{ $membership->member->name }}</td>
                            <td class="fh-td">{{ $membership->plan->name }}</td>
                            <td class="fh-td-mono">{{ number_format($membership->balance_due, 2) }}</td>
                            <td class="fh-td">
                                @if ($membership->payment_status === 'partial')
                                    <span class="fh-pill-warn">Partial</span>
                                @else
                                    <span class="fh-pill-bad">Pending</span>
                                @endif
                                @if ($membership->isOverdue())
                                    <span class="text-tape text-xs block font-mono mt-1">overdue</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="fh-td text-steel" colspan="4">No outstanding balances.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
