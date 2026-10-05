@props(['title', 'close', 'wide' => false])

<div
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-void/70"
    wire:click="{{ $close }}"
    x-data
    x-on:keydown.escape.window="$wire.{{ $close }}()"
>
    <div x-on:click.stop class="fh-card shadow-2xl w-full {{ $wide ? 'max-w-3xl' : 'max-w-lg' }} max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between gap-4 mb-4">
            <h2 class="fh-heading">{{ $title }}</h2>
            <button type="button" wire:click="{{ $close }}" class="text-steel hover:text-ink transition text-2xl leading-none" aria-label="Close">&times;</button>
        </div>

        {{ $slot }}
    </div>
</div>
