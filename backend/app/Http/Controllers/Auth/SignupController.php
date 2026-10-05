<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SignupController extends Controller
{
    public function create()
    {
        return view('auth.signup', [
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        // Honeypot: hidden field real users never fill in, only bots do.
        // Pretend it worked so the bot doesn't know it got caught.
        if ($request->filled('website')) {
            return redirect('/start-trial');
        }

        $validated = $request->validate([
            'gym_name' => ['required', 'string', 'max:255'],
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:gyms,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $plan = SubscriptionPlan::where('is_active', true)->findOrFail($validated['subscription_plan_id']);

        [$gym, $user] = DB::transaction(function () use ($validated, $plan) {
            $gym = Gym::create([
                'name' => $validated['gym_name'],
                'email' => $validated['email'],
                'currency_code' => 'USD',
                'subscription_status' => 'trial',
                'subscription_plan_id' => $plan->id,
                'plan_name' => $plan->name,
                'plan_price' => $plan->monthly_price,
                'billing_cycle' => 'monthly',
                'trial_ends_at' => now()->addDays(14),
            ]);

            $user = User::create([
                'gym_id' => $gym->id,
                'name' => $validated['owner_name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'role' => 'owner',
            ]);

            return [$gym, $user];
        });

        PlatformActivityLog::record('gym.created', "New gym signed up via self-serve trial: {$gym->name}.", $gym);

        $user->sendEmailVerificationNotification();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect('/dashboard')->with('status', "Welcome to FitHub! Your {$plan->name} trial ends ".$gym->trial_ends_at->format('M j, Y').'.');
    }
}
