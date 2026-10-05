<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetLinkMail;
use App\Models\PlatformActivityLog;
use App\Models\PlatformAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlatformForgotPasswordController extends Controller
{
    public function create()
    {
        return view('platform.auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('platform_admins')->sendResetLink(
            $request->only('email'),
            function (PlatformAdmin $admin, string $token) {
                $url = url(route('platform.password.reset', ['token' => $token], false)).'?email='.urlencode($admin->email);

                Mail::to($admin->email)->send(new PasswordResetLinkMail($url, 'RankSol platform panel'));
            }
        );

        return back()->with('status', 'If an account matches, a reset link has been sent.');
    }

    public function edit(Request $request, string $token)
    {
        return view('platform.auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::broker('platform_admins')->reset(
            $validated,
            function (PlatformAdmin $admin, string $password) {
                $admin->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                PlatformActivityLog::create([
                    'platform_admin_id' => $admin->id,
                    'action' => 'auth.password_reset',
                    'description' => 'Password was reset via the forgot-password email link.',
                    'created_at' => now(),
                ]);

                Mail::to($admin->email)->send(new PasswordChangedMail('RankSol platform panel'));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['This reset link is invalid or has expired.'],
            ]);
        }

        return redirect()->route('platform.login')->with('status', 'Your password has been reset. Please log in.');
    }
}
