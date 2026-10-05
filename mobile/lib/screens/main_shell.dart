import 'dart:ui';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../providers/auth_provider.dart';
import '../providers/navigation_provider.dart';
import '../theme/app_theme.dart';
import 'attendance_screen.dart';
import 'bmi_screen.dart';
import 'classes_screen.dart';
import 'home_screen.dart';
import 'login_screen.dart';
import 'progress_screen.dart';
import 'settings_screen.dart';

class MainShell extends ConsumerStatefulWidget {
  const MainShell({super.key});

  // Lock tab was removed (it's now a Home quick-action), tabs renumbered.
  static const _tabs = [
    HomeScreen(),
    ClassesScreen(),
    AttendanceScreen(),
    ProgressScreen(),
    BmiScreen(),
    SettingsScreen(),
  ];

  @override
  ConsumerState<MainShell> createState() => _MainShellState();
}

class _MainShellState extends ConsumerState<MainShell> {
  // Only builds a tab's screen once it's actually opened, not all at once.
  final _visited = {0};

  @override
  Widget build(BuildContext context) {
    // Covers both the manual Log Out button and an automatic logout when a
    // stale token gets a 401 — neither has a screen of its own to navigate
    // from, so this is the one place that reacts to auth state changing.
    ref.listen<AuthState>(authProvider, (previous, next) {
      if (!next.isLoggedIn) {
        Navigator.of(context).pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    });

    final index = ref.watch(selectedTabProvider);
    _visited.add(index);

    return Scaffold(
      body: IndexedStack(
        index: index,
        children: [
          for (var i = 0; i < MainShell._tabs.length; i++)
            _visited.contains(i) ? MainShell._tabs[i] : const SizedBox.shrink(),
        ],
      ),
      bottomNavigationBar: Padding(
        padding: const EdgeInsets.fromLTRB(16, 0, 16, 12),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(AppTheme.radiusXl),
          child: BackdropFilter(
            filter: ImageFilter.blur(sigmaX: 20, sigmaY: 20),
            child: Container(
              decoration: BoxDecoration(
                color: AppColors.paper2.withValues(alpha: 0.72),
                borderRadius: BorderRadius.circular(AppTheme.radiusXl),
                border: Border.all(color: AppColors.ink2, width: 1),
                boxShadow: [
                  BoxShadow(color: Colors.black.withValues(alpha: 0.35), blurRadius: 24, offset: const Offset(0, 8)),
                ],
              ),
              child: NavigationBar(
                selectedIndex: index,
                onDestinationSelected: (index) => ref.read(selectedTabProvider.notifier).select(index),
                backgroundColor: Colors.transparent,
                elevation: 0,
                height: 64,
                indicatorColor: AppColors.gold.withValues(alpha: 0.18),
                surfaceTintColor: Colors.transparent,
                labelTextStyle: WidgetStateProperty.resolveWith((states) {
                  final selected = states.contains(WidgetState.selected);
                  return AppTheme.display(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    letterSpacing: 0.5,
                    color: selected ? AppColors.gold : AppColors.steel,
                  );
                }),
                destinations: [
                  NavigationDestination(icon: Icon(Icons.home_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.home, color: AppColors.gold), label: 'Home'),
                  NavigationDestination(icon: Icon(Icons.event_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.event, color: AppColors.gold), label: 'Class'),
                  NavigationDestination(icon: Icon(Icons.qr_code_scanner_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.qr_code_scanner, color: AppColors.gold), label: 'Attend'),
                  NavigationDestination(icon: Icon(Icons.show_chart_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.show_chart, color: AppColors.gold), label: 'Progress'),
                  NavigationDestination(icon: Icon(Icons.monitor_weight_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.monitor_weight, color: AppColors.gold), label: 'BMI'),
                  NavigationDestination(icon: Icon(Icons.settings_outlined, color: AppColors.steel), selectedIcon: const Icon(Icons.settings, color: AppColors.gold), label: 'Settings'),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}
