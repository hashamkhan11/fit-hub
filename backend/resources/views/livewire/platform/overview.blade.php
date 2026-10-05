<div class="max-w-[1400px] mx-auto space-y-6">
    <div>
        <p class="pf-eyebrow">RankSol Platform</p>
        <h1 class="pf-heading text-2xl">Overview</h1>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">Total Gyms</p>
            <p class="pf-stat-value" x-data="countUp({{ $totalGyms }})" x-text="display">{{ $totalGyms }}</p>
        </div>
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">Active</p>
            <p class="pf-stat-value text-teal" x-data="countUp({{ $activeGyms }})" x-text="display">{{ $activeGyms }}</p>
        </div>
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">On Trial</p>
            <p class="pf-stat-value text-[#B9862E]" x-data="countUp({{ $trialGyms }})" x-text="display">{{ $trialGyms }}</p>
        </div>
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">Suspended</p>
            <p class="pf-stat-value text-tape" x-data="countUp({{ $suspendedGyms }})" x-text="display">{{ $suspendedGyms }}</p>
        </div>
    </div>

    <div class="pf-card max-w-xs">
        <p class="pf-eyebrow mb-2">Estimated MRR</p>
        <p class="pf-stat-value" x-data="countUp({{ $mrr }}, { decimals: 2, prefix: '$' })" x-text="display">${{ number_format($mrr, 2) }}</p>
        <p class="text-xs text-mist mt-1">Manually tracked — no payment gateway yet</p>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="pf-card">
            <h2 class="pf-heading text-sm mb-4">Recent Signups</h2>
            <div class="space-y-3">
                @forelse ($recentGyms as $gym)
                    <a href="/ranksol/gyms/{{ $gym->id }}" class="flex items-center justify-between hover:bg-ink/5 -mx-2 px-2 py-1.5 rounded transition">
                        <div class="min-w-0">
                            <p class="text-sm text-ink truncate">{{ $gym->name }}</p>
                            <p class="text-xs text-mist">{{ $gym->created_at->format('M j, Y') }}</p>
                        </div>
                        @if ($gym->isSuspended())
                            <span class="pf-pill-bad">Suspended</span>
                        @elseif ($gym->isOnTrial())
                            <span class="pf-pill-warn">Trial</span>
                        @else
                            <span class="pf-pill-good">Active</span>
                        @endif
                    </a>
                @empty
                    <p class="text-sm text-mist">No gyms yet.</p>
                @endforelse
            </div>
        </div>

        <div class="pf-card">
            <h2 class="pf-heading text-sm mb-4">Recent Platform Activity</h2>
            <div class="space-y-3">
                @forelse ($recentActivity as $log)
                    <div>
                        <p class="text-sm text-ink">{{ $log->description }}</p>
                        <p class="text-xs text-mist">
                            {{ $log->platformAdmin?->name ?? 'System' }}
                            @if ($log->gym) &middot; {{ $log->gym->name }} @endif
                            &middot; {{ $log->created_at->diffForHumans() }}
                        </p>
                    </div>
                @empty
                    <p class="text-sm text-mist">No activity yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
