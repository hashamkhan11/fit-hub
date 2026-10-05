<x-mail::message>
# Your subscription has been canceled

Hi {{ $gym->owner()?->name ?? 'there' }},

{{ $gym->name }}'s FitHub subscription has been canceled and dashboard access is now paused. Your data is kept safe — resubscribe any time to pick up right where you left off.

<x-mail::button :url="url('/dashboard/billing')">
Resubscribe
</x-mail::button>

We'd love to know if something didn't work for you — just reply to this email or write to {{ config('app.support_email') }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
