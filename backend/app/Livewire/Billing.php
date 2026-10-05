<?php

namespace App\Livewire;

use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Billing extends Component
{
    public ?string $checkoutClientSecret = null;

    public function mount(): void
    {
        Gate::authorize('manage-billing');
    }

    public function render()
    {
        $gym = auth()->user()->gym;

        return view('livewire.billing', [
            'gym' => $gym,
            'subscription' => $gym->subscription('default'),
            'plans' => SubscriptionPlan::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'stripeKey' => config('cashier.key'),
            'invoices' => $gym->invoices(),
        ]);
    }

    public function subscribe(int $planId, string $cycle): void
    {
        Gate::authorize('manage-billing');

        if (! auth()->user()->hasVerifiedEmail()) {
            $this->addError('subscribe', 'Please verify your email address before subscribing to a plan.');

            return;
        }

        $gym = auth()->user()->gym;
        $plan = SubscriptionPlan::findOrFail($planId);

        $priceId = $cycle === 'yearly' ? $plan->stripe_price_id_yearly : $plan->stripe_price_id_monthly;

        if (! $priceId) {
            $this->addError('subscribe', 'This plan has not been synced to Stripe yet. Contact '.config('app.support_email').'.');

            return;
        }

        try {
            // Embedded mode keeps the user on this page instead of redirecting to Stripe.
            $checkout = $gym->newSubscription('default', $priceId)->checkout([
                'ui_mode' => 'embedded',
                'return_url' => route('billing').'?checkout=success',
            ]);

            $gym->update(['subscription_plan_id' => $plan->id]);

            $this->checkoutClientSecret = $checkout->asStripeCheckoutSession()->client_secret;
        } catch (\Throwable $e) {
            $this->addError('subscribe', "Could not start checkout: {$e->getMessage()}");
        }
    }

    public function manage(): void
    {
        Gate::authorize('manage-billing');

        $gym = auth()->user()->gym;

        try {
            $this->redirect($gym->redirectToBillingPortal(route('billing'))->getTargetUrl());
        } catch (\Throwable $e) {
            $this->addError('manage', "Could not open the billing portal: {$e->getMessage()}");
        }
    }
}
