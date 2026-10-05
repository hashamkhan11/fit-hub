# Gym Floor Lock

ESP32 firmware for a member-facing gym door: a relay-driven lock plus an
optical fingerprint sensor, controlled by the FitHub backend. Unlike
[`office-lock/`](../office-lock) (which is fully standalone), this device
has no UI of its own — it polls the backend for commands and reports back,
and members unlock the door either with a fingerprint or from the mobile
app.

This is intentionally separate firmware from `office-lock/`: that one has
no server dependency at all, this one is meaningless without a FitHub gym
account and a device token issued from its dashboard.

## Hardware

- ESP32 dev board
- Relay module: `VCC → 5V/3V3`, `GND → GND`, `IN → GPIO25`. Active-HIGH
  (`HIGH` = unlock). Boot-default state is locked.
- Optical fingerprint sensor (Adafruit-compatible, UART): `VIN → 5V/3V3`,
  `GND → GND`, sensor TX → GPIO16, sensor RX → GPIO17, 57600 baud.
- BOOT button (GPIO0, built into most dev boards) — hold 3s at power-on to
  wipe saved WiFi/server/token and drop back into setup mode.

## How it talks to the backend

The device never accepts inbound connections in normal operation — it's a
plain polling client, so it works from behind normal gym WiFi/NAT with no
port forwarding:

1. **Poll.** Every second (`POLL_INTERVAL_MS`) it does
   `GET {server}/api/lock/poll` with an `X-Device-Token` header identifying
   this specific device. The backend returns any `pending` commands queued
   for it (`open`, `close`, `enroll`, `delete_fingerprint`).
2. **Act.** The device runs the command — pulses or holds the relay, or
   drives the fingerprint sensor through an enrollment/deletion step.
3. **Ack.** It reports back with
   `POST {server}/api/lock/commands/{id}/ack` (`completed` / `failed` /
   `in_progress`), so the dashboard and the member's app know what
   happened. Command IDs are strictly increasing and the device remembers
   the highest one it has already handled, so a dropped ack (brief WiFi
   blip) can't cause the same command to fire twice on the next poll.
4. **Progress.** Multi-step commands (fingerprint enrollment needs two
   scans) also post interim updates to
   `POST {server}/api/lock/commands/{id}/progress` — this is what lets the
   dashboard show "Place finger again" / "Remove finger" live.
5. **Fingerprint match.** Matching happens on the sensor itself, not the
   backend — the device just reports the matched fingerprint ID to
   `POST {server}/api/fingerprint/scan`, and the backend replies with
   whether that ID is valid and the door should unlock.

All backend calls use the device's own token (issued per-device from the
FitHub dashboard's Lock page), not a user login — see
`backend/app/Http/Controllers/Api/LockController.php` and
`FingerprintController.php` for the server side of this protocol.

HTTPS requests are validated against the backend's real certificate chain
(pinned to the Let's Encrypt root CA — see `FITHUB_ROOT_CA` in
`src/main.cpp`), not skipped.

## Configuration

There's no hardcoded WiFi network or server URL — each device is configured
after flashing, and the config is stored in flash (`Preferences`) so it
survives reboots:

- **First boot / after a reset:** the device has no saved config, so it
  starts its own access point (`FitHub-Lock-Setup`, open/no password — it's
  meant to be used once during install). Join it from a phone and visit the
  IP printed over serial to fill in:
  - the gym's WiFi SSID/password
  - the backend server URL (e.g. `https://fithub.ranksol.net`)
  - the device token from the dashboard's Lock page
- **Over USB serial** (115200 baud), as a fallback if a phone can't join the
  setup AP reliably:
  - `cfg <ssid>|<pass>|<server>|<token>` — full config, same as the web form
  - `seturl <server>` / `settoken <token>` / `setwifi <ssid>|<pass>` —
    update just one piece without redoing the rest
  - `enroll <id>` / `delete <id>` — manual fingerprint enrollment, useful
    for bench testing without the dashboard
  - `status` — quick sensor/WiFi/enrollment snapshot
- **Factory reset:** hold BOOT (GPIO0) for 3 seconds at power-on to wipe
  everything and return to setup mode.

## Build & flash (PlatformIO)

```
pio run -t upload      # build and flash
pio device monitor      # serial console, 9600 baud
```

Dependencies (`ArduinoJson`, Adafruit's Fingerprint Sensor library) are
pulled automatically via `platformio.ini`.

An unauthenticated manual relay-test page (port 81, bypasses the backend
entirely) exists for bench-testing the door hardware before a device is
enrolled with a gym. It's compiled out by default — see the
`ENABLE_TEST_UNLOCK_SERVER` build flag commented in `platformio.ini` — and
should only ever be turned on for a device sitting on an isolated test
network, never one that will end up on a real gym's WiFi.
