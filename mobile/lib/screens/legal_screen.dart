import 'package:flutter/material.dart';

import '../theme/app_theme.dart';
import '../widgets/page_header.dart';

const _supportEmail = 'ranksolcompany@gmail.com';

class LegalSection {
  const LegalSection(this.heading, this.body);

  final String? heading;
  final String body;
}

/// Shows Terms/Privacy/Data-deletion pages inside the app, in app style.
class LegalScreen extends StatelessWidget {
  const LegalScreen({super.key, required this.title, required this.sections});

  final String title;
  final List<LegalSection> sections;

  static List<LegalSection> terms() => const [
        LegalSection(
          null,
          'These Terms of Service ("Terms") govern use of FitHub, a gym management platform provided by '
              'RankSol ("RankSol", "we", "us") to gyms and fitness studios ("Gyms") and their staff and members '
              '("Members") through the web dashboard and mobile app (together, the "Service").',
        ),
        LegalSection(
          '1. Who these Terms apply to',
          "A Gym's owner agrees to these Terms either by signing up for the Service directly or when RankSol "
              'provisions their account on their behalf. Staff and trainers use the Service under that Gym\'s '
              'account. Members use the Service under the Gym\'s authorization once enrolled by Gym staff — Members '
              'do not create their own accounts directly.',
        ),
        LegalSection(
          '2. What the Service does',
          'FitHub lets Gyms manage member enrollment, plans and billing, class scheduling and bookings, '
              'attendance and check-ins, progress tracking, staff/trainer accounts, and (where installed) app-based '
              'access to smart door locks. Members use the mobile app to view their membership, book classes, '
              'check in, log progress, and request door access.',
        ),
        LegalSection(
          '3. Accounts and access',
          'Gyms are responsible for the accuracy of information they enter about their staff and members, and '
              'for promptly removing access for staff who leave. Members are responsible for keeping their login '
              'credentials confidential and for notifying their Gym if they suspect unauthorized access.',
        ),
        LegalSection(
          '4. Payments',
          "Gyms subscribe to FitHub on a recurring billing basis; payment is processed by our payment "
              "processor and is subject to that processor's own terms. A Gym's handling of its own members' "
              'membership payments (fees, plan pricing, refunds) is between the Gym and its Members — RankSol '
              'operates the software but is not a party to that relationship.',
        ),
        LegalSection(
          '5. Acceptable use',
          'The Service may not be used to store or transmit unlawful content, to attempt to access another '
              "Gym's data, to circumvent access controls (including door lock controls), or to interfere with the "
              "Service's normal operation.",
        ),
        LegalSection(
          '6. Door lock access',
          'Where a Gym has installed FitHub-connected door locks, unlock/lock requests are logged and '
              'time-limited. RankSol is not responsible for physical security decisions the Gym makes, including '
              'who it authorizes for access.',
        ),
        LegalSection(
          '7. Availability',
          'We aim to keep the Service available but do not guarantee uninterrupted access. Scheduled '
              'maintenance and unplanned outages may occur.',
        ),
        LegalSection(
          '8. Termination',
          'A Gym may stop using the Service at any time. RankSol may suspend or terminate access for a Gym '
              'that violates these Terms or fails to pay for the Service.',
        ),
        LegalSection(
          '9. Changes to these Terms',
          'We may update these Terms from time to time. Continued use of the Service after an update means '
              'you accept the revised Terms.',
        ),
        LegalSection(
          '10. Contact',
          "Questions about these Terms can be directed to your Gym's staff, or to RankSol support at "
              '$_supportEmail.',
        ),
      ];

