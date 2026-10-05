<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub — @yield('title')</title>

    @if (file_exists(public_path('images/branding/logo.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/branding/logo.png') }}">
    @endif

    @vite('resources/css/app.css')
</head>
<body class="font-sans bg-chalk text-ink">
    <div class="max-w-3xl mx-auto px-6 py-14">
        <a href="/" class="inline-flex items-center gap-3 mb-10">
            @include('partials.logo', ['class' => 'w-8 h-8', 'textClass' => 'text-sm'])
            <span class="font-display font-semibold tracking-wide text-base text-ink">FITHUB</span>
        </a>

        <p class="fh-eyebrow text-gold-3 mb-2">Legal</p>
        <h1 class="fh-heading text-3xl mb-1">@yield('title')</h1>
        <p class="text-xs text-steel mb-10">Last updated July 22, 2026</p>

        <div class="space-y-6 text-sm leading-relaxed text-ink [&_h2]:fh-heading [&_h2]:text-base [&_h2]:mt-8 [&_h2]:mb-2 [&_p]:text-steel [&_p]:mb-3 [&_li]:text-steel [&_ul]:list-disc [&_ul]:pl-5 [&_ul]:space-y-1">
            @yield('content')
        </div>

        <div class="mt-14 pt-6 border-t border-chalk-3 flex flex-wrap gap-x-6 gap-y-2 text-xs">
            <a href="{{ route('legal.terms') }}" class="fh-link-action text-steel hover:text-ink">Terms of Service</a>
            <a href="{{ route('legal.privacy') }}" class="fh-link-action text-steel hover:text-ink">Privacy Policy</a>
            <a href="{{ route('legal.data-deletion') }}" class="fh-link-action text-steel hover:text-ink">Delete My Data</a>
        </div>
    </div>
</body>
</html>
