@extends('layouts.legal')

@section('title', 'Terms of Service')

@section('content')
    <p>
        These Terms of Service ("Terms") govern use of FitHub, a gym management platform provided by
        RankSol ("RankSol", "we", "us") to gyms and fitness studios ("Gyms") and their staff and members
        ("Members") through the web dashboard and mobile app (together, the "Service").
    </p>

    <h2>1. Who these Terms apply to</h2>
    <p>
        A Gym's owner agrees to these Terms either by signing up for the Service directly or when RankSol
        provisions their account on their behalf. Staff and trainers use the Service under that Gym's
        account. Members use the Service under the Gym's authorization once enrolled by Gym staff — Members
        do not create their own accounts directly.
    </p>

    <h2>2. What the Service does</h2>
    <p>
        FitHub lets Gyms manage member enrollment, plans and billing, class scheduling and bookings,
        attendance and check-ins, progress tracking, staff/trainer accounts, and (where installed) app-based
        access to smart door locks. Members use the mobile app to view their membership, book classes,
        check in, log progress, and request door access.
    </p>

    <h2>3. Accounts and access</h2>
    <p>
        Gyms are responsible for the accuracy of information they enter about their staff and members, and
        for promptly removing access for staff who leave. Members are responsible for keeping their login
        credentials confidential and for notifying their Gym if they suspect unauthorized access.
    </p>

    <h2>4. Payments</h2>
    <p>
        Gyms subscribe to FitHub on a recurring billing basis; payment is processed by our payment
        processor and is subject to that processor's own terms. A Gym's handling of its own members'
        membership payments (fees, plan pricing, refunds) is between the Gym and its Members — RankSol
        operates the software but is not a party to that relationship.
    </p>

    <h2>5. Acceptable use</h2>
    <p>
        The Service may not be used to store or transmit unlawful content, to attempt to access another
        Gym's data, to circumvent access controls (including door lock controls), or to interfere with the
        Service's normal operation.
    </p>

    <h2>6. Door lock access</h2>
    <p>
        Where a Gym has installed FitHub-connected door locks, unlock/lock requests are logged and
        time-limited. RankSol is not responsible for physical security decisions the Gym makes, including
        who it authorizes for access.
    </p>

    <h2>7. Availability</h2>
    <p>
        We aim to keep the Service available but do not guarantee uninterrupted access. Scheduled
        maintenance and unplanned outages may occur.
    </p>

    <h2>8. Termination</h2>
    <p>
        A Gym may stop using the Service at any time. RankSol may suspend or terminate access for a Gym
        that violates these Terms or fails to pay for the Service.
    </p>

    <h2>9. Changes to these Terms</h2>
    <p>
        We may update these Terms from time to time. Continued use of the Service after an update means
        you accept the revised Terms.
    </p>

    <h2>10. Contact</h2>
    <p>
        Questions about these Terms can be directed to your Gym's staff, or to RankSol support at
        {{ config('app.support_email') }}.
    </p>
@endsection
