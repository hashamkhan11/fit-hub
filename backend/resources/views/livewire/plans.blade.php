<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="flex justify-end">
        <button type="button" wire:click="createNew" class="fh-btn-primary">New Plan</button>
    </div>

    @if ($showForm)
        <x-fh-modal :title="$editingId ? 'Edit Plan' : 'New Plan'" close="resetForm">
            <form wire:submit="save" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="fh-label">Name</label>
                    <input type="text" wire:model="name" class="fh-input">
                    @error('name') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Duration (days)</label>
                    <input type="number" wire:model="duration_days" class="fh-input">
                    @error('duration_days') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Price</label>
                    <input type="text" wire:model="price" class="fh-input">
                    @error('price') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-2 mt-6">
                    <input type="checkbox" wire:model="is_active" id="is_active" class="accent-gold-2">
                    <label for="is_active" class="text-sm">Active</label>
                </div>

                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="fh-btn-primary">
                        {{ $editingId ? 'Update' : 'Create' }}
                    </button>

                    <button type="button" wire:click="resetForm" class="fh-btn-secondary">
                        Cancel
                    </button>
                </div>
            </form>
        </x-fh-modal>
    @endif

    <div class="fh-card-flush">
        @error('deletePlan')
            <p class="fh-error px-4 pt-4">{{ $message }}</p>
        @enderror
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Duration</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Price</th>
                    <th class="fh-th">Status</th>
                    <th class="fh-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($plans as $plan)
                    <tr class="fh-tr">
                        <td class="fh-td font-medium">{{ $plan->name }}</td>
                        <td class="fh-td-mono">{{ $plan->duration_days }}d</td>
                        <td class="fh-td-mono">{{ $plan->price }}</td>
                        <td class="fh-td">
                            @if ($plan->is_active)
                                <span class="fh-pill-good">Active</span>
                            @else
                                <span class="fh-pill-neutral">Inactive</span>
                            @endif
                        </td>
                        <td class="fh-td flex gap-3">
                            <button wire:click="edit({{ $plan->id }})" class="fh-link-action text-gold-3">Edit</button>
                            <button type="button" x-on:click="$store.confirmModal.show({ message: 'Delete this plan?', danger: true, confirmLabel: 'Delete', onConfirm: () => $wire.delete({{ $plan->id }}) })" class="fh-link-action text-tape">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel text-center py-12" colspan="5">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-8 h-8 mx-auto mb-2 text-steel-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1M5 6h14a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2z"/>
                            </svg>
                            <p>No plans yet.</p>
                            <button type="button" wire:click="createNew" class="fh-link-action text-gold-3 mt-1">Create your first plan</button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
