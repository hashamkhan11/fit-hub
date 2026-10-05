@php
    $logoFile = collect(['logo.webp', 'logo.png'])
        ->first(fn ($f) => file_exists(public_path('images/branding/'.$f)));
@endphp

@if ($logoFile)
    <img
        src="{{ asset('images/branding/'.$logoFile) }}"
        alt="FitHub"
        class="{{ $class ?? 'w-7 h-7' }} rounded object-contain"
    >
@else
    <span class="{{ $class ?? 'w-7 h-7' }} rounded bg-gradient-to-br from-gold to-gold-2 flex items-center justify-center font-display font-extrabold text-ink {{ $textClass ?? 'text-xs' }}">FH</span>
@endif
