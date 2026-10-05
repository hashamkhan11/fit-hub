<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RankSol Platform — Forgot password</title>

    @vite('resources/css/app.css')
</head>
<body class="font-sans bg-void">
    <div class="min-h-screen flex items-center justify-center px-6 py-14">
        <div class="w-full max-w-sm motion-safe:animate-fade-up">
            <div class="flex items-center gap-2 mb-10 justify-center">
                <span class="w-1.5 h-1.5 rounded-full bg-teal shadow-[0_0_8px_theme(colors.teal.DEFAULT)]" aria-hidden="true"></span>
                <span class="font-display font-semibold tracking-wide text-ink">RANKSOL PLATFORM</span>
            </div>

            <p class="pf-eyebrow text-center mb-2">Account Recovery</p>
            <h1 class="pf-heading text-2xl text-center mb-8">Reset your password</h1>

            @if (session('status'))
                <div class="pf-eyebrow mb-5 border border-teal/30 bg-teal/5 rounded px-3 py-2 text-ink normal-case tracking-normal text-center">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="pf-error mb-5 border border-tape/30 bg-tape/5 rounded px-3 py-2 text-center">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('platform.password.email') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="pf-label" for="email">Email</label>
                    <input class="pf-input" type="email" name="email" id="email" value="{{ old('email') }}" required autofocus>
                </div>

                <button class="pf-btn-primary w-full !py-3" type="submit">Send reset link</button>
            </form>

            <p class="text-xs text-mist mt-10 text-center"><a href="{{ route('platform.login') }}" class="text-ink hover:underline">Back to login</a></p>
        </div>
    </div>
</body>
</html>
