<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="flex justify-end">
        <button type="button" wire:click="createNew" class="fh-btn-primary">New Class</button>
    </div>

    @if ($showForm)
        <x-fh-modal :title="$editingId ? 'Edit Class' : 'New Class'" close="resetForm">
            <form wire:submit="save" class="grid grid-cols-2 gap-4">
                <div>
                    <label class="fh-label">Name</label>
                    <input type="text" wire:model="name" class="fh-input">
                    @error('name') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Instructor</label>
                    <input type="text" wire:model="instructor_name" class="fh-input">
                    @error('instructor_name') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Start time</label>
                    <input type="datetime-local" wire:model="start_time" class="fh-input">
                    @error('start_time') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Duration (minutes)</label>
                    <input type="number" wire:model="duration_minutes" class="fh-input">
                    @error('duration_minutes') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Capacity</label>
                    <input type="number" wire:model="capacity" class="fh-input">
                    @error('capacity') <p class="fh-error">{{ $message }}</p> @enderror
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
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Instructor</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Start time</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Capacity</th>
                    <th class="fh-th font-mono normal-case tracking-normal">No-shows</th>
                    <th class="fh-th">Status</th>
                    <th class="fh-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($classes as $class)
                    <tr class="fh-tr">
                        <td class="fh-td font-medium">{{ $class->name }}</td>
                        <td class="fh-td text-steel">{{ $class->instructor_name ?? '—' }}</td>
                        <td class="fh-td-mono">{{ $class->start_time->format('D, M j g:ia') }}</td>
                        <td class="fh-td-mono">
                            {{ $class->booked_count }}/{{ $class->capacity }}
                            @if ($class->waitlisted_count > 0)
                                <span class="text-gold-3">(+{{ $class->waitlisted_count }})</span>
                            @endif
                        </td>
                        <td class="fh-td-mono">
                            @if ($class->start_time->isPast())
                                {{ $class->no_show_count }}/{{ $class->booked_count }}
                            @else
                                <span class="text-steel">—</span>
                            @endif
                        </td>
                        <td class="fh-td">
                            @if ($class->is_active)
                                <span class="fh-pill-good">Active</span>
                            @else
                                <span class="fh-pill-neutral">Inactive</span>
                            @endif
                        </td>
                        <td class="fh-td flex gap-3">
                            <button wire:click="edit({{ $class->id }})" class="fh-link-action text-gold-3">Edit</button>
                            <button type="button" x-on:click="$store.confirmModal.show({ message: 'Delete this class?', danger: true, confirmLabel: 'Delete', onConfirm: () => $wire.delete({{ $class->id }}) })" class="fh-link-action text-tape">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel text-center py-12" colspan="7">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="w-8 h-8 mx-auto mb-2 text-steel-2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3M4 11h16M5 21h14a1 1 0 001-1V7a1 1 0 00-1-1H5a1 1 0 00-1 1v13a1 1 0 001 1z"/>
                            </svg>
                            <p>No classes scheduled yet.</p>
                            <button type="button" wire:click="createNew" class="fh-link-action text-gold-3 mt-1">Schedule your first class</button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
