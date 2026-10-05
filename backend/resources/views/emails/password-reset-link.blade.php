<x-mail::message>
# Reset your password

We received a request to reset the password for your {{ $panelLabel }} account.

<x-mail::button :url="$resetUrl">
Reset password
</x-mail::button>

This link expires in 60 minutes and can only be used once. If you didn't request this, you can safely ignore this email — your password won't be changed.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
