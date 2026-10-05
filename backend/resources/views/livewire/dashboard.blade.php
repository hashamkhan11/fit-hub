<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <p class="fh-eyebrow">Overview</p>
            <h2 class="fh-heading text-xl mt-1">Welcome back, {{ auth()->user()->name }}</h2>
        </div>
        <a href="/dashboard/members" class="fh-btn-primary">+ Add Member</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
        <div class="fh-ticket">
            <p class="fh-eyebrow">Total Members</p>
            <p class="fh-stat-value mt-2" x-data="countUp({{ $totalMembers }})" x-text="display">{{ $totalMembers }}</p>
        </div>
        <div class="fh-ticket">
            <p class="fh-eyebrow">Active Members</p>
            <p class="fh-stat-value mt-2 text-turf" x-data="countUp({{ $activeMembersCount }})" x-text="display">{{ $activeMembersCount }}</p>
        </div>
        <div class="fh-ticket">
            <p class="fh-eyebrow">Check-ins — this week</p>
            <p class="fh-stat-value mt-2" x-data="countUp({{ $checkInsThisWeek }})" x-text="display">{{ $checkInsThisWeek }}</p>
        </div>
        <div class="fh-ticket">
            <p class="fh-eyebrow">Revenue — this week</p>
            <p class="fh-stat-value mt-2 text-gold" x-data="countUp({{ $revenueThisWeek }}, { decimals: 2 })" x-text="display">{{ number_format($revenueThisWeek, 2) }}</p>
        </div>
    </div>

    <div class="fh-card"
         x-data="areaChart(
            {{ Js::from(array_map(fn ($d) => \Illuminate\Support\Carbon::parse($d)->format('D'), array_keys($dailyCheckIns))) }},
            {{ Js::from(array_values($dailyCheckIns)) }},
            '#3DD6D0',
            'weekly-checkins-updated'
         )">
        <div class="flex items-center justify-between mb-4">
            <h3 class="fh-heading text-sm">Check-ins Overview</h3>
            <div class="flex items-center gap-1.5">
                <button type="button" wire:click="checkInsPrevWeek" class="fh-month-nav-btn" aria-label="Previous week">&#8249;</button>
                <span class="fh-eyebrow">
                    {{ \Illuminate\Support\Carbon::parse($checkInsWeekStart)->format('M j') }} – {{ \Illuminate\Support\Carbon::parse($checkInsWeekEnd)->format('M j') }}
                </span>
                <button type="button" wire:click="checkInsNextWeek"
                        @disabled($checkInsWeekStart === now()->startOfWeek(\Illuminate\Support\Carbon::MONDAY)->toDateString())
                        class="fh-month-nav-btn" aria-label="Next week">&#8250;</button>
            </div>
        </div>
        <div class="h-56" wire:ignore>
            <canvas x-ref="canvas"></canvas>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="fh-card" x-data="donutChart(
            {{ Js::from($byPlan->pluck('name')) }},
            {{ Js::from($byPlan->pluck('count')) }},
            {{ Js::from($planColors) }}
        )">
            <h3 class="fh-heading text-sm mb-4">Members by Plan</h3>
            <div class="relative h-56">
                <div wire:ignore class="h-full"><canvas x-ref="canvas"></canvas></div>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="fh-stat-value text-2xl" x-data="countUp({{ $byPlan->sum('count') }})" x-text="display">{{ $byPlan->sum('count') }}</span>
                    <span class="fh-eyebrow">Active</span>
                </div>
            </div>
        </div>

        <div class="fh-card" x-data="donutChart(
            ['Active', 'Expired', 'Pending'],
            {{ Js::from([$membershipStatus['active'], $membershipStatus['expired'], $membershipStatus['pending']]) }},
            ['#4ADE80', '#FB6B6B', '#F5B94D']
        )">
            <h3 class="fh-heading text-sm mb-4">Membership Status</h3>
            <div class="relative h-56">
                <div wire:ignore class="h-full"><canvas x-ref="canvas"></canvas></div>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                    <span class="fh-stat-value text-2xl" x-data="countUp({{ array_sum($membershipStatus) }})" x-text="display">{{ array_sum($membershipStatus) }}</span>
                    <span class="fh-eyebrow">Total</span>
                </div>
            </div>
        </div>
    </div>

    <div class="fh-card-flush p-0 overflow-hidden">
        <div class="flex items-center justify-between p-4 pb-0">
            <h3 class="fh-heading text-sm">Recent Members</h3>
            <a href="/dashboard/members" class="fh-link-action text-gold">View all &rarr;</a>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full mt-2">
            <thead>
                <tr>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Plan</th>
                    <th class="fh-th">Join Date</th>
                    <th class="fh-th">Status</th>
                    <th class="fh-th">Check-ins</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentMembers as $member)
                    <tr class="fh-tr" wire:key="recent-member-{{ $member->id }}">
                        <td class="fh-td font-medium max-w-[180px]">
                            <div class="flex items-center gap-2.5">
                                @if ($member->photo_url)
                                    <img src="{{ $member->photo_url }}" alt="" class="fh-avatar w-8 h-8 text-xs">
                                @else
                                    <span class="fh-avatar w-8 h-8 text-xs">{{ $member->initials }}</span>
                                @endif
                                <span class="truncate" title="{{ $member->name }}">{{ $member->name }}</span>
                            </div>
                        </td>
                        <td class="fh-td">{{ $member->latestMembership?->plan?->name ?? '—' }}</td>
                        <td class="fh-td-mono">{{ $member->join_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="fh-td">
                            @if ($member->status === 'active')
                                <span class="fh-pill-good">Active</span>
                            @elseif ($member->status === 'pending')
                                <span class="fh-pill-warn">Pending</span>
                            @else
                                <span class="fh-pill-bad">Expired</span>
                            @endif
                        </td>
                        <td class="fh-td-mono">{{ $member->checkins_count }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="5">No members enrolled yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
