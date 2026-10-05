<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ $title ?? 'RankSol Platform' }}</title>

        @if (file_exists(public_path('images/branding/logo.png')))
            <link rel="icon" type="image/png" href="{{ asset('images/branding/logo.png') }}">
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-void min-h-screen font-sans text-ink lg:flex overflow-x-hidden">
        @php
            $items = [
                'overview' => ['Overview', '/ranksol'],
                'business' => ['Business', '/ranksol/business'],
                'gyms' => ['Gyms', '/ranksol/gyms'],
                'plans' => ['Plans', '/ranksol/plans'],
                'admins' => ['Admins', '/ranksol/admins'],
                'activity' => ['Activity', '/ranksol/activity'],
            ];

            $icons = [
                'overview' => '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="6" rx="0.5"/><rect x="13" y="8" width="3" height="10" rx="0.5"/><rect x="18.5" y="5" width="3" height="13" rx="0.5"/>',
                'business' => '<path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H7"/>',
                'gyms' => '<path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-6h6v6"/>',
                'plans' => '<path d="M12.586 3H7a2 2 0 0 0-2 2v5.586a1 1 0 0 0 .293.707l8.414 8.414a2 2 0 0 0 2.828 0l5.586-5.586a2 2 0 0 0 0-2.828l-8.414-8.414A1 1 0 0 0 12.586 3Z"/><circle cx="9" cy="9" r="1.3" fill="currentColor" stroke="none"/>',
                'admins' => '<path d="M16 19v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1"/><circle cx="9.5" cy="7" r="3.5"/><path d="M21 19v-1a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
                'activity' => '<path d="M22 12h-4l-3 9L9 3l-3 9H2"/>',
            ];

            $currentKey = collect($items)->keys()->first(function ($key) use ($items) {
                $path = $items[$key][1];
                return $path === '/ranksol' ? request()->is('ranksol') : request()->is(ltrim($path, '/').'*');
            });
        @endphp

        {{-- Desktop sidebar --}}
        <aside class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-4 lg:left-4 lg:rounded-xl bg-chalk/80 backdrop-blur-xl border border-chalk-3 shadow-fh-card z-20">
            <div class="flex items-center gap-2.5 px-6 h-16 border-b border-chalk-3 shrink-0">
                @include('partials.logo', ['class' => 'w-6 h-6 shrink-0'])
                <span class="font-mono font-semibold text-ink tracking-wide text-sm">RANKSOL</span>
            </div>

            <nav class="flex-1 overflow-y-auto py-6 px-3 space-y-0.5">
                @foreach ($items as $key => [$label, $href])
                    @php $active = $key === $currentKey; @endphp
                    <a href="{{ $href }}"
                       class="fh-nav-link {{ $active ? 'fh-nav-link-active' : '' }}">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" class="w-[18px] h-[18px] shrink-0">
                            {!! $icons[$key] !!}
                        </svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="p-4 border-t border-chalk-3 shrink-0">
                <a href="/ranksol/account" class="flex items-center gap-3 px-2 mb-3 rounded hover:bg-chalk transition py-1 -mx-1">
                    <div class="w-8 h-8 rounded-full bg-gold-soft flex items-center justify-center shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-teal">
                            <circle cx="12" cy="8" r="4"/>
                            <path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-ink text-sm font-medium truncate">{{ auth('platform')->user()->name }}</p>
                        <p class="text-mist text-xs font-mono uppercase truncate">{{ auth('platform')->user()->role }}</p>
                    </div>
                </a>
                <form method="POST" action="/ranksol/logout">
                    @csrf
                    <button type="submit" class="w-full text-left font-mono uppercase text-xs tracking-wide text-mist hover:text-tape transition px-2">Log out</button>
                </form>
            </div>
        </aside>

        {{-- Mobile top bar --}}
        <div class="lg:hidden sticky top-0 z-20 bg-void border-b border-chalk-3">
            <div class="px-4 h-14 flex items-center justify-between">
                <span class="flex items-center gap-2 font-mono font-semibold text-ink tracking-wide text-sm">
                    @include('partials.logo', ['class' => 'w-5 h-5 shrink-0'])
                    RANKSOL
                </span>
                <form method="POST" action="/ranksol/logout">
                    @csrf
                    <button type="submit" class="font-mono uppercase text-xs tracking-wide text-mist hover:text-tape transition">Log out</button>
                </form>
            </div>
            <nav class="flex overflow-x-auto px-2 pb-2 gap-1 no-scrollbar">
                @foreach ($items as $key => [$label, $href])
                    <a href="{{ $href }}"
                       class="fh-nav-pill {{ $key === $currentKey ? 'fh-nav-pill-active' : '' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </div>

        {{-- Main content --}}
        <div class="flex-1 lg:pl-72 min-w-0">
            <main class="p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>

        <x-confirm-modal />

        @livewireScripts
    </body>
</html>
