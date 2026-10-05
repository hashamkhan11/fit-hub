<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordChangedMail;
use App\Mail\PasswordResetLinkMail;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::broker('users')->sendResetLink(
            $request->only('email'),
            function (User $user, string $token) {
                $url = url(route('password.reset', ['token' => $token], false)).'?email='.urlencode($user->email);

                Mail::to($user->email)->send(new PasswordResetLinkMail($url, 'FitHub admin panel'));
            }
        );

        return back()->with('status', 'If an account matches, a reset link has been sent.');
    }

    public function edit(Request $request, string $token)
    {
        return view('auth.reset-password', [
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

        $status = Password::broker('users')->reset(
            $validated,
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                ActivityLog::create([
                    'gym_id' => $user->gym_id,
                    'user_id' => $user->id,
                    'action' => 'auth.password_reset',
                    'description' => 'Password was reset via the forgot-password email link.',
                    'created_at' => now(),
                ]);

                Mail::to($user->email)->send(new PasswordChangedMail('FitHub admin panel'));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['This reset link is invalid or has expired.'],
            ]);
        }

        return redirect()->route('login')->with('status', 'Your password has been reset. Please log in.');
    }
}
