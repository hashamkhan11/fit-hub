<div class="max-w-[1400px] mx-auto space-y-6" @if($activeCommand) wire:poll.2s @endif>
    @error('enroll')
        <div class="fh-card border-2 border-tape">
            <p class="text-sm text-tape">{{ $message }}</p>
        </div>
    @enderror

    @if ($activeCommand)
        <div @class([
            'fh-card max-w-2xl border-2',
            'border-gold-2' => in_array($activeCommand->status, ['pending', 'in_progress']),
            'border-turf' => $activeCommand->status === 'completed',
            'border-tape' => in_array($activeCommand->status, ['failed', 'expired']),
        ])>
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="fh-heading mb-1">
                        {{ $activeCommand->action === 'enroll' ? 'Enrolling fingerprint' : 'Removing fingerprint' }}
                    </h2>

                    @if (in_array($activeCommand->status, ['pending', 'in_progress']))
                        <p class="text-sm text-steel">
                            {{ $activeCommand->progress_message ?? 'Waiting for the device to pick this up...' }}
                        </p>
                    @elseif ($activeCommand->status === 'completed')
                        <p class="text-sm text-turf">Done.</p>
                    @elseif ($activeCommand->status === 'expired')
                        <p class="text-sm text-tape">Timed out waiting for the device to pick this up.</p>
                    @else
                        <p class="text-sm text-tape">{{ $activeCommand->progress_message ?? 'Failed.' }}</p>
                    @endif
                </div>

                @if (in_array($activeCommand->status, ['completed', 'failed', 'expired']))
                    <button wire:click="dismissActive" class="fh-btn-secondary shrink-0">Dismiss</button>
                @endif
            </div>
        </div>
    @endif

    @if ($enrollPickerMemberId)
        <x-fh-modal title="Choose a scanner" close="closeEnrollPicker">
            <p class="text-sm text-steel mb-4">Which lock device's fingerprint scanner should this member enroll on?</p>
            <div class="flex flex-col gap-2">
                @foreach ($devices as $device)
                    <button
                        wire:click="confirmEnroll({{ $enrollPickerMemberId }}, {{ $device->id }})"
                        class="fh-btn-secondary text-left flex items-center justify-between"
                    >
                        <span>{{ $device->name }}</span>
                        @if ($device->is_online)
                            <span class="fh-pill-good">Online</span>
                        @else
                            <span class="fh-pill-neutral">Offline</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </x-fh-modal>
    @endif

    <div class="fh-card-flush">
        <div class="p-4 border-b border-white/5">
            <input type="text" wire:model.live.debounce.300ms="search" class="fh-input max-w-xs" placeholder="Search members...">
        </div>

        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Name</th>
                    <th class="fh-th">Fingerprint</th>
                    <th class="fh-th"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($members as $member)
                    <tr class="fh-tr">
                        <td class="fh-td font-medium">{{ $member->name }}</td>
                        <td class="fh-td">
                            @if ($member->fingerprint_id)
                                <span class="fh-pill-good">Enrolled #{{ $member->fingerprint_id }}</span>
                                @if ($member->fingerprintDevice)
                                    <span class="text-steel text-xs ml-1">on {{ $member->fingerprintDevice->name }}</span>
                                @endif
                            @else
                                <span class="fh-pill-neutral">Not enrolled</span>
                            @endif
                        </td>
                        <td class="fh-td flex gap-3">
                            @if ($member->fingerprint_id)
                                <button type="button" x-on:click="$store.confirmModal.show({ message: 'Remove ' + @js($member->name) + '\'s fingerprint?', danger: true, confirmLabel: 'Remove', onConfirm: () => $wire.removeFingerprint({{ $member->id }}) })" class="fh-link-action text-tape">Remove</button>
                            @else
                                <button wire:click="openEnrollPicker({{ $member->id }})" class="fh-link-action text-gold-3">Enroll</button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="3">No members found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
