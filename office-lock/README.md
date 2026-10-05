# Office Door Lock

Standalone ESP32 firmware for a WiFi-controlled door relay. No backend, no
database, no companion app — the ESP32 hosts its own login-protected web
page directly on the office WiFi network.

This is intentionally separate from [`esp32-lock/`](../esp32-lock), which is
the gym-floor lock that polls the FitHub backend. This one has no server
dependency at all: point a phone browser at it and it just works.

## Hardware

- ESP32 dev board
- Relay module: `VCC → 3V3`, `GND → GND`, `IN → GPIO27`
- Relay is active-HIGH (`HIGH` = unlock). Boot-default state is locked.

## First-time setup

1. Flash with PlatformIO (`pio run -t upload`).
2. On first boot (or after a factory reset) the device has no saved WiFi, so
   it starts its own access point: **OfficeDoor-Setup**. Its password is
   generated per device (`door-` plus a 6-character code from the chip's own
   ID, e.g. `door-3FA9C2`), not a fixed string shared by every unit - read it
   off the USB serial console at boot (115200 baud), or send `appass` over
   serial at any time to print it again.
3. Visit `http://192.168.4.1/` and fill in the office WiFi credentials plus
   an admin username/password. WiFi must be 2.4GHz — the ESP32 cannot join
   5GHz networks.
4. Saving restarts the device onto the office network. From then on it's
   reachable at `http://doorlock.local/` (mDNS) or its IP shown over serial.

## Day to day

- Visit the device's page, sign in, tap **Unlock**. The relay pulses for
  ~1 second then relocks itself.
- The admin account (`/admin`) can add/remove additional users (each gets
  their own login) and see the device's IP, WiFi network, and uptime.
- **Reset WiFi** (in `/admin`) clears the saved network only, so the device
  drops back into setup mode without losing the user list.
- Holding the **BOOT** button for 3 seconds at power-on does a full factory
  reset (WiFi + admin + all users).

## Recovery without the web UI

Connected over USB serial (115200 baud), the firmware accepts plain-text
commands as a fallback if the network side is unreachable:

```
status                  # WiFi/heap/user-count snapshot
appass                  # reprint this device's setup AP password
listusers
adduser name|password
deluser name
resetwifi
factoryreset
```

## Notes

- All config (WiFi, admin login, user list) is stored in the ESP32's flash
  (`Preferences`) and survives power loss/reboots. Login passwords (admin and
  per-user) are stored as a salted SHA-256 hash, never plaintext - only the
  office WiFi password is kept plaintext, since the device needs it back to
  join the network.
- The setup AP password is derived per device from its chip ID (see
  `deviceIdSuffix()` in `src/main.cpp`) instead of a fixed string, so reading
  it off one unit doesn't give access to another.
- Both the setup AP and the day-to-day admin page are plain HTTP - fine for a
  private office LAN, but there's no transport encryption. Full TLS for a
  device that's also its own captive portal (self-signed cert, client trust
  prompts) is a bigger job than this firmware takes on; hashing at rest is
  the practical fix here.
