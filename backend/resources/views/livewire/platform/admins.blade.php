<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div>
            <p class="pf-eyebrow">RankSol Platform</p>
            <h1 class="pf-heading text-2xl">Admins</h1>
        </div>
        <button type="button" wire:click="createNew" class="pf-btn-primary">+ New Admin</button>
    </div>

    @if (session('status'))
        <div class="pf-card border-teal/30 bg-teal/5 text-sm text-teal-2">{{ session('status') }}</div>
    @endif

    @if (session('error'))
        <div class="pf-card border-tape/30 bg-tape/5 text-sm text-tape">{{ session('error') }}</div>
    @endif

    @if ($showForm)
        <x-fh-modal :title="$editingId ? 'Edit Admin' : 'New Admin'" close="resetForm">
            <form wire:submit="save" class="grid grid-cols-2 gap-4">
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
                    <label class="fh-label">Password {{ $editingId ? '(leave blank to keep unchanged)' : '' }}</label>
                    <input type="password" wire:model="password" class="fh-input">
                    @error('password') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="fh-label">Role</label>
                    <select wire:model="role" class="fh-input">
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                    @error('role') <p class="fh-error">{{ $message }}</p> @enderror
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

    <div class="pf-card p-0 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="pf-th">Name</th>
                    <th class="pf-th">Email</th>
                    <th class="pf-th">Role</th>
                    <th class="pf-th">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admins as $admin)
                    <tr class="pf-tr" wire:key="admin-{{ $admin->id }}">
                        <td class="pf-td font-medium">
                            {{ $admin->name }}
                            @if ($admin->id === auth('platform')->id())
                                <span class="pf-pill-good ml-1">You</span>
                            @endif
                        </td>
                        <td class="pf-td text-mist">{{ $admin->email }}</td>
                        <td class="pf-td">{{ $admin->role === 'super_admin' ? 'Super Admin' : 'Admin' }}</td>
                        <td class="pf-td flex gap-3">
                            <button type="button" wire:click="edit({{ $admin->id }})" class="fh-link-action text-gold-3">Edit</button>

                            @if ($admin->id !== auth('platform')->id())
                                <button
                                    type="button"
                                    x-on:click="$store.confirmModal.show({ message: 'Delete admin account ' + @js($admin->name) + '?', danger: true, confirmLabel: 'Delete', onConfirm: () => $wire.delete({{ $admin->id }}) })"
                                    class="fh-link-action text-tape"
                                >Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="pf-td text-mist" colspan="4">No admin accounts yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
