<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
use App\Models\Member;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    public function sendOtp(Request $request)
    {
        $validated = $request->validate([
            'channel' => ['required', 'in:email,phone'],
            'identifier' => ['required', 'string'],
        ]);

        $member = $this->findMember($validated['channel'], $validated['identifier']);

        if ($member) {
            $code = (string) random_int(100000, 999999);

            $member->update([
                'reset_otp' => Hash::make($code),
                'reset_otp_expires_at' => now()->addMinutes(10),
            ]);

            RateLimiter::clear($this->otpAttemptKey($member));

            if ($validated['channel'] === 'email') {
                Mail::to($member->email)->send(new OtpMail($code));
            } else {
                app(SmsService::class)->send(
                    $member->phone,
                    "Your FitHub password reset code is {$code}. It expires in 10 minutes."
                );
            }
        }

        return response()->json([
            'message' => 'If an account matches, a reset code has been sent.',
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'channel' => ['required', 'in:email,phone'],
            'identifier' => ['required', 'string'],
            'otp' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $member = $this->findMember($validated['channel'], $validated['identifier']);

        if ($member && RateLimiter::tooManyAttempts($this->otpAttemptKey($member), 5)) {
            throw ValidationException::withMessages([
                'otp' => ['Too many incorrect attempts. Request a new code and try again.'],
            ]);
        }

        $invalid = ! $member
            || ! $member->reset_otp
            || ! $member->reset_otp_expires_at
            || $member->reset_otp_expires_at->isPast()
            || ! Hash::check($validated['otp'], $member->reset_otp);

        if ($invalid) {
            if ($member) {
                RateLimiter::hit($this->otpAttemptKey($member), 600);
            }

            throw ValidationException::withMessages([
                'otp' => ['That code is invalid or has expired.'],
            ]);
        }

        RateLimiter::clear($this->otpAttemptKey($member));

        $member->update([
            'password' => $validated['password'],
            'reset_otp' => null,
            'reset_otp_expires_at' => null,
        ]);

        $member->tokens()->delete();

        return response()->json(['message' => 'Password reset. Please log in.']);
    }

    private function findMember(string $channel, string $identifier): ?Member
    {
        return $channel === 'email'
            ? Member::where('email', $identifier)->first()
            : Member::where('phone', $identifier)->first();
    }

    private function otpAttemptKey(Member $member): string
    {
        return "otp-reset-attempts:{$member->id}";
    }
}
