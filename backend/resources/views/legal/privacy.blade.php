@extends('layouts.legal')

@section('title', 'Privacy Policy')

@section('content')
    <p>
        This Privacy Policy explains what personal data FitHub collects, why, and how it's used, for Gym
        staff and Members using the web dashboard or mobile app.
    </p>

    <h2>1. Data we collect</h2>
    <p>For Members, this includes:</p>
    <ul>
        <li>Contact and identity details entered at enrollment — name, email, phone number.</li>
        <li>Membership and payment records — plan, price, payment status, receipts.</li>
        <li>Attendance and class booking history, and progress measurements you choose to log.</li>
        <li>A profile photo, if you upload one.</li>
        <li>A push-notification token, if you enable notifications on the mobile app.</li>
        <li>Door-lock unlock/lock requests, where your Gym uses FitHub-connected locks — including which
            device, when, and the outcome.</li>
        <li>A fingerprint template, where your Gym uses a FitHub fingerprint scanner for attendance or
            entry — captured on the scanner hardware and used only to match you to your account.</li>
    </ul>
    <p>For Gym staff, we collect the account details needed to operate the dashboard — name, email, and role.</p>

    <h2>2. Why we collect it</h2>
    <p>
        Data is collected to run the core features of the Service: enrolling and billing members,
        scheduling and booking classes, tracking attendance and progress, sending reminders, and operating
        door access where installed. We do not sell personal data.
    </p>

    <h2>3. Who we share it with</h2>
    <p>Data is shared only with the service providers that power specific features, and only as needed to provide them:</p>
    <ul>
        <li>Payment processing for a Gym's own FitHub subscription, via our payment processor.</li>
        <li>Push notifications, via Firebase Cloud Messaging.</li>
        <li>Transactional email (renewal and class reminders, password resets), via our email provider.</li>
        <li>SMS reminders, via our SMS provider, where a phone number is on file.</li>
        <li>Error monitoring, which may capture technical details of a failure (not routine personal data)
            to help us fix bugs.</li>
    </ul>
    <p>
        Data entered under a Gym's account is only visible to that Gym's own staff and trainers — Gyms
        cannot see another Gym's members or data.
    </p>

    <h2>4. Data retention</h2>
    <p>
        We keep membership and payment records for as long as the Gym's account is active, and for a
        reasonable period after to satisfy financial record-keeping obligations. See our
        <a href="{{ route('legal.data-deletion') }}" class="underline">data deletion page</a> for how to
        request removal of your data.
    </p>

    <h2>5. Security</h2>
    <p>
        Passwords are stored hashed, not in plain text. Access to a Gym's data is restricted to that Gym's
        authenticated staff, trainers, and members, scoped by role. Door-lock devices authenticate with a
        long-lived secret token rather than a member credential.
    </p>

    <h2>6. Your rights</h2>
    <p>
        You can review and correct your profile details from the mobile app, or by asking your Gym's staff.
        You can request a copy or deletion of your data at any time — see the
        <a href="{{ route('legal.data-deletion') }}" class="underline">data deletion page</a>.
    </p>

    <h2>7. Children</h2>
    <p>
        The Service is intended for use by adults enrolling in a Gym's membership. Gyms are responsible for
        obtaining appropriate consent when enrolling a minor.
    </p>

    <h2>8. Changes to this policy</h2>
    <p>
        We may update this policy as the Service evolves. Material changes will be reflected here with an
        updated date.
    </p>
@endsection
