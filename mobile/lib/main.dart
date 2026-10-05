import 'dart:async';

import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/material.dart';
import 'package:flutter_native_splash/flutter_native_splash.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import 'screens/splash_screen.dart';
import 'services/push_notifications.dart';
import 'theme/app_theme.dart';

void main() async {
  final widgetsBinding = WidgetsFlutterBinding.ensureInitialized();
  FlutterNativeSplash.preserve(widgetsBinding: widgetsBinding);

  // Push setup is best-effort and must never keep the app stuck on the
  // native splash — bounded so a Firebase/network hiccup can't block startup.
  var firebaseReady = false;
  try {
    await Future.wait([
      Firebase.initializeApp(),
      initLocalNotifications(),
    ]).timeout(const Duration(seconds: 10));
    firebaseReady = true;
  } catch (_) {}
  if (firebaseReady) listenForForegroundMessages();

  final container = ProviderContainer();

  runApp(UncontrolledProviderScope(container: container, child: const FitHubApp()));
  // The animated SplashScreen (the app's real first frame) takes over the
  // brand moment from here, so the native splash can come off immediately.
  FlutterNativeSplash.remove();

  // Only needed if app was opened from a notification tap, so it can wait.
  if (firebaseReady) unawaited(initNotificationTapHandling(container));
}

class FitHubApp extends StatelessWidget {
  const FitHubApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'FitHub',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.theme,
      home: const SplashScreen(),
    );
  }
}
