import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../theme/app_theme.dart';
import '../widgets/zine.dart';
import 'login_screen.dart';
import 'main_shell.dart';

/// First screen shown once the native cold-start splash hands off to
/// Flutter. Holds the brand moment on screen for a minimum duration and
/// waits for the saved-session check to finish, so a logged-in member never
/// sees a login-screen flash before landing in the app.
class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  bool _minDurationElapsed = false;
  bool _navigated = false;
  ProviderSubscription<AuthState>? _authSub;

  @override
  void initState() {
    super.initState();
    Timer(const Duration(milliseconds: 1100), () {
      _minDurationElapsed = true;
      _maybeNavigate();
    });
    _authSub = ref.listenManual(authProvider, (previous, next) => _maybeNavigate());
  }

  @override
  void dispose() {
    _authSub?.close();
    super.dispose();
  }

  void _maybeNavigate() {
    if (_navigated || !mounted) return;
    if (!_minDurationElapsed || ref.read(authProvider).restoring) return;

    _navigated = true;
    final loggedIn = ref.read(authProvider).isLoggedIn;
    Navigator.of(context).pushReplacement(
      MaterialPageRoute(builder: (_) => loggedIn ? const MainShell() : const LoginScreen()),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.voidBg,
      body: Center(
        child: Reveal(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Image.asset('assets/images/branding/logo_splash.png', width: 132, height: 132),
              const SizedBox(height: 22),
              RichText(
                text: TextSpan(
                  children: [
                    TextSpan(
                      text: 'Fit',
                      style: AppTheme.display(fontSize: 32, fontWeight: FontWeight.w800, color: AppColors.ink, letterSpacing: 0.4),
                    ),
                    TextSpan(
                      text: 'Hub',
                      style: AppTheme.display(fontSize: 32, fontWeight: FontWeight.w800, color: AppColors.gold, letterSpacing: 0.4),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
