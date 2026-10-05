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
   it starts its own access point: join **OfficeDoor-Setup** (password
   `setup1234`) from a phone or laptop.
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
listusers
adduser name|password
deluser name
resetwifi
factoryreset
```

## Notes

- All config (WiFi, admin login, user list) is stored in the ESP32's flash
  (`Preferences`) and survives power loss/reboots.
- Change the default setup AP password (`AP_PASS` in `src/main.cpp`) if the
  office network is somewhere physically accessible to the public during
  the setup window.
