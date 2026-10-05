<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FitHub — Start Your Free Trial</title>

    @if (file_exists(public_path('images/branding/logo.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/branding/logo.png') }}">
    @endif

    @vite('resources/css/app.css')
</head>
<body class="font-sans">
    <div class="min-h-screen lg:grid lg:grid-cols-5">

        {{-- Hero panel --}}
        <div class="relative lg:col-span-2 min-h-[32vh] lg:min-h-screen flex flex-col justify-between overflow-hidden bg-void">
            <div class="absolute inset-0 opacity-[0.05]"
                 style="background-image: radial-gradient(rgba(255,255,255,.5) 1px, transparent 1px); background-size: 22px 22px;"></div>
            <div class="absolute -top-32 -left-24 w-[28rem] h-[28rem] rounded-full bg-gold/10 blur-3xl"></div>
            <div class="absolute bottom-0 right-0 w-[32rem] h-[32rem] rounded-full bg-gold-2/10 blur-3xl"></div>

            <div class="relative p-8 lg:p-12 flex items-center gap-3 motion-safe:animate-fade-up">
                @include('partials.logo', ['class' => 'w-9 h-9', 'textClass' => 'text-sm'])
                <span class="font-display font-semibold tracking-wide text-lg text-ink">FITHUB</span>
            </div>

            <div class="relative p-8 lg:p-12 pb-14 lg:pb-16 max-w-xl motion-safe:animate-fade-up" style="animation-delay: 120ms">
                <p class="fh-eyebrow text-gold mb-3">14-Day Free Trial</p>
                <h1 class="font-display font-semibold text-3xl lg:text-4xl leading-[1.05] tracking-tight text-balance text-ink mb-5">
                    Your gym, live on the board in five minutes.
                </h1>
                <p class="text-steel-2 text-sm lg:text-base leading-relaxed mb-8 max-w-md">
                    No card required to start. Pick a plan, create your account, and
                    you're straight into the dashboard.
                </p>

                <ul class="space-y-2 text-sm text-steel-2 font-mono">
                    <li class="flex items-center gap-2"><span class="text-gold">&bull;</span> Members, classes & attendance</li>
                    <li class="flex items-center gap-2"><span class="text-gold">&bull;</span> Automated renewal reminders</li>
                    <li class="flex items-center gap-2"><span class="text-gold">&bull;</span> Live revenue insight</li>
                </ul>
            </div>
        </div>

        {{-- Form panel --}}
        <div class="lg:col-span-3 flex items-center justify-center bg-chalk px-6 py-14 sm:px-12">
            <div class="w-full max-w-lg motion-safe:animate-fade-up" style="animation-delay: 180ms">
                <p class="fh-eyebrow mb-2">Start Your Trial</p>
                <h2 class="fh-heading text-2xl mb-8">Create your gym account</h2>

                @if ($errors->any())
                    <div class="fh-error mb-5 border border-tape/30 bg-tape/5 rounded px-3 py-2">
                        {{ $errors->first() }}
                    </div>
                @endif

                <form method="POST" action="/start-trial" class="space-y-5">
                    @csrf

                    {{-- Honeypot field to catch bots. Hidden off-screen, not display:none. --}}
                    <div class="absolute -left-[9999px]" aria-hidden="true">
                        <label for="website">Website</label>
                        <input type="text" name="website" id="website" tabindex="-1" autocomplete="off">
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="fh-label" for="gym_name">Gym name</label>
                            <input class="fh-input" type="text" name="gym_name" id="gym_name" value="{{ old('gym_name') }}" required autofocus>
                        </div>
                        <div>
                            <label class="fh-label" for="owner_name">Your name</label>
                            <input class="fh-input" type="text" name="owner_name" id="owner_name" value="{{ old('owner_name') }}" required>
                        </div>
                    </div>

                    <div>
                        <label class="fh-label" for="email">Email</label>
                        <input class="fh-input" type="email" name="email" id="email" value="{{ old('email') }}" required>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="fh-label" for="password">Password</label>
                            <input class="fh-input" type="password" name="password" id="password" required minlength="8">
                        </div>
                        <div>
                            <label class="fh-label" for="password_confirmation">Confirm password</label>
                            <input class="fh-input" type="password" name="password_confirmation" id="password_confirmation" required minlength="8">
                        </div>
                    </div>

                    <div>
                        <p class="fh-label mb-2">Plan</p>
                        <div class="grid sm:grid-cols-3 gap-3">
                            @forelse ($plans as $index => $plan)
                                <label class="fh-card cursor-pointer !p-3 flex flex-col gap-1 has-[:checked]:border-gold has-[:checked]:bg-gold/5">
                                    <input type="radio" name="subscription_plan_id" value="{{ $plan->id }}"
                                           class="sr-only" {{ old('subscription_plan_id', $plans->first()?->id) == $plan->id ? 'checked' : '' }} required>
                                    <span class="text-sm font-display font-semibold">{{ $plan->name }}</span>
                                    <span class="fh-td-mono text-xs">${{ $plan->monthly_price }}<span class="text-steel">/mo</span></span>
                                </label>
                            @empty
                                <p class="text-sm text-steel col-span-3">No plans are available right now — please check back soon.</p>
                            @endforelse
                        </div>
                    </div>

                    <button class="fh-btn-primary w-full !py-3" type="submit">Start free trial</button>
                </form>

                <p class="text-xs text-steel mt-8">Already have an account? <a href="{{ route('login') }}" class="text-ink hover:underline">Log in</a></p>
                <p class="text-xs text-steel mt-2 flex gap-4">
                    <a href="{{ route('legal.terms') }}" class="hover:text-ink transition">Terms</a>
                    <a href="{{ route('legal.privacy') }}" class="hover:text-ink transition">Privacy</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>
