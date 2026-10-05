import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart' show defaultTargetPlatform, kIsWeb, TargetPlatform;
import 'package:http/http.dart' as http;

class OfflineException implements Exception {
  const OfflineException([this.message = "You're offline — check your connection and try again."]);

  final String message;

  @override
  String toString() => 'Exception: $message';
}

class SessionExpiredException implements Exception {
  const SessionExpiredException([this.message = 'Your session has expired — please log in again.']);

  final String message;

  @override
  String toString() => 'Exception: $message';
}

class ApiClient {
  // Real phones can't use 127.0.0.1, so use your PC's LAN IP for testing.
  // The default below is just a placeholder (a typical home-router subnet),
  // not anyone's actual machine - override it with --dart-define=API_HOST=...
  // (or edit the default) to match your own dev machine's current LAN IP.
  static const String _lanIp = String.fromEnvironment('API_HOST', defaultValue: '192.168.1.100');
  static const String _scheme = String.fromEnvironment('API_SCHEME', defaultValue: 'https');
  static const int _port = int.fromEnvironment('API_PORT', defaultValue: 8080);

  static String get baseUrl {
    const override = String.fromEnvironment('API_BASE_URL');
    if (override.isNotEmpty) return override;
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return '$_scheme://$_lanIp:$_port/api';
    }
    return '$_scheme://127.0.0.1:$_port/api';
  }

  /// Same host as [baseUrl] but without `/api`, used for links to web pages.
  static String get webBaseUrl => baseUrl.replaceFirst(RegExp(r'/api$'), '');

  final String? token;
  final http.Client _client;
  final void Function()? onSessionExpired;

  ApiClient({this.token, this.onSessionExpired, http.Client? client}) : _client = client ?? http.Client();

  Map<String, String> get _headers => {
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  /// Runs an HTTP call and turns network/login errors into clear exceptions.
  /// Always bounded by a timeout so a stalled connection can never leave the
  /// UI stuck showing a loading spinner forever.
  Future<http.Response> _send(
    Future<http.Response> Function() request, {
    Duration timeout = const Duration(seconds: 15),
  }) async {
    late final http.Response response;
    try {
      response = await request().timeout(timeout);
    } on TimeoutException {
      throw const OfflineException();
    } on SocketException {
      throw const OfflineException();
    } on http.ClientException {
      throw const OfflineException();
    }

    if (response.statusCode == 401 && token != null) {
      onSessionExpired?.call();
      throw const SessionExpiredException();
    }

    return response;
  }

  Future<Map<String, dynamic>> login(String email, String password) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/login'),
          headers: {'Accept': 'application/json'},
          body: {'email': email, 'password': password},
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> fetchMembership() async {
    final response = await _send(() => _client.get(
          Uri.parse('$baseUrl/member/membership'),
          headers: _headers,
        ));

    if (response.statusCode != 200) {
      throw Exception('Failed to load membership.');
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<void> sendPasswordResetOtp({required String channel, required String identifier}) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/forgot-password'),
          headers: {'Accept': 'application/json'},
          body: {'channel': channel, 'identifier': identifier},
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<void> resetPassword({
    required String channel,
    required String identifier,
    required String otp,
    required String password,
  }) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/reset-password'),
          headers: {'Accept': 'application/json'},
          body: {
            'channel': channel,
            'identifier': identifier,
            'otp': otp,
            'password': password,
            'password_confirmation': password,
          },
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Uri qrCodeUrl() => Uri.parse('$baseUrl/member/qr');

  Future<List<dynamic>> fetchClasses() async {
    final response = await _send(() => _client.get(Uri.parse('$baseUrl/classes'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception('Failed to load classes.');
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['classes'] as List<dynamic>;
  }

  Future<void> bookClass(int classId) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/classes/$classId/book'),
          headers: _headers,
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<void> cancelBooking(int bookingId) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/bookings/$bookingId/cancel'),
          headers: _headers,
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<void> updateFcmToken(String fcmToken) async {
    await _send(() => _client.post(
          Uri.parse('$baseUrl/member/fcm-token'),
          headers: _headers,
          body: {'fcm_token': fcmToken},
        ));
  }

  Future<List<dynamic>> fetchAttendance() async {
    final response = await _send(() => _client.get(Uri.parse('$baseUrl/member/attendance'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception('Failed to load attendance.');
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['attendance'] as List<dynamic>;
  }

  Future<List<dynamic>> fetchMeasurements() async {
    final response = await _send(() => _client.get(Uri.parse('$baseUrl/member/measurements'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception('Failed to load measurements.');
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['measurements'] as List<dynamic>;
  }

  Future<void> logMeasurement({
    required String recordedAt,
    double? weightKg,
    double? bodyFatPercentage,
    double? chestCm,
    double? waistCm,
    double? hipsCm,
    double? armsCm,
    String? notes,
  }) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/member/measurements'),
          headers: _headers,
          body: {
            'recorded_at': recordedAt,
            if (weightKg != null) 'weight_kg': weightKg.toString(),
            if (bodyFatPercentage != null) 'body_fat_percentage': bodyFatPercentage.toString(),
            if (chestCm != null) 'chest_cm': chestCm.toString(),
            if (waistCm != null) 'waist_cm': waistCm.toString(),
            if (hipsCm != null) 'hips_cm': hipsCm.toString(),
            if (armsCm != null) 'arms_cm': armsCm.toString(),
            if (notes != null && notes.isNotEmpty) 'notes': notes,
          },
        ));

    if (response.statusCode != 201) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<Map<String, dynamic>> fetchProfile() async {
    final response = await _send(() => _client.get(Uri.parse('$baseUrl/member'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception('Failed to load profile.');
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  /// Only sends the fields you pass in, so other fields stay unchanged.
  Future<Map<String, dynamic>> updateProfile({String? name, String? phone, double? heightCm, bool clearHeight = false}) async {
    final response = await _send(() => _client.put(
          Uri.parse('$baseUrl/member/profile'),
          headers: _headers,
          body: {
            'name': ?name,
            'phone': ?phone,
            if (heightCm != null)
              'height_cm': heightCm.toString()
            else if (clearHeight)
              'height_cm': '',
          },
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['member'] as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> fetchPaymentHistory() async {
    final response = await _send(() => _client.get(Uri.parse('$baseUrl/member/payments'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception('Failed to load payment history.');
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<Map<String, dynamic>> uploadPhoto(File photo) async {
    final response = await _send(() async {
      final request = http.MultipartRequest('POST', Uri.parse('$baseUrl/member/photo'))
        ..headers.addAll(_headers)
        ..files.add(await http.MultipartFile.fromPath('photo', photo.path));

      final streamed = await _client.send(request);
      return http.Response.fromStream(streamed);
    }, timeout: const Duration(seconds: 45));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['member'] as Map<String, dynamic>;
  }

  Future<List<dynamic>> fetchLockDevices() async {
    final response = await _send(() => _client.get(Uri.parse('$baseUrl/lock/devices'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception('Failed to load lock devices.');
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['devices'] as List<dynamic>;
  }

  Future<int> unlockDevice(int deviceId) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/lock/devices/$deviceId/unlock'),
          headers: _headers,
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['command_id'] as int;
  }

  Future<int> lockDevice(int deviceId) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/lock/devices/$deviceId/lock'),
          headers: _headers,
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['command_id'] as int;
  }

  /// Status is one of: pending, completed, failed, expired.
  Future<String> fetchCommandStatus(int commandId) async {
    final response = await _send(() => _client.get(
          Uri.parse('$baseUrl/lock/commands/$commandId/status'),
          headers: _headers,
        ));

    if (response.statusCode != 200) {
      throw Exception('Failed to check command status.');
    }

    return (jsonDecode(response.body) as Map<String, dynamic>)['status'] as String;
  }

  Future<void> changePassword({required String currentPassword, required String newPassword}) async {
    final response = await _send(() => _client.post(
          Uri.parse('$baseUrl/member/change-password'),
          headers: _headers,
          body: {
            'current_password': currentPassword,
            'new_password': newPassword,
            'new_password_confirmation': newPassword,
          },
        ));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<Map<String, dynamic>> fetchNotifications() async {
    final response = await _send(() => _client.get(Uri.parse('$baseUrl/member/notifications'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception('Failed to load notifications.');
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<void> markNotificationsRead() async {
    await _send(() => _client.post(Uri.parse('$baseUrl/member/notifications/read-all'), headers: _headers));
  }

  Future<void> deleteNotification(int id) async {
    final response = await _send(() => _client.delete(Uri.parse('$baseUrl/member/notifications/$id'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<void> deleteAllNotifications() async {
    final response = await _send(() => _client.delete(Uri.parse('$baseUrl/member/notifications'), headers: _headers));

    if (response.statusCode != 200) {
      throw Exception(_extractError(response.body));
    }
  }

  Future<void> logout() async {
    try {
      await _client.post(Uri.parse('$baseUrl/logout'), headers: _headers).timeout(const Duration(seconds: 15));
    } catch (_) {
      // Just try once — don't block logout if the server call fails.
    }
  }

  String get authToken => token ?? '';

  String _extractError(String body) {
    try {
      final data = jsonDecode(body) as Map<String, dynamic>;
      final errors = data['errors'] as Map<String, dynamic>?;
      if (errors != null && errors.isNotEmpty) {
        return (errors.values.first as List).first.toString();
      }
      return data['message']?.toString() ?? 'Something went wrong.';
    } catch (_) {
      return 'Something went wrong.';
    }
  }
}
