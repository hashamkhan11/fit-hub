<x-mail::message>
# We couldn't process your payment

Hi {{ $gym->owner()?->name ?? 'there' }},

A payment for {{ $gym->name }}'s FitHub subscription didn't go through. Please update your card details to avoid any interruption to your account.

<x-mail::button :url="url('/dashboard/billing')">
Update payment method
</x-mail::button>

If you believe this is a mistake, contact us at {{ config('app.support_email') }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
