<x-mail::message>
# Your password was just changed

The password for your {{ $panelLabel }} account was changed just now.

If this was you, no action is needed. If you don't recognize this change, contact us right away at {{ config('app.support_email') }}.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
