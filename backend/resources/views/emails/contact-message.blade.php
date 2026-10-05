<x-mail::message>
# New message from the FitHub website

**Name:** {{ $senderName }}<br>
**Email:** {{ $senderEmail }}<br>
@if ($gymName)
**Gym:** {{ $gymName }}<br>
@endif

**Message:**

{{ $messageBody }}

Thanks,<br>
{{ config('app.name') }} website
</x-mail::message>
