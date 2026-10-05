import 'dart:async';

import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import '../services/api_client.dart';
import '../services/push_notifications.dart';

const _secureStorage = FlutterSecureStorage();

class AuthState {
  final String? token;
  final Map<String, dynamic>? member;

  /// True until the saved-session check on cold start has finished. The
  /// splash screen watches this so it never briefly shows the login screen
  /// to an already-logged-in member.
  final bool restoring;

  const AuthState({this.token, this.member, this.restoring = true});

  bool get isLoggedIn => token != null;
}

class AuthNotifier extends Notifier<AuthState> {
  @override
  AuthState build() {
    _restoreSession();
    return const AuthState();
  }

  Future<void> _restoreSession() async {
    final token = await _secureStorage.read(key: 'token');
    if (token != null) {
      state = AuthState(token: token, restoring: false);
      unawaited(registerPushToken(ApiClient(token: token)));
    } else {
      state = const AuthState(restoring: false);
    }
  }

  Future<void> login(String email, String password) async {
    final client = ApiClient();
    final data = await client.login(email, password);

    await _secureStorage.write(key: 'token', value: data['token'] as String);

    state = AuthState(
      token: data['token'] as String,
      member: data['member'] as Map<String, dynamic>,
      restoring: false,
    );

    unawaited(registerPushToken(ApiClient(token: data['token'] as String)));
  }

  Future<void> logout() async {
    final token = state.token;
    if (token != null) {
      // Try to log out on server too, but still clear local login if this fails.
      try {
        await ApiClient(token: token).logout();
      } catch (_) {}
    }

    await _clearLocalSession();
  }

  /// Clears local login without calling server, used when token is invalid.
  Future<void> forceLogout() async {
    if (state.token == null) return;
    await _clearLocalSession();
  }

  Future<void> _clearLocalSession() async {
    await _secureStorage.delete(key: 'token');
    state = const AuthState(restoring: false);
  }
}

final authProvider = NotifierProvider<AuthNotifier, AuthState>(AuthNotifier.new);

final apiClientProvider = Provider<ApiClient>((ref) {
  final token = ref.watch(authProvider).token;
  return ApiClient(
    token: token,
    onSessionExpired: () => ref.read(authProvider.notifier).forceLogout(),
  );
});
