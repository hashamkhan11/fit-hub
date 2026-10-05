# FitHub — Member App

FitHub is a two-sided gym management system. This Flutter app is the member-facing side: gym members log in to view their membership, check in with a QR code, browse and book classes, log body measurements, and receive push notifications — backed by the FitHub Laravel API.

## Tech Stack

- **Framework:** Flutter (Dart)
- **State management:** Riverpod
- **HTTP:** `http` package talking to the Laravel REST API
- **Auth:** Bearer token (Laravel Sanctum), persisted with `shared_preferences`
- **Push notifications:** Firebase Cloud Messaging (`firebase_core`, `firebase_messaging`), with `flutter_local_notifications` to display incoming messages while the app is in the foreground
- **Charts:** `fl_chart` for the progress screen
- **QR code display:** `flutter_svg` (rendered server-side, fetched as SVG)

## Features

- **Login** — token-based auth against the backend.
- **Home** — current membership plan, expiry date, payment status, and a personal QR code for gym check-in.
- **Classes** — browse upcoming classes, book or join a waitlist, cancel a booking.
- **Progress** — log body measurements (weight, body fat %, chest/waist/hips/arms, notes) and view a weight-over-time chart.
- **Push notifications** — class-start reminders and weekly progress reminders, delivered via Firebase Cloud Messaging.

## Setup

### 1. Install dependencies

```bash
flutter pub get
```

### 2. Firebase

Push notifications require a Firebase Android app configured for this project:

1. Register an Android app in the Firebase console with package name `com.ranksol.fithub_app`.
2. Download `google-services.json` and place it at `android/app/google-services.json` (git-ignored — never commit it).

The Google Services Gradle plugin is already wired up in `android/settings.gradle.kts` and `android/app/build.gradle.kts`.

### 3. Point the app at the backend

The API base URL is defined in `lib/services/api_client.dart`:

```dart
static const String _lanIp = String.fromEnvironment('API_HOST', defaultValue: '192.168.1.100');
```

- Running on a physical Android device: the app uses `http://<_lanIp>:8080/api`. Update the default in `api_client.dart`, or pass `--dart-define=API_HOST=...`, to match your development machine's current LAN IP (find it with `ipconfig` on Windows).
- Running on Android emulator/web: it falls back to `http://127.0.0.1:8080/api` automatically.

Make sure the backend is running and reachable on port 8080 (Laragon/Apache on this project — not `php artisan serve`) from the phone, not just the host machine.

### 4. Run

The app defaults to **HTTPS** (fail-safe for production). Local dev against Laragon/Apache is HTTP-only, so pass the scheme override explicitly:

```bash
flutter run --dart-define=API_SCHEME=http
```

Or build a debug APK to install manually:

```bash
flutter build apk --debug --dart-define=API_SCHEME=http
```

The APK is output to `build/app/outputs/flutter-apk/app-debug.apk`.

### Notifications on Android 13+

The app requests the `POST_NOTIFICATIONS` runtime permission on first login (in addition to the manifest declaration already in `AndroidManifest.xml`). Notifications from a backgrounded/terminated app show in the system tray automatically via FCM. For the foreground case — FCM notification payloads never auto-display while the app is open — `initPushNotifications()` in `push_notifications.dart` listens on `FirebaseMessaging.onMessage` and shows the notification manually via `flutter_local_notifications`.

`flutter_local_notifications` requires Android core library desugaring; this is already enabled in `android/app/build.gradle.kts` (`isCoreLibraryDesugaringEnabled = true` + the `desugar_jdk_libs` dependency) — don't remove it or release builds will fail with a "requires core library desugaring" AAR metadata error.

### Production release build

The app has no compiled-in production URL — without `API_BASE_URL`, it falls back to a LAN dev IP that is unreachable off your home network. Every release build (AAB for Play Store, or a release APK) **must** pass the live backend URL explicitly:

```bash
flutter build appbundle --release --dart-define=API_BASE_URL=https://fithub.ranksol.net/api
```

There is no default for this on purpose — a forgotten flag should fail loudly (app can't reach anything, caught in testing) rather than silently shipping a dev IP to real users.

## Testing

```bash
flutter test
```

`test/api_client_test.dart` covers `ApiClient` (login, class booking, fetching classes/attendance/measurements, and error-message extraction) using `package:http/testing.dart`'s `MockClient` — no backend server needed. `test/widget_test.dart` is a basic widget test asserting the login screen shows when logged out.

## Project Structure

```
lib/
├── main.dart                  # App entry point, Firebase init
├── providers/
│   └── auth_provider.dart     # Login/session state, triggers FCM token registration
├── services/
│   ├── api_client.dart        # All backend API calls
│   └── push_notifications.dart # FCM permission + token registration + foreground local-notification display
├── widgets/
│   └── logout_action.dart     # Shared AppBar logout button, used on every tab
└── screens/
    ├── login_screen.dart
    ├── main_shell.dart         # Bottom nav bar shell (Home/Classes/Attendance/Progress)
    ├── home_screen.dart        # Membership + QR code
    ├── classes_screen.dart     # Browse/book/cancel classes
    ├── attendance_screen.dart  # Check-in/check-out history
    └── progress_screen.dart    # Log measurements + progress chart
```

## Notes

Feature scope follows the fixed FitHub client project spec.
