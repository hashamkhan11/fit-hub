<div class="max-w-[1400px] mx-auto space-y-6">
    @if (session('status'))
        <div class="fh-card border-warn/40 text-sm text-warn">{{ session('status') }}</div>
    @endif
    @error('subscribe') <div class="fh-card border-tape/40 text-sm text-tape">{{ $message }}</div> @enderror
    @error('manage') <div class="fh-card border-tape/40 text-sm text-tape">{{ $message }}</div> @enderror

    <div class="fh-card">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <p class="fh-label mb-1">Current Plan</p>
                @if ($gym->subscriptionPlan)
                    <p class="text-xl font-display font-semibold">{{ $gym->subscriptionPlan->name }}</p>
                @elseif ($gym->plan_name)
                    <p class="text-xl font-display font-semibold">{{ $gym->plan_name }}</p>
                    <p class="text-xs text-steel mt-1">Set manually by RankSol — not yet linked to a catalog plan.</p>
                @else
                    <p class="text-xl font-display font-semibold text-steel">No plan assigned</p>
                @endif
            </div>

            <div>
                @if ($gym->isSuspended())
                    <span class="fh-pill-bad">Suspended</span>
                @elseif ($gym->isOnTrial())
                    <span class="fh-pill-warn">Trial &middot; {{ $gym->trialDaysRemaining() }}d left</span>
                @elseif ($subscription && $subscription->ends_at)
                    <span class="fh-pill-warn">Canceling</span>
                @else
                    <span class="fh-pill-good">Active</span>
                @endif
            </div>
        </div>

        @if ($subscription)
            <div class="border-t border-chalk-3 mt-5 pt-5 flex items-center justify-between flex-wrap gap-4">
                <div class="text-sm text-steel">
                    @if ($gym->pm_type)
                        <p>Card on file: {{ ucfirst($gym->pm_type) }} &middot;&middot;&middot;&middot; {{ $gym->pm_last_four }}</p>
                    @endif
                    @if ($subscription->ends_at)
                        <p>Cancels on {{ $subscription->ends_at->format('M j, Y') }}</p>
                    @elseif ($subscription->asStripeSubscription()->current_period_end ?? null)
                        <p>Renews {{ \Illuminate\Support\Carbon::createFromTimestamp($subscription->asStripeSubscription()->current_period_end)->format('M j, Y') }}</p>
                    @endif
                </div>
                <button wire:click="manage" class="fh-btn-secondary">Manage Billing</button>
            </div>
        @endif
    </div>

    @if ($checkoutClientSecret)
        <div class="fh-card">
            <div class="flex items-center justify-between mb-4">
                <h2 class="fh-heading text-sm">Complete your subscription</h2>
                <button wire:click="$set('checkoutClientSecret', null)" class="text-xs text-steel hover:text-ink">&larr; Back to plans</button>
            </div>

            <div
                wire:ignore
                wire:key="stripe-checkout-{{ $checkoutClientSecret }}"
                x-data
                x-init="
                    const stripe = Stripe(@js($stripeKey));
                    stripe.initEmbeddedCheckout({ clientSecret: @js($checkoutClientSecret) })
                        .then((checkout) => checkout.mount($el));
                "
            ></div>
        </div>
    @elseif (! $subscription)
        <div>
            <h2 class="fh-heading text-sm mb-4">Choose a plan</h2>
            <div class="grid sm:grid-cols-2 gap-4">
                @forelse ($plans as $plan)
                    <div class="fh-card flex flex-col">
                        <p class="text-lg font-display font-semibold">{{ $plan->name }}</p>
                        @if ($plan->description)
                            <p class="text-sm text-steel mt-1">{{ $plan->description }}</p>
                        @endif

                        <div class="mt-4 space-y-1 text-sm">
                            <p class="fh-td-mono">${{ $plan->monthly_price }}<span class="text-steel text-xs">/mo</span></p>
                            @if ($plan->yearly_price)
                                <p class="fh-td-mono text-steel">${{ $plan->yearly_price }}<span class="text-xs">/yr</span></p>
                            @endif
                        </div>

                        @if ($plan->features)
                            <ul class="text-sm text-steel mt-4 space-y-1 flex-1">
                                @foreach ($plan->features as $feature)
                                    <li>&bull; {{ $feature }}</li>
                                @endforeach
                            </ul>
                        @else
                            <div class="flex-1"></div>
                        @endif

                        <div class="mt-5 flex gap-2">
                            <button wire:click="subscribe({{ $plan->id }}, 'monthly')" wire:loading.attr="disabled" class="fh-btn-primary flex-1">
                                Subscribe Monthly
                            </button>
                            @if ($plan->yearly_price)
                                <button wire:click="subscribe({{ $plan->id }}, 'yearly')" wire:loading.attr="disabled" class="fh-btn-secondary flex-1">
                                    Yearly
                                </button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-steel">No plans are available yet — check back soon.</p>
                @endforelse
            </div>
        </div>
    @endif

    @if ($invoices->isNotEmpty())
        <div>
            <h2 class="fh-heading text-sm mb-4">Invoice History</h2>
            <div class="fh-card-flush">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr>
                                <th class="fh-th">Date</th>
                                <th class="fh-th">Number</th>
                                <th class="fh-th">Status</th>
                                <th class="fh-th font-mono normal-case tracking-normal">Amount</th>
                                <th class="fh-th"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoices as $invoice)
                                <tr class="fh-tr">
                                    <td class="fh-td-mono">{{ $invoice->date()->format('M j, Y') }}</td>
                                    <td class="fh-td text-steel">{{ $invoice->number ?? '—' }}</td>
                                    <td class="fh-td">
                                        @if ($invoice->status === 'paid')
                                            <span class="fh-pill-good">Paid</span>
                                        @elseif ($invoice->status === 'open')
                                            <span class="fh-pill-warn">Open</span>
                                        @else
                                            <span class="fh-pill-bad">{{ ucfirst($invoice->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="fh-td-mono">{{ $invoice->total() }}</td>
                                    <td class="fh-td text-right whitespace-nowrap">
                                        @if ($invoice->hosted_invoice_url)
                                            <a href="{{ $invoice->hosted_invoice_url }}" target="_blank" rel="noopener" class="fh-link-action text-gold-3">View</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
    <script src="https://js.stripe.com/v3/"></script>
@endpush
