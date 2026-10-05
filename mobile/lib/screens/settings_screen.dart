import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/page_header.dart';
import '../widgets/zine.dart';
import 'legal_screen.dart';
import 'password_screen.dart';
import 'payment_history_screen.dart';
import 'profile_screen.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  Future<void> _confirmLogout(BuildContext context, WidgetRef ref) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (dialogContext) => AlertDialog(
        title: const Text('LOG OUT'),
        content: const Text('Are you sure you want to log out?'),
        actions: [
          TextButton(onPressed: () => Navigator.of(dialogContext).pop(false), child: const Text('CANCEL')),
          PressScale(
            onTap: () => Navigator.of(dialogContext).pop(true),
            child: IgnorePointer(
              child: TextButton(
                onPressed: () => Navigator.of(dialogContext).pop(true),
                child: Text('LOG OUT', style: AppTheme.body(color: AppColors.tape, fontWeight: FontWeight.w700)),
              ),
            ),
          ),
        ],
      ),
    );

    if (confirmed != true) return;
    if (!context.mounted) return;

    // The server-side logout call can take a moment; show a blocking
    // spinner so the tap has visible feedback instead of the screen looking
    // frozen. MainShell's auth listener replaces the whole route stack with
    // LoginScreen once logout succeeds, which dismisses this dialog too —
    // the explicit pop here only matters if logout throws before that.
    showDialog<void>(
      context: context,
      barrierDismissible: false,
      builder: (_) => const Center(child: CircularProgressIndicator()),
    );

    try {
      await ref.read(authProvider.notifier).logout();
    } catch (_) {
      // logout() only throws for a genuine local failure (e.g. secure
      // storage) — auth state never changed, so MainShell's listener never
      // fired and this dialog is still open. Dismiss it ourselves.
      //
      // On the success path we deliberately do NOT pop here: MainShell's
      // pushAndRemoveUntil has already replaced the entire route stack
      // (this dialog included) by the time logout() returns. Calling
      // Navigator.pop() here as well raced that replacement — context.mounted
      // was still true for one frame after the stack swap, so the pop landed
      // on the freshly-pushed LoginScreen route instead of the dialog,
      // leaving an empty Navigator (black screen).
      if (context.mounted) Navigator.of(context, rootNavigator: true).pop();
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final client = ref.watch(apiClientProvider);
    final member = ref.watch(memberProfileProvider).asData?.value;
    final fullName = (member?['name'] as String?)?.trim() ?? '';
    final firstName = fullName.isEmpty ? '' : fullName.split(RegExp(r'\s+')).first;
    final photoUrl = member?['photo_url'] as String?;

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 12, 20, 8),
              child: PageHeader(
                title: 'Settings',
                showBackButton: false,
                leading: Avatar(
                  photoUrl: photoUrl,
                  name: firstName,
                  authToken: client.authToken,
                  size: 60,
                  borderWidth: 2.5,
                ),
              ),
            ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(20, 0, 20, 20),
                children: [
                  _SettingsRow(
                    icon: Icons.person_outline,
                    label: 'Profile',
                    onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const ProfileScreen())),
                  ),
                  _SettingsRow(
                    icon: Icons.lock_outline,
                    label: 'Change Password',
                    onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const PasswordScreen())),
                  ),
                  _SettingsRow(
                    icon: Icons.receipt_long_outlined,
                    label: 'Payment History',
                    onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const PaymentHistoryScreen())),
                  ),
                  const SizedBox(height: 24),
                  const _LegalCard(),
                  const SizedBox(height: 24),
                  _SettingsRow(
                    icon: Icons.logout,
                    label: 'Log Out',
                    destructive: true,
                    onTap: () => _confirmLogout(context, ref),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _SettingsRow extends StatelessWidget {
  const _SettingsRow({
    required this.icon,
    required this.label,
    required this.onTap,
    this.destructive = false,
  });

  final IconData icon;
  final String label;
  final VoidCallback onTap;
  final bool destructive;

  @override
  Widget build(BuildContext context) {
    final iconColor = destructive ? AppColors.tape : AppColors.gold;
    final labelColor = destructive ? AppColors.tape : AppColors.ink;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(AppTheme.radiusMd),
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
          child: Row(
            children: [
              Icon(icon, color: iconColor),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  label,
                  style: AppTheme.display(fontSize: 14, fontWeight: FontWeight.w600, color: labelColor),
                ),
              ),
              if (!destructive) const Icon(Icons.chevron_right, color: AppColors.steel),
            ],
          ),
        ),
      ),
    );
  }
}

class _LegalCard extends StatelessWidget {
  const _LegalCard();

  void _open(BuildContext context, String title, List<LegalSection> sections) {
    Navigator.of(context).push(
      MaterialPageRoute(builder: (_) => LegalScreen(title: title, sections: sections)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text('LEGAL', style: AppTheme.display(fontSize: 13, fontWeight: FontWeight.w600, letterSpacing: 1)),
            const SizedBox(height: 4),
            _LegalLink(label: 'Terms of Service', onTap: () => _open(context, 'Terms of Service', LegalScreen.terms())),
            _LegalLink(label: 'Privacy Policy', onTap: () => _open(context, 'Privacy Policy', LegalScreen.privacy())),
            _LegalLink(label: 'Delete My Data', onTap: () => _open(context, 'Delete My Data', LegalScreen.dataDeletion())),
          ],
        ),
      ),
    );
  }
}

class _LegalLink extends StatelessWidget {
  const _LegalLink({required this.label, required this.onTap});

  final String label;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 10),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(label, style: AppTheme.mono(fontSize: 13, color: AppColors.ink)),
            const Icon(Icons.chevron_right, size: 18, color: AppColors.steel),
          ],
        ),
      ),
    );
  }
}
