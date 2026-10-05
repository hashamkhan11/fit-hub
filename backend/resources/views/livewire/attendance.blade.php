<div class="max-w-[1400px] mx-auto space-y-6">
    <div class="grid lg:grid-cols-5 gap-6 items-start">
        {{-- Check-in kiosk --}}
        <div x-data="qrScanner" class="lg:col-span-3 relative overflow-hidden bg-void border border-chalk-3 rounded-lg p-6 lg:p-8 motion-safe:animate-fade-up">
            <div class="absolute inset-0 opacity-[0.05] pointer-events-none"
                 style="background-image: radial-gradient(rgba(255,255,255,.5) 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="absolute -top-24 -right-16 w-72 h-72 rounded-full bg-gold/10 blur-3xl pointer-events-none"></div>

            <div class="relative flex items-start justify-between gap-4 mb-6">
                <div>
                    <p class="fh-eyebrow text-gold mb-1">Front Desk</p>
                    <h2 class="font-display font-semibold tracking-wide text-xl text-ink">Check-In Station</h2>
                </div>
                <div class="text-right shrink-0">
                    <p class="fh-eyebrow text-steel-2">In gym now</p>
                    <p class="font-mono text-3xl font-semibold text-gold tabular-nums leading-none mt-1">{{ $currentlyIn }}</p>
                </div>
            </div>

            <div class="relative max-w-sm mx-auto">
                <div class="fh-scan-frame" aria-hidden="true">
                    <span class="fh-scan-corner fh-scan-corner-tl"></span>
                    <span class="fh-scan-corner fh-scan-corner-tr"></span>
                    <span class="fh-scan-corner fh-scan-corner-bl"></span>
                    <span class="fh-scan-corner fh-scan-corner-br"></span>
                    <span class="fh-scan-line"></span>
                </div>
                <div id="qr-reader" wire:ignore></div>
            </div>

            <p class="relative mt-4 text-center fh-eyebrow text-steel-2">Scan once to check in &middot; scan again to check out</p>

            <div class="relative mt-5 max-w-sm mx-auto" style="min-height: 2.75rem;">
                @if ($lastMessage)
                    <div wire:key="scan-result-{{ \Illuminate\Support\Str::slug($lastMessage) }}-{{ $lastSuccess ? 1 : 0 }}"
                         class="flex items-center gap-2.5 justify-center rounded border px-4 py-2.5 motion-safe:animate-fade-up {{ $lastSuccess ? 'border-turf/30 bg-turf/10 text-turf' : 'border-tape/30 bg-tape/10 text-tape' }}">
                        <span class="w-2 h-2 rounded-full shrink-0 {{ $lastSuccess ? 'bg-turf' : 'bg-tape' }}"></span>
                        <span class="font-display uppercase tracking-wide text-sm">{{ $lastMessage }}</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Last activity --}}
        <div class="lg:col-span-2">
            <div class="fh-card h-full">
                <p class="fh-eyebrow mb-4">Last Activity</p>

                @if ($recent->isNotEmpty())
                    @php $latest = $recent->first(); @endphp
                    <div class="flex items-center gap-3">
                        @if ($latest->member->photo_url)
                            <img src="{{ $latest->member->photo_url }}" alt="" class="fh-avatar w-11 h-11 text-sm">
                        @else
                            <span class="fh-avatar w-11 h-11 text-sm">{{ $latest->member->initials }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="font-medium text-ink truncate">{{ $latest->member->name }}</p>
                            <p class="text-xs font-mono text-steel mt-0.5">
                                {{ $latest->checked_out_at ? 'Out at '.$latest->checked_out_at->format('H:i') : 'In at '.$latest->checked_in_at->format('H:i') }}
                            </p>
                        </div>
                        <span class="ml-auto {{ $latest->checked_out_at ? 'fh-pill-neutral' : 'fh-pill-good' }}">
                            {{ $latest->checked_out_at ? 'Out' : 'In' }}
                        </span>
                    </div>
                @else
                    <p class="text-sm text-steel">No check-ins yet today.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="fh-card-flush">
        <div class="px-5 py-4 border-b border-chalk-3">
            <p class="fh-eyebrow">Activity Log</p>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full">
            <thead>
                <tr>
                    <th class="fh-th">Member</th>
                    <th class="fh-th">Status</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Checked in</th>
                    <th class="fh-th font-mono normal-case tracking-normal">Checked out</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recent as $entry)
                    <tr class="fh-tr">
                        <td class="fh-td font-medium">
                            <div class="flex items-center gap-2.5">
                                @if ($entry->member->photo_url)
                                    <img src="{{ $entry->member->photo_url }}" alt="" class="fh-avatar w-8 h-8 text-xs">
                                @else
                                    <span class="fh-avatar w-8 h-8 text-xs">{{ $entry->member->initials }}</span>
                                @endif
                                <span>{{ $entry->member->name }}</span>
                            </div>
                        </td>
                        <td class="fh-td">
                            <span class="{{ $entry->checked_out_at ? 'fh-pill-neutral' : 'fh-pill-good' }}">
                                {{ $entry->checked_out_at ? 'Out' : 'In' }}
                            </span>
                        </td>
                        <td class="fh-td-mono">{{ $entry->checked_in_at->format('M d, H:i') }}</td>
                        <td class="fh-td-mono">{{ $entry->checked_out_at?->format('M d, H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="fh-td text-steel" colspan="4">No check-ins yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
