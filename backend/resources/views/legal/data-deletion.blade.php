@extends('layouts.legal')

@section('title', 'Delete My Data')

@section('content')
    <p>
        You can ask to have your personal data deleted from FitHub at any time. Here's how it works and
        what to expect.
    </p>

    <h2>1. How to request deletion</h2>
    <p>
        Members: ask your Gym's front desk or an owner/staff account to remove you as a member. Gym
        staff/trainer accounts: ask the Gym's owner to remove your staff account. If you'd rather not go
        through your Gym directly, you can contact RankSol support ({{ config('app.support_email') }}) and we'll pass the request to the Gym.
    </p>

    <h2>2. What deletion does</h2>
    <p>
        Removing a member clears their email address immediately and marks their record deleted, so it no
        longer appears in day-to-day lists, bookings, or check-ins. Name and historical activity (past
        attendance, class bookings, payment/receipt history) may be retained in the Gym's records — this
        mirrors standard bookkeeping practice, since financial records typically can't simply be erased on
        request.
    </p>

    <h2>3. Outstanding balances</h2>
    <p>
        If you have an unpaid balance on your membership, your Gym will need to resolve that (record the
        payment or write it off) before your record can be removed — otherwise the Gym would lose track of
        money owed to it.
    </p>

    <h2>4. Push notification tokens</h2>
    <p>
        Disabling notifications or uninstalling the mobile app stops new push notifications from being
        sent to that device. Logging out from the app also clears the stored token from your account.
    </p>

    <h2>5. Timing</h2>
    <p>
        Deletion requests made directly by Gym staff take effect immediately. Requests routed through
        RankSol support ({{ config('app.support_email') }}) are typically actioned within a few business days.
    </p>
@endsection
