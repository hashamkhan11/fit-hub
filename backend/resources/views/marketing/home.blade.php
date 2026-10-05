@php
    $supportPhone = config('app.support_phone');
    $phoneDigits = preg_replace('/[^\d+]/', '', $supportPhone);
    $phoneDisplay = preg_match('/^\+92(\d{3})(\d{7})$/', $supportPhone, $m)
        ? "+92 {$m[1]} {$m[2]}"
        : $supportPhone;
    $whatsappUrl = 'https://wa.me/'.ltrim($phoneDigits, '+').'?text='.rawurlencode("Hi FitHub, I'd like to know more.");
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/') }}">

    <title>FitHub — Gym Management Software for Independent Gyms</title>
    <meta name="description" content="FitHub replaces spreadsheets and sign-in sheets with one dashboard for memberships, QR check-in, classes, billing, staff, and revenue insight. 14-day free trial, no card required.">
    <meta name="keywords" content="gym management software, gym membership software, gym check-in app, class booking software for gyms, gym billing software, fitness studio management">
    <meta name="author" content="RankSol">

    {{-- Open Graph --}}
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="FitHub">
    <meta property="og:title" content="FitHub — Gym Management Software for Independent Gyms">
    <meta property="og:description" content="Memberships, QR check-in, classes, billing, staff, and revenue insight — in one dashboard. 14-day free trial, no card required.">
    <meta property="og:url" content="{{ url('/') }}">
    <meta property="og:image" content="{{ asset('images/branding/og-image.png') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- Twitter --}}
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="FitHub — Gym Management Software for Independent Gyms">
    <meta name="twitter:description" content="Memberships, QR check-in, classes, billing, staff, and revenue insight — in one dashboard. 14-day free trial, no card required.">
    <meta name="twitter:image" content="{{ asset('images/branding/og-image.png') }}">

    {{-- Icons / PWA manifest --}}
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/branding/favicon-32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/branding/favicon-16.png') }}">
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/branding/apple-touch-icon.png') }}">
    <link rel="manifest" href="{{ asset('site.webmanifest') }}">
    <meta name="theme-color" content="#0B0F1A">

    {{-- Structured data: Organization --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'RankSol',
        'url' => url('/'),
        'logo' => asset('images/branding/logo.png'),
        'email' => config('app.support_email'),
    ]) !!}
    </script>

    {{-- Structured data: SoftwareApplication + pricing offers --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'FitHub',
        'applicationCategory' => 'BusinessApplication',
        'operatingSystem' => 'Web, Android, iOS',
        'description' => 'Gym management software covering memberships, QR check-in, class scheduling, billing, staff access, and revenue analytics for independent gyms and fitness studios.',
        'offers' => $plans->map(fn ($plan) => [
            '@type' => 'Offer',
            'name' => $plan->name,
            'price' => rtrim(rtrim($plan->monthly_price, '0'), '.'),
            'priceCurrency' => 'USD',
            'category' => 'subscription',
        ])->values(),
    ]) !!}
    </script>

    {{-- Structured data: FAQPage (must match the FAQ section below) --}}
    <script type="application/ld+json">
    {!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($faqs)->map(fn ($faq) => [
            '@type' => 'Question',
            'name' => $faq['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $faq['a']],
        ])->values(),
    ]) !!}
    </script>

    @vite('resources/css/app.css')
