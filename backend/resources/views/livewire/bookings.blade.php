<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="fh-card max-w-2xl">
        <label class="fh-label">Class</label>
        <select wire:change="selectClass($event.target.value)" class="fh-input">
            @foreach ($classes as $class)
                <option value="{{ $class->id }}" @selected($classId === $class->id)>
                    {{ $class->name }} — {{ $class->start_time->format('D, M j g:ia') }}
                </option>
            @endforeach
        </select>
    </div>

    @if ($selectedClass)
        <div class="fh-card max-w-2xl">
            <h2 class="fh-heading mb-4">Add booking to {{ $selectedClass->name }}</h2>

            <form wire:submit="addBooking" class="flex gap-3 items-start">
                <div class="flex-1">
                    <select wire:model="memberId" class="fh-input">
                        <option value="">Select member…</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                    @error('memberId') <p class="fh-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="fh-btn-primary">Book</button>
            </form>
        </div>

        <div class="fh-card-flush">
            <div class="px-5 py-4 border-b border-chalk-3 flex items-center justify-between gap-4">
                <div>
                    <p class="fh-eyebrow">Bookings</p>
                    <p class="font-medium text-ink">
                        {{ $selectedClass->name }}
                        <span class="text-steel font-normal">— {{ $selectedClass->start_time->format('D, M j g:ia') }}</span>
                    </p>
                </div>
                <span class="fh-pill-neutral shrink-0">
                    {{ $bookings->where('status', 'booked')->count() }}/{{ $selectedClass->capacity }} booked
                </span>
            </div>
            <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr>
                        <th class="fh-th">Member</th>
                        <th class="fh-th">Status</th>
                        <th class="fh-th"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr class="fh-tr">
                            <td class="fh-td font-medium">{{ $booking->member->name }}</td>
                            <td class="fh-td">
                                @if ($booking->status === 'booked')
                                    <span class="fh-pill-good">Booked</span>
                                @else
                                    <span class="fh-pill-warn">Waitlisted</span>
                                @endif
                            </td>
                            <td class="fh-td">
                                <button type="button" x-on:click="$store.confirmModal.show({ message: 'Cancel this booking?', danger: true, confirmLabel: 'Cancel Booking', onConfirm: () => $wire.cancelBooking({{ $booking->id }}) })" class="fh-link-action text-tape">
                                    Cancel
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="fh-td text-steel" colspan="3">No bookings yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    @else
        <p class="text-steel">No classes yet — create one on the Classes page first.</p>
    @endif
</div>
