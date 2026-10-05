<?php

namespace App\Providers;

use App\Models\Gym;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Cashier;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Cashier::ignoreRoutes();
        Cashier::useCustomerModel(Gym::class);

        if ($this->app->environment('production') && ! config('cashier.webhook.secret')) {
            Log::critical('STRIPE_WEBHOOK_SECRET is not set in production — the /stripe/webhook endpoint is accepting unsigned requests.');
        }

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(90)->by($request->user()?->id ?: $request->ip());
        });

        Gate::define('view-insight', fn (User $user) => $user->role === 'owner');
        Gate::define('manage-billing', fn (User $user) => $user->role === 'owner');
        Gate::define('manage-plans', fn (User $user) => $user->role === 'owner');
        Gate::define('view-plans', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('manage-staff', fn (User $user) => $user->role === 'owner');
        Gate::define('manage-business', fn (User $user) => $user->role === 'owner');

        Gate::define('manage-trainers', fn (User $user) => $user->role === 'owner');
        Gate::define('view-trainers', fn (User $user) => in_array($user->role, ['owner', 'staff']));

        Gate::define('manage-members', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('view-members', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('view-dashboard', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('manage-payments', fn (User $user) => in_array($user->role, ['owner', 'staff']));

        Gate::define('checkin-attendance', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('view-attendance', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('manage-classes', fn (User $user) => $user->role === 'owner');
        Gate::define('view-classes', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('manage-bookings', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('view-bookings', fn (User $user) => in_array($user->role, ['owner', 'staff', 'trainer']));

        Gate::define('view-activity', fn (User $user) => $user->role === 'owner');

        Gate::define('manage-lock-devices', fn (User $user) => $user->role === 'owner');
        Gate::define('trigger-lock', fn (User $user) => in_array($user->role, ['owner', 'staff']));
        Gate::define('manage-fingerprints', fn (User $user) => $user->role === 'owner');
    }
}
