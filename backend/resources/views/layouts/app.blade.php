<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        @if (file_exists(public_path('images/branding/logo.png')))
            <link rel="icon" type="image/png" href="{{ asset('images/branding/logo.png') }}">
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-void min-h-screen font-sans text-ink lg:flex overflow-x-hidden">
        @php
            $navIndex = 0;
            $groups = [
                'Front Desk' => [
                    'members' => ['Members', '/dashboard/members'],
                    'attendance' => ['Attendance', '/dashboard/attendance'],
                    'classes' => ['Classes', '/dashboard/classes'],
                    'bookings' => ['Bookings', '/dashboard/bookings'],
                    'trainers' => ['Trainers', '/dashboard/trainers'],
                    'lock-devices' => ['Lock', '/dashboard/lock-devices'],
                    'fingerprints' => ['Fingerprints', '/dashboard/fingerprints'],
                ],
                'Business' => [
                    'plans' => ['Plans', '/dashboard/plans'],
                    'payments' => ['Payments', '/dashboard/payments'],
                    'billing' => ['Billing', '/dashboard/billing'],
                    'insight' => ['Insight', '/dashboard/insight'],
                    'staff' => ['Staff', '/dashboard/staff'],
                    'activity' => ['Activity', '/dashboard/activity'],
                    'settings' => ['Settings', '/dashboard/settings'],
                ],
            ];

            $icons = [
                'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
                'plans' => '<path d="M12.586 3H7a2 2 0 0 0-2 2v5.586a1 1 0 0 0 .293.707l8.414 8.414a2 2 0 0 0 2.828 0l5.586-5.586a2 2 0 0 0 0-2.828l-8.414-8.414A1 1 0 0 0 12.586 3Z"/><circle cx="9" cy="9" r="1.3" fill="currentColor" stroke="none"/>',
                'members' => '<path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="9.5" cy="7" r="3.5"/><path d="M21 19v-1a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                'trainers' => '<path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="9" cy="7" r="4"/><path d="m17 11 2 2 4-4"/>',
                'attendance' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/><path d="m9 16 2 2 4-4"/>',
                'classes' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M16 3v4M8 3v4M3 11h18"/><path d="M8 15h.01M12 15h.01M16 15h.01M8 18h.01M12 18h.01"/>',
                'bookings' => '<path d="M6 4a2 2 0 0 0-2 2v14l8-5 8 5V6a2 2 0 0 0-2-2H6Z"/>',
                'insight' => '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6" rx="0.5"/><rect x="13" y="8" width="3" height="10" rx="0.5"/><rect x="18.5" y="5" width="3" height="13" rx="0.5"/>',
                'staff' => '<path d="M12 3 4 6v6c0 5 3.5 8.5 8 9 4.5-.5 8-4 8-9V6l-8-3Z"/>',
                'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
                'lock-devices' => '<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
                'fingerprints' => '<path d="M12 3a9 9 0 0 1 9 9c0 1.8-.3 3.4-1 5"/><path d="M3 12a9 9 0 0 1 3.5-7.1"/><path d="M12 6a6 6 0 0 1 6 6c0 2-.4 3.6-1 5"/><path d="M6 8.5A6 6 0 0 0 12 18"/><path d="M12 9a3 3 0 0 1 3 3c0 2.5-.8 4-1.8 5.3"/><path d="M9.5 11a2.5 2.5 0 0 1 2.5-2"/><path d="M9 15.5c1 1 1.6 1.7 2 2.5"/>',
                'payments' => '<rect x="2" y="6" width="20" height="13" rx="2"/><path d="M2 10h20"/><path d="M6 15h4"/>',
                'billing' => '<path d="M20 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2Z"/><path d="M2 11h20"/><path d="M6 3h12"/>',
                'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>',
            ];

            $visibleKeys = match (auth()->user()->role) {
                'owner' => null,
                'staff' => ['members', 'attendance', 'classes', 'bookings', 'trainers', 'lock-devices', 'plans', 'payments'],
                'trainer' => ['members', 'attendance', 'classes', 'bookings'],
                default => [],
            };

            if ($visibleKeys !== null) {
                foreach ($groups as $groupName => $items) {
                    $groups[$groupName] = collect($items)->only($visibleKeys)->all();

                    if (empty($groups[$groupName])) {
                        unset($groups[$groupName]);
                    }
                }
            }

            if (! auth()->user()->gym?->hasHardwareAccess()) {
                foreach ($groups as $groupName => $items) {
                    $groups[$groupName] = collect($items)->except(['lock-devices', 'fingerprints'])->all();

                    if (empty($groups[$groupName])) {
                        unset($groups[$groupName]);
                    }
                }
            }

            $allItems = collect($groups)->collapse();
            $currentKey = $allItems->keys()->first(fn ($key) => request()->is('dashboard/'.$key.'*'));
            $currentSection = collect($groups)->first(fn ($items) => isset($items[$currentKey]));
            $currentLabel = $currentKey && $currentSection ? $currentSection[$currentKey][0] : null;
            $currentGroupName = collect($groups)->search(fn ($items) => isset($items[$currentKey]));

            $dashboardActive = request()->is('dashboard');
            if ($dashboardActive) {
                $currentLabel = 'Dashboard';
                $currentGroupName = 'Overview';
            }
        @endphp

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-4 lg:left-4 lg:rounded-xl bg-chalk/80 backdrop-blur-xl border border-chalk-3 shadow-fh-card z-20">
            <div class="flex items-center gap-3 px-6 h-16 border-b border-chalk-3 shrink-0">
                @include('partials.logo')
                <span class="flex items-center gap-2 font-mono font-semibold text-ink tracking-wide text-sm">
                    <span class="w-1.5 h-1.5 rounded-full bg-gold shadow-[0_0_8px_theme(colors.gold.DEFAULT)]" aria-hidden="true"></span>
                    FIT<span class="text-gold">HUB</span>
                </span>
            </div>

            <nav class="flex-1 overflow-y-auto no-scrollbar py-5 px-3 space-y-6">
                <div class="space-y-0.5">
                    @php $navIndex++; @endphp
                    <a href="/dashboard"
                       class="fh-nav-link {{ $dashboardActive ? 'fh-nav-link-active' : '' }}">
                        <span class="font-mono text-[10px] {{ $dashboardActive ? 'text-gold/60' : 'text-steel-2/60' }} w-4 shrink-0">{{ str_pad($navIndex, 2, '0', STR_PAD_LEFT) }}</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px] shrink-0">
                            {!! $icons['dashboard'] !!}
                        </svg>
                        Dashboard
                    </a>
                </div>

                @foreach ($groups as $groupName => $items)
                    <div>
                        <p class="px-3 mb-2 text-[11px] font-mono uppercase tracking-[0.15em] text-steel-2">{{ $groupName }}</p>
                        <div class="space-y-0.5">
                            @foreach ($items as $key => [$label, $href])
                                @php $active = request()->is('dashboard/'.$key.'*'); $navIndex++; @endphp
                                <a href="{{ $href }}"
                                   class="fh-nav-link {{ $active ? 'fh-nav-link-active' : '' }}">
                                    <span class="font-mono text-[10px] {{ $active ? 'text-gold/60' : 'text-steel-2/60' }} w-4 shrink-0">{{ str_pad($navIndex, 2, '0', STR_PAD_LEFT) }}</span>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px] shrink-0">
                                        {!! $icons[$key] !!}
                                    </svg>
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>

            <div class="p-4 border-t border-chalk-3 shrink-0">
                <a href="/account" class="flex items-center gap-3 px-2 mb-3 rounded hover:bg-chalk transition py-1 -mx-1">
                    <div class="w-8 h-8 rounded-full bg-gold-soft flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-gold">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-ink text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                        <p class="text-steel-2 text-xs font-mono uppercase truncate">{{ auth()->user()->role }}</p>
                    </div>
                </a>
                <form method="POST" action="/logout">
                    @csrf
                    <button type="submit" class="w-full text-left font-mono uppercase text-xs tracking-wide text-steel-2 hover:text-tape transition px-2">Log out</button>
                </form>
                <a href="mailto:{{ config('app.support_email') }}" class="block mt-3 px-2 text-[11px] text-steel-2 hover:text-ink transition">Need help? {{ config('app.support_email') }}</a>
            </div>
        </aside>

        {{-- Mobile top bar --}}
        <div class="lg:hidden sticky top-0 z-20 bg-void border-b border-chalk-3">
            <div class="px-4 h-14 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    @include('partials.logo')
                    <span class="flex items-center gap-2 font-mono font-semibold text-ink tracking-wide text-sm">
                        <span class="w-1.5 h-1.5 rounded-full bg-gold shadow-[0_0_8px_theme(colors.gold.DEFAULT)]" aria-hidden="true"></span>
                        FIT<span class="text-gold">HUB</span>
                    </span>
                </div>
                <div class="flex items-center gap-3">
                    <a href="/account" aria-label="Account"
                       class="w-8 h-8 rounded-full border border-chalk-3 flex items-center justify-center shrink-0 transition {{ request()->is('account') ? 'bg-gold-soft' : 'bg-chalk' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 {{ request()->is('account') ? 'text-gold' : 'text-ink' }}">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
                        </svg>
                    </a>
                    <form method="POST" action="/logout">
                        @csrf
                        <button type="submit" class="font-mono uppercase text-xs tracking-wide text-steel-2 hover:text-ink transition">Log out</button>
                    </form>
                </div>
            </div>
            <nav class="flex overflow-x-auto px-2 pb-2 gap-1 no-scrollbar">
                <a href="/dashboard"
                   class="fh-nav-pill {{ $dashboardActive ? 'fh-nav-pill-active' : '' }}">
                    Dashboard
                </a>
                @foreach ($allItems as $key => [$label, $href])
                    <a href="{{ $href }}"
                       class="fh-nav-pill {{ request()->is('dashboard/'.$key.'*') ? 'fh-nav-pill-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Main content --}}
        <div class="flex-1 lg:pl-72 min-w-0">
            @if (session('status'))
                <div class="px-6 lg:px-8 pt-6">
                    <div class="rounded border border-turf/25 bg-turf/10 px-4 py-3 text-sm text-ink">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            @if (! auth()->user()->hasVerifiedEmail())
                <div class="px-6 lg:px-8 pt-6">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded border border-warn/25 bg-warn/10 px-4 py-3">
                        <p class="text-sm text-ink">
                            Please verify your email address to keep full access to your account.
                        </p>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <button type="submit" class="fh-btn-secondary text-xs">Resend verification email</button>
                        </form>
                    </div>
                </div>
            @endif

            @if ($currentLabel)
                <header class="hidden lg:flex items-end justify-between px-8 pt-8 pb-5 border-b border-chalk-3 mb-2">
                    <div>
                        <p class="fh-eyebrow pb-1 inline-block">{{ $currentGroupName }}</p>
                        <h1 class="font-display font-semibold text-2xl tracking-tight text-ink">{{ $currentLabel }}</h1>
                    </div>
                    <p class="font-mono text-xs text-steel uppercase tracking-wide">{{ now()->format('D, M j') }}</p>
                </header>
            @endif

            <main class="p-6 lg:px-8 lg:pb-8 lg:pt-0">
                {{ $slot }}
            </main>
        </div>

        <x-confirm-modal />

        @livewireScripts
        @stack('scripts')
    </body>
</html>
