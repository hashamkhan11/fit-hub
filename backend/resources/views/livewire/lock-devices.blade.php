<div class="max-w-[1400px] mx-auto space-y-6">
    @if ($revealedToken)
        <div class="fh-card max-w-2xl border-2 border-gold-2">
            <h2 class="fh-heading mb-2">Device Token — copy this now</h2>
            <p class="text-sm text-steel mb-4">
                This is shown only once. Paste it into the device's WiFi setup screen. If you lose it, come back here and rotate the token to get a new one.
            </p>
            <div class="fh-input font-mono text-sm select-all break-all bg-void/40">{{ $revealedToken }}</div>
            <button wire:click="dismissToken" class="fh-btn-secondary mt-4">Done, I've saved it</button>
        </div>
    @endif

    @if ($canManage)
        <div class="fh-card max-w-2xl">
            <h2 class="fh-heading mb-4">Add Lock Device</h2>

            <form wire:submit="addDevice" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="fh-label">Name</label>
                    <input type="text" wire:model="name" class="fh-input" placeholder="e.g. Front Door">
                    @error('name') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="fh-btn-primary">Register</button>
            </form>
        </div>
    @endif

    <div class="fh-card-flush">
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Status</th>
                    <th class="fh-th">Last Seen</th>
                    <th class="fh-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($devices as $device)
                    <tr class="fh-tr">
                        <td class="fh-td font-medium">{{ $device->name }}</td>
                        <td class="fh-td">
                            @if ($device->is_online)
                                <span class="fh-pill-good">Online</span>
                            @else
                                <span class="fh-pill-neutral">Offline</span>
                            @endif
                            @if ($device->pending_count > 0)
                                <span class="fh-pill-neutral ml-1">{{ $device->pending_count }} pending</span>
                            @endif
                        </td>
                        <td class="fh-td-mono">
                            {{ $device->last_seen_at?->diffForHumans() ?? 'Never' }}
                        </td>
                        <td class="fh-td flex gap-3">
                            <button type="button" x-on:click="$store.confirmModal.show({ message: 'Unlock ' + @js($device->name) + '?', confirmLabel: 'Unlock', onConfirm: () => $wire.triggerUnlock({{ $device->id }}) })" class="fh-link-action text-gold-3">Unlock</button>
                            @if ($canManage)
                                <button type="button" x-on:click="$store.confirmModal.show({ message: 'Rotate token for ' + @js($device->name) + '? The old token will stop working immediately.', danger: true, confirmLabel: 'Rotate', onConfirm: () => $wire.regenerateToken({{ $device->id }}) })" class="fh-link-action">Rotate Token</button>
                                <button type="button" x-on:click="$store.confirmModal.show({ message: 'Remove ' + @js($device->name) + '?', danger: true, confirmLabel: 'Remove', onConfirm: () => $wire.deleteDevice({{ $device->id }}) })" class="fh-link-action text-tape">Delete</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="4">No lock devices registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
