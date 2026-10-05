<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <p class="pf-eyebrow">RankSol Platform</p>
            <h1 class="pf-heading text-2xl">Gyms</h1>
        </div>
        <a href="/ranksol/gyms/new" class="pf-btn-primary">+ New Gym</a>
    </div>

    @if (session('status'))
        <div class="pf-card border-teal/30 bg-teal/5 text-sm text-teal-2">{{ session('status') }}</div>
    @endif

    <div class="flex gap-3 flex-wrap">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search by name or email…" class="pf-input max-w-xs">
        <select wire:model.live="statusFilter" class="pf-input max-w-[10rem]">
            <option value="">All statuses</option>
            <option value="trial">Trial</option>
            <option value="active">Active</option>
            <option value="suspended">Suspended</option>
        </select>
    </div>

    <div class="pf-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="pf-th">Gym</th>
                    <th class="pf-th">Plan</th>
                    <th class="pf-th">Members</th>
                    <th class="pf-th">Status</th>
                    <th class="pf-th">Joined</th>
                    <th class="pf-th">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($gyms as $gym)
                    <tr class="pf-tr" wire:key="gym-{{ $gym->id }}">
                        <td class="pf-td">
                            <a href="/ranksol/gyms/{{ $gym->id }}" class="text-ink font-medium hover:underline">{{ $gym->name }}</a>
                            <p class="text-xs text-mist">{{ $gym->email }}</p>
                        </td>
                        <td class="pf-td">{{ $gym->plan_name ?? '—' }}</td>
                        <td class="pf-td-mono">{{ $gym->members_count }}</td>
                        <td class="pf-td">
                            @if ($gym->isSuspended())
                                <span class="pf-pill-bad">Suspended</span>
                            @elseif ($gym->isOnTrial())
                                <span class="pf-pill-warn">Trial &middot; {{ $gym->trialDaysRemaining() }}d left</span>
                            @else
                                <span class="pf-pill-good">Active</span>
                            @endif
                        </td>
                        <td class="pf-td-mono">{{ $gym->created_at->format('M j, Y') }}</td>
                        <td class="pf-td text-right">
                            <div
                                x-data="{
                                    open: false,
                                    menuStyle: '',
                                    toggle() {
                                        if (this.open) { this.open = false; return }
                                        const r = this.$refs.trigger.getBoundingClientRect()
                                        const w = 224
                                        const spaceBelow = window.innerHeight - r.bottom
                                        const openUp = spaceBelow < 300 && r.top > 300
                                        const left = Math.min(r.right - w, window.innerWidth - w - 8)
                                        this.menuStyle = `left:${left}px;` + (openUp ? `bottom:${window.innerHeight - r.top + 6}px;` : `top:${r.bottom + 6}px;`)
                                        this.open = true
                                    }
                                }"
                                @click.window="open && !$refs.trigger.contains($event.target) && !($refs.menu && $refs.menu.contains($event.target)) && (open = false)"
                                @scroll.window="open = false"
                                @resize.window="open = false"
                            >
                                <button
                                    x-ref="trigger"
                                    @click="toggle()"
                                    type="button"
                                    aria-haspopup="true"
                                    :aria-expanded="open"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded text-steel hover:bg-chalk hover:text-ink transition"
                                    aria-label="Actions for {{ $gym->name }}"
                                >
                                    <svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                                        <circle cx="12" cy="5" r="1.8" />
                                        <circle cx="12" cy="12" r="1.8" />
                                        <circle cx="12" cy="19" r="1.8" />
                                    </svg>
                                </button>

                                <template x-teleport="body">
                                    <div
                                        x-ref="menu"
                                        x-show="open"
                                        x-cloak
                                        :style="menuStyle"
                                        style="display: none;"
                                        class="fixed z-50 w-56 rounded border border-chalk-3 bg-chalk-2 shadow-xl shadow-void/40 py-1.5"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        @keydown.escape.window="open = false"
                                    >
                                        <a href="/ranksol/gyms/{{ $gym->id }}" @click="open = false" class="fh-menu-item">View Details</a>

                                        @if ($gym->isSuspended())
                                            <button
                                                type="button"
                                                x-on:click="open = false; $store.confirmModal.show({ message: 'Reactivate ' + @js($gym->name) + '? Owner/staff/members will regain access.', confirmLabel: 'Reactivate', onConfirm: () => $wire.activate({{ $gym->id }}) })"
                                                class="fh-menu-item"
                                            >Reactivate Gym</button>
                                        @else
                                            <button
                                                type="button"
                                                x-on:click="open = false; $store.confirmModal.show({
                                                    message: 'Suspend ' + @js($gym->name) + '? Owner/staff/members will lose access immediately.',
                                                    danger: true,
                                                    confirmLabel: 'Suspend',
                                                    input: true,
                                                    inputLabel: 'Reason for suspending',
                                                    inputRequired: true,
                                                    onConfirm: (reason) => $wire.suspend({{ $gym->id }}, reason)
                                                })"
                                                class="fh-menu-item"
                                            >Suspend Gym</button>
                                        @endif

                                        <div class="border-t border-chalk-3 my-1"></div>

                                        <button
                                            type="button"
                                            x-on:click="open = false; $store.confirmModal.show({
                                                message: 'Permanently delete ' + @js($gym->name) + '? This removes all its members, staff, classes, bookings, and payment history. This cannot be undone.',
                                                danger: true,
                                                confirmLabel: 'Delete',
                                                onConfirm: () => $wire.deleteGym({{ $gym->id }})
                                            })"
                                            class="fh-menu-item text-tape hover:bg-tape/5"
                                        >Delete Gym</button>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="pf-td text-mist" colspan="6">No gyms match this filter.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    {{ $gyms->links('platform.pagination') }}
</div>
