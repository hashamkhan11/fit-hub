<?php

namespace App\Http\Controllers;

use App\Mail\PaymentFailedMail;
use App\Mail\SubscriptionCanceledMail;
use App\Models\ActivityLog;
use App\Models\Gym;
use App\Models\PlatformActivityLog;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    protected function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionUpdated($payload);

        $this->syncGymFromStripeEvent($payload);

        return $response;
    }

    protected function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $gym = $this->syncGymFromStripeEvent($payload);

        if ($gym && $gym->email) {
            Mail::to($gym->email)->send(new SubscriptionCanceledMail($gym));
        }

        return $response;
    }

    /**
     * Fires as soon as a renewal payment fails, so we can warn the owner early.
     */
    protected function handleInvoicePaymentFailed(array $payload): Response
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;
        $gym = $stripeCustomerId ? Gym::where('stripe_id', $stripeCustomerId)->first() : null;

        if ($gym && $gym->email) {
            Mail::to($gym->email)->send(new PaymentFailedMail($gym));

            ActivityLog::create([
                'gym_id' => $gym->id,
                'user_id' => null,
                'action' => 'billing.payment_failed',
                'description' => "A payment for {$gym->name} failed.",
                'created_at' => now(),
            ]);
        }

        return $this->successMethod();
    }

    private function syncGymFromStripeEvent(array $payload): ?Gym
    {
        $stripeCustomerId = $payload['data']['object']['customer'] ?? null;

        if (! $stripeCustomerId) {
            return null;
        }

        $gym = Gym::where('stripe_id', $stripeCustomerId)->first();

        if (! $gym) {
            return null;
        }

        $stripeStatus = $payload['data']['object']['status'] ?? null;
        $status = match ($stripeStatus) {
            'active', 'trialing' => 'active',
            'canceled', 'unpaid', 'incomplete_expired' => 'suspended',
            default => $gym->subscription_status,
        };

        $plan = $gym->subscriptionPlan;

        $gym->update([
            'subscription_status' => $status,
            'plan_name' => $plan->name ?? $gym->plan_name,
            'plan_price' => $plan->monthly_price ?? $gym->plan_price,
        ]);

        $description = "Stripe subscription for {$gym->name} is now '{$stripeStatus}'.";

        ActivityLog::create([
            'gym_id' => $gym->id,
            'user_id' => null,
            'action' => 'billing.subscription_updated',
            'description' => $description,
            'created_at' => now(),
        ]);

        PlatformActivityLog::create([
            'platform_admin_id' => null,
            'gym_id' => $gym->id,
            'action' => 'gym.subscription_updated',
            'description' => $description,
            'created_at' => now(),
        ]);

        return $gym;
    }
}