  static List<LegalSection> privacy() => const [
        LegalSection(
          null,
          "This Privacy Policy explains what personal data FitHub collects, why, and how it's used, for Gym "
              'staff and Members using the web dashboard or mobile app.',
        ),
        LegalSection(
          '1. Data we collect',
          'For Members, this includes contact and identity details entered at enrollment (name, email, phone '
              'number); membership and payment records (plan, price, payment status, receipts); attendance and '
              'class booking history and progress measurements you choose to log; a profile photo, if you upload '
              'one; a push-notification token, if you enable notifications; and door-lock unlock/lock requests, '
              'where your Gym uses FitHub-connected locks — including which device, when, and the outcome.\n\n'
              'For Gym staff, we collect the account details needed to operate the dashboard — name, email, and role.',
        ),
        LegalSection(
          '2. Why we collect it',
          'Data is collected to run the core features of the Service: enrolling and billing members, '
              'scheduling and booking classes, tracking attendance and progress, sending reminders, and operating '
              'door access where installed. We do not sell personal data.',
        ),
        LegalSection(
          '3. Who we share it with',
          'Data is shared only with the service providers that power specific features, and only as needed: '
              "payment processing for a Gym's own FitHub subscription; push notifications via Firebase Cloud "
              'Messaging; transactional email (renewal and class reminders, password resets) via our email '
              'provider; SMS reminders via our SMS provider, where a phone number is on file; and error '
              'monitoring, which may capture technical details of a failure (not routine personal data) to help '
              'us fix bugs.\n\n'
              "Data entered under a Gym's account is only visible to that Gym's own staff and trainers — Gyms "
              "cannot see another Gym's members or data.",
        ),
        LegalSection(
          '4. Data retention',
          "We keep membership and payment records for as long as the Gym's account is active, and for a "
              'reasonable period after to satisfy financial record-keeping obligations. See "Delete My Data" for '
              'how to request removal of your data.',
        ),
        LegalSection(
          '5. Security',
          "Passwords are stored hashed, not in plain text. Access to a Gym's data is restricted to that Gym's "
              'authenticated staff, trainers, and members, scoped by role. Door-lock devices authenticate with a '
              'long-lived secret token rather than a member credential.',
        ),
        LegalSection(
          '6. Your rights',
          "You can review and correct your profile details from the mobile app, or by asking your Gym's staff. "
              'You can request a copy or deletion of your data at any time — see "Delete My Data".',
        ),
        LegalSection(
          '7. Children',
          "The Service is intended for use by adults enrolling in a Gym's membership. Gyms are responsible for "
              'obtaining appropriate consent when enrolling a minor.',
        ),
        LegalSection(
          '8. Changes to this policy',
          'We may update this policy as the Service evolves. Material changes will be reflected here with an '
              'updated date.',
        ),
      ];

  static List<LegalSection> dataDeletion() => const [
        LegalSection(
          null,
          'You can ask to have your personal data deleted from FitHub at any time. Here\'s how it works and '
              'what to expect.',
        ),
        LegalSection(
          '1. How to request deletion',
          "Members: ask your Gym's front desk or an owner/staff account to remove you as a member. Gym "
              "staff/trainer accounts: ask the Gym's owner to remove your staff account. If you'd rather not go "
              'through your Gym directly, you can contact RankSol support ($_supportEmail) and we\'ll pass the '
              'request to the Gym.',
        ),
        LegalSection(
          '2. What deletion does',
          'Removing a member clears their email address immediately and marks their record deleted, so it no '
              'longer appears in day-to-day lists, bookings, or check-ins. Name and historical activity (past '
              'attendance, class bookings, payment/receipt history) may be retained in the Gym\'s records — this '
              "mirrors standard bookkeeping practice, since financial records typically can't simply be erased on "
              'request.',
        ),
        LegalSection(
          '3. Outstanding balances',
          'If you have an unpaid balance on your membership, your Gym will need to resolve that (record the '
              "payment or write it off) before your record can be removed — otherwise the Gym would lose track of "
              'money owed to it.',
        ),
        LegalSection(
          '4. Push notification tokens',
          'Disabling notifications or uninstalling the mobile app stops new push notifications from being '
              'sent to that device. Logging out from the app also clears the stored token from your account.',
        ),
        LegalSection(
          '5. Timing',
          'Deletion requests made directly by Gym staff take effect immediately. Requests routed through '
              'RankSol support ($_supportEmail) are typically actioned within a few business days.',
        ),
      ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: PageHeader(title: title, fontSize: 24),
            ),
            Expanded(
              child: ListView.separated(
                padding: const EdgeInsets.fromLTRB(20, 4, 20, 32),
                itemCount: sections.length,
                separatorBuilder: (_, _) => const SizedBox(height: 18),
                itemBuilder: (context, index) {
                  final section = sections[index];
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (section.heading != null) ...[
                        Text(
                          section.heading!,
                          style: AppTheme.display(fontSize: 15, fontWeight: FontWeight.w700, color: AppColors.gold),
                        ),
                        const SizedBox(height: 6),
                      ],
                      Text(
                        section.body,
                        style: AppTheme.body(fontSize: 14, color: AppColors.ink),
                      ),
                    ],
                  );
                },
              ),
            ),
          ],
        ),
      ),
    );
  }
}