</head>
<body class="font-sans bg-void text-mk-ink">

    {{-- Nav --}}
    <div class="sticky top-0 z-30 bg-mk-paper/85 backdrop-blur-xl border-b border-mk-line">
        <header class="max-w-6xl mx-auto px-6 lg:px-8 py-5 flex items-center justify-between">
            <a href="/" class="flex items-center gap-3">
                @include('partials.logo', ['class' => 'w-8 h-8', 'textClass' => 'text-xs'])
                <span class="font-display font-semibold tracking-wide text-base text-mk-ink">FITHUB</span>
            </a>
            <nav class="hidden md:flex items-center gap-6 text-sm">
                <a href="#features" class="mk-nav-link">Features</a>
                <a href="#pricing" class="mk-nav-link">Pricing</a>
                <a href="#faq" class="mk-nav-link">FAQ</a>
                <a href="#contact" class="mk-nav-link">Contact</a>
            </nav>
            <nav class="flex items-center gap-4 text-sm">
                <a href="{{ route('login') }}" class="mk-nav-link">Log in</a>
                <a href="/start-trial" class="mk-btn-primary !py-2 !px-4">Start free trial</a>
            </nav>
        </header>
    </div>

    {{-- Hero --}}
    <section class="relative overflow-hidden">
        <div class="relative max-w-6xl mx-auto px-6 lg:px-8 pt-8 pb-14 lg:pt-14 lg:pb-20">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="motion-safe:animate-fade-up">
                    <p class="mk-eyebrow mb-4">Gym Management, Simplified</p>
                    <h1 class="font-display font-semibold text-6xl lg:text-7xl leading-[1.02] tracking-tighter text-balance mb-6 text-mk-ink">
                        Run your gym<br>from <span class="text-gold">one board.</span>
                    </h1>
                    <p class="text-mk-ink-2 text-base lg:text-lg leading-relaxed mb-8 max-w-md">
                        Members, classes, attendance, billing, and revenue — tracked live, in one dashboard. No more spreadsheets, no more sign-in sheets.
                    </p>
                    <div class="flex flex-wrap items-center gap-4 mb-8">
                        <a href="/start-trial" class="mk-btn-primary !py-3 !px-6">Start free 14-day trial</a>
                        <a href="#features" class="mk-btn-secondary !py-3 !px-6">See how it works</a>
                    </div>
                    <div class="mk-stat-strip max-w-md">
                        <span><strong>14-day</strong> free trial</span>
                        <span><strong>$0</strong> setup fee</span>
                        <span><strong>Cancel</strong> anytime</span>
                    </div>
                </div>

                <div class="relative motion-safe:animate-fade-up" style="animation-delay: 120ms">
                    @php
                        $heroFile = collect(['hero-login.webp', 'hero-login.jpg', 'hero-login.png'])
                            ->first(fn ($f) => file_exists(public_path('images/auth/'.$f)));
                    @endphp
                    @if ($heroFile)
                        <div class="relative mx-auto max-w-sm lg:max-w-none rounded-xl overflow-hidden border border-mk-line aspect-[4/5] max-h-[440px] sm:max-h-[480px] lg:max-h-[560px] shadow-mk-lift">
                            <img src="{{ asset('images/auth/'.$heroFile) }}" alt="Gym member training on the training floor" class="w-full h-full object-cover object-top">
                            <div class="absolute inset-0 bg-gradient-to-t from-void/70 via-transparent to-transparent"></div>
                            <div class="absolute left-5 right-5 bottom-5 flex items-center justify-between font-mono text-xs">
                                <span class="text-white font-semibold">Today's check-ins</span>
                                <span class="fh-pill-good">Live</span>
                            </div>
                        </div>
                    @else
                        <div class="relative mx-auto max-w-sm lg:max-w-none rounded-xl border border-mk-line aspect-[4/5] max-h-[440px] sm:max-h-[480px] lg:max-h-[560px] shadow-mk-lift bg-mk-paper-2 flex items-center justify-center">
                            <div class="w-24 h-24 rounded-full bg-gold/10 flex items-center justify-center">
                                <div class="w-12 h-12 rounded-full bg-gold/20"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    {{-- Problem / pain points --}}
    <section class="relative border-t border-mk-line bg-mk-paper-2">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="mk-eyebrow mb-3">Sound familiar?</p>
            <h2 class="mk-heading text-2xl lg:text-3xl mb-10 max-w-xl">Most gyms are still run on tools that were never built for gyms.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['title' => 'Renewals slip through', 'body' => 'No one notices a membership lapsed until the member has already stopped showing up.'],
                    ['title' => 'Paper sign-in sheets', 'body' => 'Front desk staff manually track attendance — or don\'t track it at all.'],
                    ['title' => 'Revenue is a mystery', 'body' => 'No live view of MRR, churn, or which plans are actually driving revenue.'],
                    ['title' => 'Scattered tools', 'body' => 'A spreadsheet for members, a different app for classes, a notebook for payments.'],
                ] as $pain)
                    <div class="mk-card">
                        <span class="inline-block w-2 h-2 rounded-full bg-gold mb-3"></span>
                        <p class="font-display font-semibold text-base mb-1.5 text-mk-ink">{{ $pain['title'] }}</p>
                        <p class="text-sm text-mk-ink-2 leading-relaxed">{{ $pain['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Feature grid --}}
    <section id="features" class="relative scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="mk-eyebrow mb-3">What's on the board</p>
            <h2 class="mk-heading text-2xl lg:text-3xl mb-10 max-w-lg">Everything the front desk touches — in one place.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ([
                    ['title' => 'Members & Plans', 'body' => 'Onboard members, assign plans, and get alerted before a renewal lapses.'],
                    ['title' => 'QR Check-in', 'body' => 'Members scan a QR code at the door — attendance is logged automatically, no sign-in sheets.'],
                    ['title' => 'Classes & Bookings', 'body' => 'Publish a class schedule and let members book — and cancel — their own spot.'],
                    ['title' => 'Billing & Payments', 'body' => 'Stripe-powered subscription billing, with invoices and payment history members can see themselves.'],
                    ['title' => 'Staff & Trainer Access', 'body' => 'Scoped logins for staff and trainers — no full-dashboard handover required.'],
                    ['title' => 'Revenue Insight', 'body' => 'MRR, churn risk, and attendance trends, live — not a month-end spreadsheet exercise.'],
                ] as $feature)
                    <div class="mk-card">
                        <span class="inline-block w-2 h-2 rounded-full bg-gold mb-3"></span>
                        <p class="font-display font-semibold text-base mb-1.5 text-mk-ink">{{ $feature['title'] }}</p>
                        <p class="text-sm text-mk-ink-2 leading-relaxed">{{ $feature['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section class="relative border-t border-mk-line bg-mk-paper-2">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="mk-eyebrow mb-3">How it works</p>
            <h2 class="mk-heading text-2xl lg:text-3xl mb-10 max-w-lg">Up and running in an afternoon, not a quarter.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ([
                    ['step' => '01', 'title' => 'Add your members', 'body' => 'Import your existing roster or add members one by one — plans and pricing come with them.'],
                    ['step' => '02', 'title' => 'Set up plans & classes', 'body' => 'Define membership tiers and publish your class schedule.'],
                    ['step' => '03', 'title' => 'Members check in & book', 'body' => 'Members scan in at the door and book classes from their own app.'],
                    ['step' => '04', 'title' => 'Track it all live', 'body' => 'Watch attendance, renewals, and revenue update on your dashboard in real time.'],
                ] as $item)
                    <div class="mk-reveal mk-step" style="--reveal-delay: {{ $loop->index * 100 }}ms">
                        <p class="mk-step-num font-mono text-gold text-sm mb-2 tabular-nums">{{ $item['step'] }}</p>
                        <p class="font-display font-semibold text-base mb-1.5 text-mk-ink">{{ $item['title'] }}</p>
                        <p class="text-sm text-mk-ink-2 leading-relaxed">{{ $item['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Dual interface + dashboard preview --}}
    <section class="relative">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="mk-eyebrow mb-3">One system, two sides</p>
            <h2 class="mk-heading text-2xl lg:text-3xl mb-10 max-w-lg">A dashboard for your staff. An app for your members.</h2>

            <div class="grid lg:grid-cols-2 gap-6 mb-12">
                <div class="mk-card">
                    <p class="mk-eyebrow mb-2">For your team</p>
                    <p class="font-display font-semibold text-lg mb-3 text-mk-ink">The operator dashboard.</p>
                    <ul class="text-sm text-mk-ink-2 space-y-2">
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> Manage members, plans, staff, and classes from one screen</li>
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> Live attendance and revenue dashboards</li>
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> Scoped staff logins, so you control who sees what</li>
                    </ul>
                </div>
                <div class="mk-card">
                    <p class="mk-eyebrow mb-2">For your members</p>
                    <p class="font-display font-semibold text-lg mb-3 text-mk-ink">The member app.</p>
                    <ul class="text-sm text-mk-ink-2 space-y-2">
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> QR check-in at the door — no cards, no front-desk queue</li>
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> Book and cancel classes from their phone</li>
                        <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> View plan status, invoices, and attendance streaks</li>
                    </ul>
                </div>
            </div>

            {{-- Live screenshot of the actual gym-owner dashboard, framed like a
                 browser window. Drop the image at public/images/marketing/dashboard.png
                 (or .jpg/.webp) and it appears here automatically. --}}
            @php
                $dashFile = collect(['dashboard.webp', 'dashboard.jpg', 'dashboard.png'])
                    ->first(fn ($f) => file_exists(public_path('images/marketing/'.$f)));
            @endphp
            <div class="rounded-xl border border-mk-line shadow-mk-lift overflow-hidden bg-mk-paper">
                <div class="flex items-center gap-2 px-4 py-3 bg-mk-paper-2 border-b border-mk-line">
                    <span class="w-2.5 h-2.5 rounded-full bg-tape/70"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-warn/70"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-turf/70"></span>
                    <span class="ml-3 font-mono text-[11px] text-mk-ink-2 tracking-wide">app.fithub.pk/dashboard</span>
                </div>
                @if ($dashFile)
                    <img src="{{ asset('images/marketing/'.$dashFile) }}" alt="FitHub gym-owner dashboard showing members, attendance, and revenue" class="w-full h-auto block">
                @else
                    <div class="aspect-[16/9] flex items-center justify-center bg-void text-steel-2 font-mono text-xs">
                        Dashboard preview coming soon
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Why FitHub: trust section, no fake testimonials --}}
    <section class="relative border-t border-mk-line bg-mk-paper-2">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="mk-eyebrow mb-3">Why FitHub</p>
            <h2 class="mk-heading text-2xl lg:text-3xl mb-10 max-w-lg">Built for independent gyms, not enterprise chains.</h2>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
                @foreach ([
                    ['title' => 'No contracts', 'body' => 'Month-to-month billing. Cancel anytime, no long-term lock-in.'],
                    ['title' => '14-day free trial', 'body' => 'Try the full dashboard with no card required.'],
                    ['title' => 'Your data, your gym', 'body' => 'Every gym\'s data is isolated — nothing shared across accounts.'],
                    ['title' => 'Real support', 'body' => 'Reach an actual person when something needs fixing — see contact below.'],
                ] as $reason)
                    <div class="mk-card">
                        <span class="inline-block w-2 h-2 rounded-full bg-gold mb-3"></span>
                        <p class="font-display font-semibold text-base mb-1.5 text-mk-ink">{{ $reason['title'] }}</p>
                        <p class="text-sm text-mk-ink-2 leading-relaxed">{{ $reason['body'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="relative scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="mk-eyebrow mb-3">Pricing</p>
            <h2 class="mk-heading text-2xl lg:text-3xl mb-3">One plan for every stage of your gym.</h2>
            <p class="text-mk-ink-2 text-sm mb-10">14-day free trial. No card required.</p>

            <div class="grid sm:grid-cols-3 gap-5">
                @forelse ($plans as $plan)
                    <div class="mk-card flex flex-col {{ $loop->iteration === 2 ? 'border-gold ring-1 ring-gold/30 -translate-y-1' : '' }}">
                        @if ($loop->iteration === 2)
                            <span class="fh-pill-good w-fit mb-3">Most popular</span>
                        @endif
                        <p class="text-lg font-display font-semibold text-mk-ink">{{ $plan->name }}</p>
                        @if ($plan->description)
                            <p class="text-sm text-mk-ink-2 mt-1">{{ $plan->description }}</p>
                        @endif

                        <div class="mt-5 space-y-1">
                            <p class="font-mono text-2xl text-mk-ink tabular-nums">${{ rtrim(rtrim($plan->monthly_price, '0'), '.') }}<span class="text-mk-ink-2 text-sm font-sans">/mo</span></p>
                            @if ($plan->yearly_price)
                                <p class="font-mono text-mk-ink-2 text-xs tabular-nums">or ${{ rtrim(rtrim($plan->yearly_price, '0'), '.') }}/yr</p>
                            @endif
                        </div>

                        @if ($plan->features)
                            <ul class="text-sm text-mk-ink-2 mt-6 space-y-2 flex-1">
                                @foreach ($plan->features as $feature)
                                    <li class="flex items-start gap-2"><span class="text-gold">&bull;</span> {{ $feature }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        <a href="/start-trial" class="mk-btn-primary w-full !py-2.5 mt-6 text-center">Start free trial</a>
                    </div>
                @empty
                    <p class="text-sm text-mk-ink-2 col-span-3">Pricing is being finalized — check back shortly, or contact us below.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- FAQ --}}
    <section id="faq" class="relative border-t border-mk-line bg-mk-paper-2 scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <p class="mk-eyebrow mb-3">Questions</p>
            <h2 class="mk-heading text-2xl lg:text-3xl mb-10 max-w-lg">Frequently asked questions.</h2>

            <div class="max-w-3xl space-y-3">
                @foreach ($faqs as $faq)
                    <details class="mk-card group">
                        <summary class="cursor-pointer list-none flex items-center justify-between gap-4 font-display font-semibold text-base text-mk-ink">
                            {{ $faq['q'] }}
                            <span class="text-gold text-lg group-open:rotate-45 transition-transform">+</span>
                        </summary>
                        <p class="text-sm text-mk-ink-2 leading-relaxed mt-3">{{ $faq['a'] }}</p>
                    </details>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Final CTA --}}
    <section class="relative border-t border-mk-line overflow-hidden">
        <div class="absolute left-1/2 top-0 -translate-x-1/2 w-[420px] h-[420px] rounded-full bg-gold/10 blur-[100px] pointer-events-none"></div>
        <div class="relative max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20 text-center">
            <h2 class="mk-heading text-2xl lg:text-3xl mb-4">Ready to get off the spreadsheet?</h2>
            <p class="text-mk-ink-2 text-sm mb-8">No card required for the first 14 days.</p>
            <a href="/start-trial" class="mk-btn-primary !py-3 !px-8">Start free trial</a>
        </div>
    </section>

    {{-- Contact --}}
    <section id="contact" class="relative border-t border-mk-line bg-mk-paper-2 scroll-mt-20">
        <div class="max-w-6xl mx-auto px-6 lg:px-8 py-16 lg:py-20">
            <div class="grid lg:grid-cols-2 gap-12">
                <div>
                    <p class="mk-eyebrow mb-3">Contact</p>
                    <h2 class="mk-heading text-2xl lg:text-3xl mb-4">Questions before you start? Talk to us.</h2>
                    <p class="text-mk-ink-2 text-sm leading-relaxed mb-6">Send a message and we'll reply within one business day, or reach us directly.</p>
                    <div class="flex flex-wrap gap-3">
                        <a href="mailto:{{ config('app.support_email') }}" class="mk-btn-secondary !py-2.5 !px-5 inline-block">{{ config('app.support_email') }}</a>
                        <a href="tel:{{ $phoneDigits }}" class="mk-btn-secondary !py-2.5 !px-5 inline-block">{{ $phoneDisplay }}</a>
                    </div>
                </div>

                <div class="mk-card">
                    @if (session('status'))
                        <p class="fh-pill-good mb-4">{{ session('status') }}</p>
                    @endif
                    <form method="POST" action="{{ route('contact') }}" class="space-y-4">
                        @csrf
                        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                        <div>
                            <label for="contact-name" class="text-xs text-mk-ink-2 block mb-1.5">Name</label>
                            <input id="contact-name" type="text" name="name" required value="{{ old('name') }}" class="mk-input w-full">
                            @error('name') <p class="text-tape text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="contact-email" class="text-xs text-mk-ink-2 block mb-1.5">Email</label>
                            <input id="contact-email" type="email" name="email" required value="{{ old('email') }}" class="mk-input w-full">
                            @error('email') <p class="text-tape text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="contact-gym" class="text-xs text-mk-ink-2 block mb-1.5">Gym name (optional)</label>
                            <input id="contact-gym" type="text" name="gym_name" value="{{ old('gym_name') }}" class="mk-input w-full">
                        </div>

                        <div>
                            <label for="contact-message" class="text-xs text-mk-ink-2 block mb-1.5">Message</label>
                            <textarea id="contact-message" name="message" required rows="4" class="mk-input w-full">{{ old('message') }}</textarea>
                            @error('message') <p class="text-tape text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <button type="submit" class="mk-btn-primary w-full !py-2.5">Send message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <footer class="max-w-6xl mx-auto px-6 lg:px-8 py-10 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-mk-ink-2">
        <p>&copy; {{ date('Y') }} FitHub &middot; built by RankSol</p>
        <div class="flex items-center gap-5">
            <a href="mailto:{{ config('app.support_email') }}" class="hover:text-mk-ink transition">{{ config('app.support_email') }}</a>
            <a href="tel:{{ $phoneDigits }}" class="hover:text-mk-ink transition">{{ $phoneDisplay }}</a>
            <a href="{{ route('legal.terms') }}" class="hover:text-mk-ink transition">Terms</a>
            <a href="{{ route('legal.privacy') }}" class="hover:text-mk-ink transition">Privacy</a>
        </div>
    </footer>

    {{-- Floating WhatsApp button — fixed to the corner across the whole page. --}}
    <a
        href="{{ $whatsappUrl }}"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Chat with us on WhatsApp"
        class="fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full bg-[#25D366] shadow-mk-lift flex items-center justify-center transition hover:scale-105 hover:brightness-105 active:scale-95"
    >
        <svg viewBox="0 0 32 32" class="w-7 h-7 fill-white" aria-hidden="true">
            <path d="M16.004 3C9.377 3 4 8.373 4 15c0 2.34.657 4.527 1.797 6.39L4 29l7.86-1.76A11.94 11.94 0 0 0 16.004 27C22.63 27 28 21.627 28 15S22.63 3 16.004 3Zm0 21.75c-1.98 0-3.83-.55-5.41-1.51l-.39-.23-4.66 1.04 1.02-4.55-.25-.4A9.7 9.7 0 0 1 5.25 15c0-5.93 4.82-10.75 10.754-10.75S26.75 9.07 26.75 15 21.938 24.75 16.004 24.75Zm5.93-8.02c-.32-.16-1.9-.94-2.2-1.04-.3-.11-.51-.16-.73.16-.21.32-.83 1.04-1.02 1.25-.19.21-.38.24-.7.08-.32-.16-1.34-.5-2.55-1.59-.94-.84-1.58-1.88-1.76-2.2-.19-.32-.02-.49.14-.65.14-.14.32-.38.48-.56.16-.19.21-.32.32-.54.11-.21.05-.4-.03-.56-.08-.16-.73-1.77-1-2.42-.26-.63-.53-.55-.73-.56h-.62c-.21 0-.56.08-.85.4-.29.32-1.11 1.09-1.11 2.65s1.14 3.08 1.3 3.29c.16.21 2.24 3.43 5.43 4.81.76.33 1.35.52 1.81.67.76.24 1.45.21 2 .13.61-.09 1.9-.78 2.17-1.53.27-.75.27-1.4.19-1.53-.08-.13-.29-.21-.61-.37Z"/>
        </svg>
    </a>

    <script>
        // Lightweight scroll-reveal: only kicks in when IntersectionObserver
        // is available, so content is never hidden from browsers without it.
        if ('IntersectionObserver' in window) {
            document.documentElement.classList.add('js-reveal-ready');
            var revealItems = document.querySelectorAll('.mk-reveal');
            var revealObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        revealObserver.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.2, rootMargin: '0px 0px -40px 0px' });
            revealItems.forEach(function (el) { revealObserver.observe(el); });
        }
    </script>
</body>
</html>
