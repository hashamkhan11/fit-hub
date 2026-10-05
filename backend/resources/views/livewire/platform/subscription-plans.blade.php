<div class="max-w-[1200px] mx-auto space-y-6">
    <div>
        <h1 class="pf-heading text-2xl">Subscription Plans</h1>
        <p class="text-sm text-mist mt-1">RankSol's own sellable tiers — what gyms pay to use FitHub.</p>
    </div>

    @if (session('status'))
        <div class="pf-card border-teal/30 bg-teal/5 text-sm text-teal-2">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="pf-card border-tape/30 bg-tape/5 text-sm text-tape">{{ session('error') }}</div>
    @endif
    @error('sync') <div class="pf-card border-tape/30 bg-tape/5 text-sm text-tape">{{ $message }}</div> @enderror

    <div class="pf-card max-w-2xl">
        <h2 class="pf-heading text-sm mb-4">{{ $editingId ? 'Edit Plan' : 'New Plan' }}</h2>

        <form wire:submit="save" class="grid grid-cols-2 gap-4">
            <div class="col-span-2">
                <label class="pf-label">Name</label>
                <input type="text" wire:model="name" class="pf-input">
                @error('name') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <label class="pf-label">Description</label>
                <input type="text" wire:model="description" class="pf-input" placeholder="Short one-line pitch for this tier">
                @error('description') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Monthly Price (USD)</label>
                <input type="text" wire:model="monthly_price" class="pf-input">
                @error('monthly_price') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Yearly Price (USD)</label>
                <input type="text" wire:model="yearly_price" class="pf-input" placeholder="Optional">
                @error('yearly_price') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Member Limit</label>
                <input type="text" wire:model="member_limit" class="pf-input" placeholder="Blank = unlimited">
                @error('member_limit') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="pf-label">Staff Limit</label>
                <input type="text" wire:model="staff_limit" class="pf-input" placeholder="Blank = unlimited">
                @error('staff_limit') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <label class="pf-label">Features (one per line)</label>
                <textarea wire:model="features" rows="4" class="pf-input" placeholder="Unlimited classes&#10;SMS reminders&#10;Lock/access control"></textarea>
                @error('features') <p class="pf-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="is_active" id="is_active" class="accent-teal">
                <label for="is_active" class="text-sm text-ink">Active (visible to gyms)</label>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" wire:model="has_hardware_access" id="has_hardware_access" class="accent-teal">
                <label for="has_hardware_access" class="text-sm text-ink">Includes lock/fingerprint hardware</label>
            </div>

            <div class="col-span-2 flex gap-2">
                <button type="submit" class="pf-btn-primary">
                    {{ $editingId ? 'Update' : 'Create' }}
                </button>

                @if ($editingId)
                    <button type="button" wire:click="resetForm" class="pf-btn-secondary">
                        Cancel
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div class="pf-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="pf-th">Name</th>
                    <th class="pf-th">Monthly</th>
                    <th class="pf-th">Yearly</th>
                    <th class="pf-th">Stripe</th>
                    <th class="pf-th">Hardware</th>
                    <th class="pf-th">Status</th>
                    <th class="pf-th">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr class="pf-tr" wire:key="plan-{{ $plan->id }}">
                        <td class="pf-td font-medium">{{ $plan->name }}</td>
                        <td class="pf-td-mono">${{ $plan->monthly_price }}</td>
                        <td class="pf-td-mono">{{ $plan->yearly_price ? '$'.$plan->yearly_price : '—' }}</td>
                        <td class="pf-td">
                            @if ($plan->isSyncedToStripe())
                                <span class="pf-pill-good">Synced</span>
                            @else
                                <span class="pf-pill-neutral">Not synced</span>
                            @endif
                        </td>
                        <td class="pf-td">
                            @if ($plan->has_hardware_access)
                                <span class="pf-pill-good">Included</span>
                            @else
                                <span class="pf-pill-neutral">Software only</span>
                            @endif
                        </td>
                        <td class="pf-td">
                            @if ($plan->is_active)
                                <span class="pf-pill-good">Active</span>
                            @else
                                <span class="pf-pill-neutral">Inactive</span>
                            @endif
                        </td>
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
                                    aria-label="Actions for {{ $plan->name }}"
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
                                        <button type="button" wire:click="edit({{ $plan->id }})" @click="open = false" class="fh-menu-item">Edit</button>

                                        <button type="button" wire:click="syncToStripe({{ $plan->id }})" wire:loading.attr="disabled" @click="open = false" class="fh-menu-item">
                                            {{ $plan->isSyncedToStripe() ? 'Re-sync to Stripe' : 'Sync to Stripe' }}
                                        </button>

                                        <div class="border-t border-chalk-3 my-1"></div>

                                        <button
                                            type="button"
                                            x-on:click="open = false; $store.confirmModal.show({
                                                message: 'Permanently delete the ' + @js($plan->name) + ' plan? This cannot be undone.',
                                                danger: true,
                                                confirmLabel: 'Delete',
                                                onConfirm: () => $wire.delete({{ $plan->id }})
                                            })"
                                            class="fh-menu-item text-tape hover:bg-tape/5"
                                        >Delete Plan</button>
                                    </div>
                                </template>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="pf-td text-mist" colspan="6">No subscription plans yet — create one above.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
