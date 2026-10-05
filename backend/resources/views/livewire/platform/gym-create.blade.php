<div class="max-w-2xl mx-auto space-y-6">
    <div>
        <p class="pf-eyebrow">RankSol Platform</p>
        <h1 class="pf-heading text-2xl">Onboard a New Gym</h1>
    </div>

    <form wire:submit="save" class="pf-card space-y-6">
        <div>
            <h2 class="pf-heading text-sm mb-3">Gym Details</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="pf-label">Gym Name</label>
                    <input type="text" wire:model="gym_name" class="pf-input">
                    @error('gym_name') <p class="pf-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="pf-label">Gym Email</label>
                    <input type="email" wire:model="gym_email" class="pf-input">
                    @error('gym_email') <p class="pf-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="pf-label">Gym Phone</label>
                    <input type="text" wire:model="gym_phone" class="pf-input">
                    @error('gym_phone') <p class="pf-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="border-t border-ink/10 pt-6">
            <h2 class="pf-heading text-sm mb-3">Owner Account</h2>
            <p class="text-xs text-mist mb-3">A temporary password is generated and emailed to the owner automatically.</p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="pf-label">Owner Name</label>
                    <input type="text" wire:model="owner_name" class="pf-input">
                    @error('owner_name') <p class="pf-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="pf-label">Owner Email</label>
                    <input type="email" wire:model="owner_email" class="pf-input">
                    @error('owner_email') <p class="pf-error">{{ $message }}</p> @enderror
                </div>
            </div>
        </div>

        <div class="border-t border-ink/10 pt-6">
            <h2 class="pf-heading text-sm mb-3">Subscription</h2>
            <p class="text-xs text-mist mb-3">The gym owner completes payment themselves via the Billing page in their dashboard once created.</p>
            <div class="grid grid-cols-2 gap-4">
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
                <div>
                    <label class="pf-label">Starting Status</label>
                    <select wire:model="subscription_status" class="pf-input">
                        <option value="trial">Trial</option>
                        <option value="active">Active</option>
                    </select>
                </div>
                @if ($subscription_status === 'trial')
                    <div>
                        <label class="pf-label">Trial Length (days)</label>
                        <input type="number" wire:model="trial_days" class="pf-input">
                        @error('trial_days') <p class="pf-error">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="pf-btn-primary" wire:loading.attr="disabled">Create Gym & Send Welcome Email</button>
            <a href="/ranksol/gyms" class="pf-btn-secondary">Cancel</a>
        </div>
    </form>
</div>
