import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:fithub_app/main.dart';

const _secureStorageChannel = MethodChannel('plugins.it_nomads.com/flutter_secure_storage');

void main() {
  setUp(() {
    // AuthNotifier reads a saved token on startup via flutter_secure_storage,
    // which has no platform implementation under `flutter test` — without a
    // mock, that read() never resolves and the splash screen waits forever.
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(_secureStorageChannel, (call) async {
      if (call.method == 'read') return null;
      return null;
    });
  });

  tearDown(() {
    TestDefaultBinaryMessengerBinding.instance.defaultBinaryMessenger
        .setMockMethodCallHandler(_secureStorageChannel, null);
  });

  testWidgets('shows login screen when logged out', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: FitHubApp()));
    // SplashScreen holds for a minimum 1100ms before replacing itself with
    // LoginScreen (see splash_screen.dart) — advance past that plus the
    // route transition before asserting on what's underneath it.
    await tester.pump(const Duration(milliseconds: 1100));
    await tester.pumpAndSettle();

    expect(find.text('FITHUB'), findsOneWidget);
    expect(find.text('LOG IN'), findsOneWidget);
  });
}
