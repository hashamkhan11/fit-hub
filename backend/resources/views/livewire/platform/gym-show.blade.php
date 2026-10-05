<div class="max-w-[1100px] mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <a href="/ranksol/gyms" class="text-xs text-mist hover:text-ink transition">&larr; All Gyms</a>
            <h1 class="pf-heading text-2xl mt-1">{{ $gym->name }}</h1>
            <p class="text-sm text-mist">{{ $gym->email }}</p>
        </div>
        <div>
            @if ($gym->isSuspended())
                <span class="pf-pill-bad">Suspended</span>
            @elseif ($gym->isOnTrial())
                <span class="pf-pill-warn">Trial &middot; {{ $gym->trialDaysRemaining() }}d left</span>
            @else
                <span class="pf-pill-good">Active</span>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="pf-card border-teal/30 bg-teal/5 text-sm text-teal-2">{{ session('status') }}</div>
    @endif

    <div class="pf-card max-w-2xl">
        <h2 class="pf-heading text-sm mb-4">Gym Profile</h2>
        <form wire:submit="updateGymProfile" class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="pf-label">Name</label>
                <input type="text" wire:model="gym_name" class="pf-input">
                @error('gym_name') <p class="pf-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="pf-label">Email</label>
                <input type="email" wire:model="gym_email" class="pf-input">
                @error('gym_email') <p class="pf-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="pf-label">Phone</label>
                <input type="text" wire:model="gym_phone" class="pf-input">
                @error('gym_phone') <p class="pf-error">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-3">
                <button type="submit" class="pf-btn-primary">Save Profile</button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-3 gap-4">
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">Members</p>
            <p class="pf-stat-value" x-data="countUp({{ $memberCount }})" x-text="display">{{ $memberCount }}</p>
        </div>
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">Staff</p>
            <p class="pf-stat-value" x-data="countUp({{ $staffCount }})" x-text="display">{{ $staffCount }}</p>
        </div>
        <div class="pf-card">
            <p class="pf-eyebrow mb-2">Owner</p>
            <p class="text-ink text-sm mt-3">{{ $owner->name ?? '—' }}</p>
            <p class="text-xs text-mist">{{ $owner->email ?? '' }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="pf-card">
            <h2 class="pf-heading text-sm mb-4">Plan</h2>
            <form wire:submit="updatePlan" class="space-y-4">
                <div>
                    <label class="pf-label">Catalog Plan</label>
                    <select wire:model="subscription_plan_id" class="pf-input">
                        @forelse ($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }} — ${{ $plan->monthly_price }}/mo</option>
                        @empty
                            <option value="">No plans in the catalog yet</option>
                        @endforelse
                    </select>
                    @error('subscription_plan_id') <p class="pf-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="pf-label">Billing Cycle</label>
                    <select wire:model="billing_cycle" class="pf-input">
                        <option value="monthly">Monthly</option>
                        <option value="yearly">Yearly</option>
                    </select>
                </div>
                <button type="submit" class="pf-btn-primary">Save Plan</button>
            </form>

            <div class="border-t border-ink/10 mt-6 pt-6">
                <h2 class="pf-heading text-sm mb-3">Stripe Subscription</h2>
                @if ($subscription)
                    <p class="text-sm text-ink">Status: <span class="font-mono">{{ $subscription->stripe_status }}</span></p>
                    @if ($gym->pm_type)
                        <p class="text-sm text-mist mt-1">Card: {{ ucfirst($gym->pm_type) }} &middot;&middot;&middot;&middot; {{ $gym->pm_last_four }}</p>
                    @endif
                @else
                    <p class="text-sm text-mist">No Stripe subscription yet — the gym owner subscribes from their own dashboard's Billing page.</p>
                @endif
            </div>

            <div class="border-t border-ink/10 mt-6 pt-6 space-y-3">
                <h2 class="pf-heading text-sm">Account Actions</h2>
                @error('resend') <p class="pf-error">{{ $message }}</p> @enderror

                <button type="button" x-on:click="$store.confirmModal.show({ message: 'Reset the owner\'s password and resend the welcome email?', confirmLabel: 'Resend', onConfirm: () => $wire.resendWelcome() })" class="pf-btn-secondary w-full">
                    Resend Welcome Email
                </button>

                @if ($gym->isSuspended())
                    <button type="button" x-on:click="$store.confirmModal.show({ message: 'Reactivate this gym? Owner/staff/members will regain access.', confirmLabel: 'Reactivate', onConfirm: () => $wire.activate() })" class="pf-btn-primary w-full">
                        Reactivate Gym
                    </button>
                @else
                    <div class="space-y-2">
                        <input type="text" wire:model="suspend_reason" placeholder="Reason for suspension…" class="pf-input">
                        @error('suspend_reason') <p class="pf-error">{{ $message }}</p> @enderror
                        <button type="button" x-on:click="$store.confirmModal.show({ message: 'Suspend this gym? Owner/staff/members will be locked out immediately.', danger: true, confirmLabel: 'Suspend', onConfirm: () => $wire.suspend() })" class="pf-btn-danger w-full">
                            Suspend Gym
                        </button>
                    </div>
                @endif
            </div>
        </div>

        <div class="pf-card">
            <h2 class="pf-heading text-sm mb-4">Gym Activity (RankSol-visible)</h2>
            <div class="space-y-3">
                @forelse ($activity as $log)
                    <div>
                        <p class="text-sm text-ink">{{ $log->description }}</p>
                        <p class="text-xs text-mist">{{ $log->platformAdmin?->name ?? 'System' }} &middot; {{ $log->created_at->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-mist">No platform activity for this gym yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
