<x-mail::message>
# Your trial is ending soon

Hi {{ $gym->owner()?->name ?? 'there' }},

{{ $gym->name }}'s free trial on FitHub ends on **{{ $gym->trial_ends_at->format('M j, Y') }}**. Pick a plan now to keep your dashboard, members, and data without interruption.

<x-mail::button :url="url('/dashboard/billing')">
Choose a plan
</x-mail::button>

Questions? Just reply to this email or reach us at {{ config('app.support_email') }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
