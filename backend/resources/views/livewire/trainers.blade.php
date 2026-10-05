<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card max-w-2xl">
        <h2 class="fh-heading mb-4">Add Trainer</h2>

        <form wire:submit="addTrainer" class="grid grid-cols-2 gap-4">
            <div>
                <label class="fh-label">Name</label>
                <input type="text" wire:model="name" class="fh-input">
                @error('name') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Email</label>
                <input type="email" wire:model="email" class="fh-input">
                @error('email') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="fh-label">Password</label>
                <input type="password" wire:model="password" class="fh-input">
                @error('password') <p class="fh-error">{{ $message }}</p> @enderror
            </div>

            <div class="col-span-2">
                <button type="submit" class="fh-btn-primary">
                    Add Trainer
                </button>
            </div>
        </form>
    </div>

    @if ($editingTrainerId)
        <div class="fh-card max-w-2xl">
            <h2 class="fh-heading mb-4">Edit Trainer</h2>

            <form wire:submit="updateTrainer" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="fh-label">Name</label>
                    <input type="text" wire:model="edit_name" class="fh-input">
                    @error('edit_name') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Email</label>
                    <input type="email" wire:model="edit_email" class="fh-input">
                    @error('edit_email') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div class="col-span-2 flex gap-2">
                    <button type="submit" class="fh-btn-primary">Save Changes</button>
                    <button type="button" wire:click="cancelEdit" class="fh-btn-secondary">Cancel</button>
                </div>
            </form>
        </div>
    @endif

    <div class="fh-card-flush">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Email</th>
                    <th class="fh-th">Members Assigned</th>
                    <th class="fh-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($trainers as $trainer)
                    <tr class="fh-tr">
                        <td class="fh-td font-medium">{{ $trainer->name }}</td>
                        <td class="fh-td text-steel">{{ $trainer->email }}</td>
                        <td class="fh-td-mono">{{ $trainer->members_count }}</td>
                        <td class="fh-td flex gap-3">
                            <button wire:click="startEdit({{ $trainer->id }})" class="fh-link-action text-gold-3">Edit</button>
                            <button
                                type="button"
                                x-on:click="$store.confirmModal.show({ message: 'Delete ' + @js($trainer->name) + '? Members assigned to them will become unassigned.', danger: true, confirmLabel: 'Delete', onConfirm: () => $wire.deleteTrainer({{ $trainer->id }}) })"
                                class="fh-link-action text-tape"
                            >Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="4">No trainers yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
