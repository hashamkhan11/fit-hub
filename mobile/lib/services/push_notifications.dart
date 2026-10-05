import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../providers/navigation_provider.dart';
import 'api_client.dart';

const _lastFcmTokenKey = 'last_registered_fcm_token';

const _channel = AndroidNotificationChannel(
  'fithub_default',
  'FitHub notifications',
  description: 'Booking, class, progress and renewal reminders',
  importance: Importance.high,
);

final _localNotifications = FlutterLocalNotificationsPlugin();

/// Doesn't need Firebase, so it can run at the same time as Firebase setup.
Future<void> initLocalNotifications() async {
  await _localNotifications.initialize(
    const InitializationSettings(
      android: AndroidInitializationSettings('@mipmap/ic_launcher'),
    ),
  );

  await _localNotifications
      .resolvePlatformSpecificImplementation<
          AndroidFlutterLocalNotificationsPlugin>()
      ?.createNotificationChannel(_channel);
}

/// Needs Firebase already started — call this after Firebase setup finishes.
void listenForForegroundMessages() {
  FirebaseMessaging.onMessage.listen(_showForegroundNotification);
}

void _showForegroundNotification(RemoteMessage message) {
  final notification = message.notification;
  if (notification == null) return;

  _localNotifications.show(
    notification.hashCode,
    notification.title,
    notification.body,
    NotificationDetails(
      android: AndroidNotificationDetails(
        _channel.id,
        _channel.name,
        channelDescription: _channel.description,
        importance: Importance.high,
        priority: Priority.high,
      ),
    ),
  );
}

Future<void> initNotificationTapHandling(ProviderContainer container) async {
  final initialMessage = await FirebaseMessaging.instance.getInitialMessage();
  if (initialMessage != null) {
    _routeToNotificationTab(initialMessage, container);
  }

  FirebaseMessaging.onMessageOpenedApp.listen(
    (message) => _routeToNotificationTab(message, container),
  );
}

void _routeToNotificationTab(RemoteMessage message, ProviderContainer container) {
  final index = notificationTypeTabIndex[message.data['type']];
  if (index != null) {
    container.read(selectedTabProvider.notifier).select(index);
  }
}

/// Skips sending the token again if it hasn't changed since last time.
/// Best-effort: push setup must never block or break login on devices
/// without working Google Play Services (getToken() can hang forever there).
Future<void> registerPushToken(ApiClient client) async {
  try {
    final messaging = FirebaseMessaging.instance;

    await messaging.requestPermission().timeout(const Duration(seconds: 10));

    final token = await messaging.getToken().timeout(const Duration(seconds: 10));
    if (token == null) return;

    final prefs = await SharedPreferences.getInstance();
    if (prefs.getString(_lastFcmTokenKey) == token) return;

    await client.updateFcmToken(token);
    await prefs.setString(_lastFcmTokenKey, token);
  } catch (_) {
    // Ignore — the app works fine without push notifications.
  }
}
