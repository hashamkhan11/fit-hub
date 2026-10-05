<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureGymIsActive
{
    /**
     * Blocks access for anyone in a gym that RankSol has suspended.
     * Platform admins are not affected.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $gym = $user?->gym;

        if ($gym && $gym->isSuspended()) {
            $message = 'This gym\'s account has been suspended. Please contact '.config('app.support_email').' for help.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => $message]);
        }

        // Trial gyms aren't locked out fully — they're sent to Billing to pick a plan.
        if ($gym && $gym->isTrialExpired() && ! $request->routeIs('billing')) {
            $message = 'Your free trial has ended — choose a plan to keep using FitHub.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 402);
            }

            return redirect()->route('billing')->with('status', $message);
        }

        return $next($request);
    }
}
