<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGymHasHardware
{
    /**
     * Blocks lock/fingerprint features for gyms without the hardware add-on plan.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $gym = $user?->gym;

        if ($gym && ! $gym->hasHardwareAccess()) {
            $message = 'Smart lock and fingerprint access aren\'t included in your current plan. Upgrade to unlock hardware features.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('billing')->with('status', $message);
        }

        return $next($request);
    }
}
