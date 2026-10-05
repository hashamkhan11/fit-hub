#include <Arduino.h>
#include <WiFi.h>
#include <WebServer.h>
#include <ESPmDNS.h>
#include <Preferences.h>
#include "mbedtls/sha256.h"

// Standalone office door lock. No backend, no database, no companion app —
// the ESP32 hosts its own login-protected web page on the office WiFi.
// Reachable at http://doorlock.local/ once joined to the network.
//
// Relay wiring: VCC->3V3, GND->GND, IN->GPIO27. HIGH = unlock, LOW = locked
// (also the boot-default state).
#define RELAY_PIN 27
#define BOOT_BUTTON_PIN 0        // hold 3s at power-on to factory reset
#define AP_SSID "OfficeDoor-Setup"
#define MDNS_NAME "doorlock"     // -> http://doorlock.local/

const unsigned long UNLOCK_PULSE_MS = 1000;
const unsigned long WIFI_CONNECT_TIMEOUT_MS = 20000;
const int MAX_USERS = 6;
const int MAX_LOG = 10;

// Simple teal padlock icon, used as favicon and PWA home-screen icon.
const char *ICON_DATA_URI =
    "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E"
    "%3Crect width='24' height='24' rx='5' fill='rgb(18,21,26)'/%3E"
    "%3Crect x='5' y='11' width='14' height='9' rx='2' fill='rgb(45,212,191)'/%3E"
    "%3Cpath d='M7.5 11V8a4.5 4.5 0 0 1 9 0v3' fill='none' stroke='rgb(45,212,191)' stroke-width='2'/%3E"
    "%3C/svg%3E";

WebServer server(80);
Preferences prefs;

bool configured = false;
String savedSsid, savedPass, adminUser, adminPass;
String userNames[MAX_USERS], userPasses[MAX_USERS];
int userCount = 0;
String apPassword; // computed at boot, see deviceIdSuffix()

// Short, deterministic string derived from the chip's own unique ID (not the
// WiFi MAC, so this works before WiFi has ever been brought up). Used to
// build a per-device setup AP password so every unit in the field doesn't
// share the same "setup1234" - anyone who'd read it off one device's serial
// console can't reuse it against another.
String deviceIdSuffix() {
    char buf[7];
    snprintf(buf, sizeof(buf), "%06X", (uint32_t)(ESP.getEfuseMac() & 0xFFFFFF));
    return String(buf);
}

// ---------- password hashing ----------
// Login passwords (admin + per-user) are only ever kept as a salted SHA-256
// hash in flash, never plaintext - Preferences storage is otherwise trivial
// to pull off the device with physical access. The salt is just the chip's
// own ID (not a secret, doesn't need to be), which is enough to stop a
// precomputed rainbow-table lookup and to make the same password hash
// differently across devices. This is a proportionate fix for an ESP32 with
// no secure element; full TLS for the local config AP is a bigger job
// (self-signed cert + client trust) and is out of scope here.
String hashPassword(const String &password) {
    String salted = deviceIdSuffix() + ":" + password;
    unsigned char digest[32];
    mbedtls_sha256((const unsigned char *)salted.c_str(), salted.length(), digest, 0 /* SHA-256, not SHA-224 */);
    char hex[65];
    for (int i = 0; i < 32; i++) {
        sprintf(hex + i * 2, "%02x", digest[i]);
    }
    hex[64] = '\0';
    return String(hex);
}

bool passwordMatchesHash(const String &enteredPassword, const String &storedHash) {
    return hashPassword(enteredPassword).equalsConstantTime(storedHash);
}

struct LogEntry { String user; unsigned long atMillis; bool valid; };
LogEntry activityLog[MAX_LOG];
int logNext = 0;

// ---------- config storage (ESP32 flash, survives reboot) ----------

void loadUsers() {
    userCount = prefs.getInt("ucount", 0);
    if (userCount > MAX_USERS) userCount = MAX_USERS;
    for (int i = 0; i < userCount; i++) {
        userNames[i] = prefs.getString(("u" + String(i)).c_str(), "");
        userPasses[i] = prefs.getString(("p" + String(i)).c_str(), "");
    }
}

void persistUsers() {
    prefs.putInt("ucount", userCount);
    for (int i = 0; i < userCount; i++) {
        prefs.putString(("u" + String(i)).c_str(), userNames[i]);
        prefs.putString(("p" + String(i)).c_str(), userPasses[i]);
    }
}

void loadConfig() {
    prefs.begin("doorlock", false);
    configured = prefs.getBool("configured", false);
    savedSsid = prefs.getString("ssid", "");
    savedPass = prefs.getString("pass", "");
    adminUser = prefs.getString("auser", "");
    adminPass = prefs.getString("apass", "");
    loadUsers();
    prefs.end();
}

void saveWifiAndAdmin(const String &ssid, const String &pass, const String &au, const String &ap) {
    prefs.begin("doorlock", false);
    prefs.putBool("configured", true);
    prefs.putString("ssid", ssid);
    prefs.putString("pass", pass);
    prefs.putString("auser", au);
    prefs.putString("apass", hashPassword(ap));
    prefs.end();
}

void saveWifiOnly(const String &ssid, const String &pass) {
    prefs.begin("doorlock", false);
    prefs.putString("ssid", ssid);
    prefs.putString("pass", pass);
    prefs.end();
}

void clearAllConfig() {
    prefs.begin("doorlock", false);
    prefs.clear();
    prefs.end();
}

bool addUser(const String &u, const String &p) {
    if (u.length() == 0 || p.length() == 0) return false;
    if (userCount >= MAX_USERS) return false;
    if (u.equalsIgnoreCase(adminUser)) return false;
    for (int i = 0; i < userCount; i++) if (userNames[i].equalsIgnoreCase(u)) return false;
    userNames[userCount] = u;
    userPasses[userCount] = hashPassword(p);
    userCount++;
    prefs.begin("doorlock", false);
    persistUsers();
    prefs.end();
    return true;
}

bool deleteUser(const String &u) {
    for (int i = 0; i < userCount; i++) {
        if (userNames[i].equalsIgnoreCase(u)) {
            for (int j = i; j < userCount - 1; j++) {
                userNames[j] = userNames[j + 1];
                userPasses[j] = userPasses[j + 1];
            }
            userCount--;
            prefs.begin("doorlock", false);
            prefs.remove(("u" + String(userCount)).c_str());
            prefs.remove(("p" + String(userCount)).c_str());
            persistUsers();
            prefs.end();
            return true;
        }
    }
    return false;
}

// ---------- relay ----------

void openLock() {
    digitalWrite(RELAY_PIN, HIGH);
    Serial.println("Lock OPEN");
}

void closeLock() {
    digitalWrite(RELAY_PIN, LOW);
    Serial.println("Lock CLOSED");
}

void logEvent(const String &user) {
    activityLog[logNext] = { user, millis(), true };
    logNext = (logNext + 1) % MAX_LOG;
}

String timeAgo(unsigned long atMillis) {
    unsigned long diff = (millis() - atMillis) / 1000;
    if (diff < 60) return String(diff) + "s ago";
    if (diff < 3600) return String(diff / 60) + "m ago";
    return String(diff / 3600) + "h ago";
}

String uptimeString() {
    unsigned long diff = millis() / 1000;
    if (diff < 60) return String(diff) + "s";
    if (diff < 3600) return String(diff / 60) + "m";
    return String(diff / 3600) + "h " + String((diff % 3600) / 60) + "m";
}

// ---------- auth ----------
//
// We only ever keep password *hashes* (see hashPassword() above), so we
// can't use WebServer::authenticate(user, password) directly - it expects
// the real plaintext password to compare the wire value against. Instead we
// use its callback form: the library hands our lambda the password the
// client actually sent (extraParams[0]) still in the clear (it just came
// off the wire), we hash that ourselves and compare it to what's stored, and
// on a match hand back the same string so the library's own comparison
// against itself trivially succeeds. This mirrors how the library's own
// authenticateBasicSHA1() helper works, just with a salted SHA-256 instead.
String currentAuthedUser() {
    String matchedUser = "";
    server.authenticate([&matchedUser](HTTPAuthMethod mode, String username, String extraParams[]) -> String * {
        if (mode != BASIC_AUTH) return nullptr;
        if (adminUser.length() && username.equalsIgnoreCase(adminUser) && passwordMatchesHash(extraParams[0], adminPass)) {
            matchedUser = adminUser;
            return new String(extraParams[0]);
        }
        for (int i = 0; i < userCount; i++) {
            if (username.equalsIgnoreCase(userNames[i]) && passwordMatchesHash(extraParams[0], userPasses[i])) {
                matchedUser = userNames[i];
                return new String(extraParams[0]);
            }
        }
        return nullptr;
    });
    return matchedUser;
}

// returns "" and already sent 401 if not authorized
String requireAuth() {
    String u = currentAuthedUser();
    if (u == "") server.requestAuthentication(BASIC_AUTH, "Office Door");
    return u;
}

bool requireAdmin() {
    bool ok = adminUser.length() && server.authenticate([](HTTPAuthMethod mode, String username, String extraParams[]) -> String * {
        if (mode != BASIC_AUTH) return nullptr;
        if (username.equalsIgnoreCase(adminUser) && passwordMatchesHash(extraParams[0], adminPass)) {
            return new String(extraParams[0]);
        }
        return nullptr;
    });
    if (!ok) server.requestAuthentication(BASIC_AUTH, "Office Door Admin");
    return ok;
}

// ---------- shared page chrome ----------

String pageHead(const String &title) {
    String h;
    h += "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1'>";
    h += "<title>" + title + "</title>";
    h += "<link rel='icon' href='" + String(ICON_DATA_URI) + "'>";
    h += "<link rel='manifest' href='/manifest.json'>";
    h += "<meta name='apple-mobile-web-app-capable' content='yes'>";
    h += "<meta name='apple-mobile-web-app-title' content='Door'>";
    h += "<link rel='apple-touch-icon' href='" + String(ICON_DATA_URI) + "'>";
    h += "<meta name='theme-color' content='#12151a'>";
    h += "<style>"
         "*{box-sizing:border-box}"
         "body{font-family:-apple-system,Segoe UI,Arial,sans-serif;background:#12151a;color:#e8eef0;"
         "margin:0;padding:24px 20px 40px;max-width:460px;margin-left:auto;margin-right:auto}"
         "h1{font-size:20px;margin:0 0 4px;letter-spacing:.2px}"
         "p.sub{color:#8b96a1;margin:0 0 24px;font-size:14px}"
         "a{color:#2dd4bf}"
         ".unlockBtn{display:block;width:100%;padding:28px;margin-top:8px;font-size:22px;font-weight:600;"
         "border:none;border-radius:16px;background:#2dd4bf;color:#0a1210;cursor:pointer}"
         ".unlockBtn:active{background:#22a89a}"
         "input{width:100%;padding:11px;margin-top:6px;border-radius:8px;border:1px solid #2a2f36;"
         "background:#1a1e24;color:#e8eef0;font-size:15px}"
         "label{display:block;margin-top:14px;font-size:13px;color:#a7b0b8}"
         "button.action{margin-top:16px;width:100%;padding:12px;border:none;border-radius:8px;"
         "background:#2dd4bf;color:#0a1210;font-weight:600;font-size:15px;cursor:pointer}"
         "button.danger{background:#e05252;color:#fff}"
         "button.small{width:auto;padding:8px 14px;font-size:13px;margin-top:0}"
         ".card{background:#181c22;border:1px solid #232830;border-radius:12px;padding:16px;margin-top:18px}"
         ".row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;"
         "border-bottom:1px solid #232830;font-size:14px}"
         ".row:last-child{border-bottom:none}"
         ".muted{color:#8b96a1;font-size:13px}"
         ".statusDot{display:inline-block;width:8px;height:8px;border-radius:50%;background:#2dd4bf;margin-right:6px}"
         "form.inline{display:flex;gap:8px;margin-top:4px}"
         "form.inline button{margin-top:0}"
         "</style></head><body>";
    return h;
}

const char *PAGE_TAIL = "</body></html>";

// ---------- normal-mode pages ----------

void handleMainRoot() {
    String user = requireAuth();
    if (user == "") return;

    String h = pageHead("Office Door");
    h += "<h1>Office Door</h1>";
    h += "<p class='sub'>Signed in as <b>" + user + "</b></p>";
    h += "<form action='/unlock' method='POST'>";
    h += "<button class='unlockBtn' type='submit'>Unlock</button>";
    h += "</form>";

    h += "<div class='card'><div class='muted' style='margin-bottom:6px'>Recent activity</div>";
    bool any = false;
    for (int i = 0; i < MAX_LOG; i++) {
        int idx = (logNext - 1 - i + MAX_LOG) % MAX_LOG;
        if (!activityLog[idx].valid) continue;
        any = true;
        h += "<div class='row'><span>" + activityLog[idx].user + "</span>"
             "<span class='muted'>" + timeAgo(activityLog[idx].atMillis) + "</span></div>";
    }
    if (!any) h += "<div class='muted'>No activity yet.</div>";
    h += "</div>";

    if (user.equalsIgnoreCase(adminUser)) {
        h += "<p style='margin-top:18px'><a href='/admin'>Manage users &amp; device &rarr;</a></p>";
    }
    h += PAGE_TAIL;
    server.send(200, "text/html", h);
}

void handleUnlock() {
    String user = requireAuth();
    if (user == "") return;
    logEvent(user);
    openLock();

    String h = pageHead("Unlocking...");
    h += "<h1>Unlocked</h1><p class='sub'>Relocking in a moment. This page will return automatically.</p>";
    h += "<script>setTimeout(function(){location.href='/'},1300)</script>";
    h += PAGE_TAIL;
    server.send(200, "text/html", h);

    delay(UNLOCK_PULSE_MS);
    closeLock();
}

void handleAdmin() {
    if (!requireAdmin()) return;

    String h = pageHead("Manage - Office Door");
    h += "<h1>Manage</h1><p class='sub'><a href='/'>&larr; Back to unlock</a></p>";

    h += "<div class='card'><div class='muted'>Device</div>";
    h += "<div class='row'><span>IP address</span><span>" + WiFi.localIP().toString() + "</span></div>";
    h += "<div class='row'><span>Network</span><span>" + WiFi.SSID() + "</span></div>";
    h += "<div class='row'><span>Uptime</span><span>" + uptimeString() + "</span></div>";
    h += "</div>";

    h += "<div class='card'><div class='muted' style='margin-bottom:6px'>Users who can unlock</div>";
    h += "<div class='row'><span>" + adminUser + "</span><span class='muted'>admin</span></div>";
    for (int i = 0; i < userCount; i++) {
        h += "<div class='row'><span>" + userNames[i] + "</span>"
             "<form class='inline' action='/admin/deluser' method='POST'>"
             "<input type='hidden' name='user' value='" + userNames[i] + "'>"
             "<button class='action danger small' type='submit'>Remove</button></form></div>";
    }
    h += "</div>";

    h += "<div class='card'><div class='muted' style='margin-bottom:6px'>Add a person</div>";
    h += "<form action='/admin/adduser' method='POST'>";
    h += "<label>Name</label><input name='user' required>";
    h += "<label>Password</label><input name='pass' required>";
    h += "<button class='action' type='submit'>Add</button></form></div>";

    h += "<div class='card'><div class='muted' style='margin-bottom:6px'>WiFi</div>";
    h += "<p class='muted' style='margin-top:0'>Reset if the office WiFi name or password changes.</p>";
    h += "<form action='/admin/resetwifi' method='POST' onsubmit=\"return confirm('Reset WiFi settings? The device will restart its own setup network.')\">";
    h += "<button class='action danger' type='submit'>Reset WiFi</button></form></div>";

    h += PAGE_TAIL;
    server.send(200, "text/html", h);
}

void handleAdminAddUser() {
    if (!requireAdmin()) return;
    String u = server.arg("user");
    String p = server.arg("pass");
    addUser(u, p);
    server.sendHeader("Location", "/admin");
    server.send(303);
}

void handleAdminDelUser() {
    if (!requireAdmin()) return;
    deleteUser(server.arg("user"));
    server.sendHeader("Location", "/admin");
    server.send(303);
}

void handleAdminResetWifi() {
    if (!requireAdmin()) return;
    prefs.begin("doorlock", false);
    prefs.remove("ssid");
    prefs.remove("pass");
    prefs.end();
    server.send(200, "text/html", pageHead("Restarting") + "<h1>Restarting...</h1><p class='sub'>Reconnect to the OfficeDoor-Setup WiFi network to reconfigure.</p>" + PAGE_TAIL);
    delay(800);
    ESP.restart();
}

void handleManifest() {
    String m = "{\"name\":\"Office Door\",\"short_name\":\"Door\",\"start_url\":\"/\",\"display\":\"standalone\","
               "\"background_color\":\"#12151a\",\"theme_color\":\"#12151a\",\"icons\":["
               "{\"src\":\"" + String(ICON_DATA_URI) + "\",\"sizes\":\"192x192\",\"type\":\"image/svg+xml\"}]}";
    server.send(200, "application/manifest+json", m);
}

// ---------- setup-mode pages (device is its own AP, no auth needed yet) ----------

void handleSetupRoot() {
    String h = pageHead("Office Door Setup");
    h += "<h1>Office Door Setup</h1>";
    h += "<p class='sub'>Connect the lock to your office WiFi and create the admin login.</p>";
    h += "<form action='/save' method='POST'>";
    h += "<label>WiFi name</label><input name='ssid' required>";
    h += "<label>WiFi password</label><input id='pass' name='pass' type='password'>";
    h += "<label>Admin username</label><input name='auser' required>";
    h += "<label>Admin password</label><input id='apass' name='apass' type='password' required>";
    h += "<label style='display:flex;align-items:center;gap:8px;margin-top:14px'>"
         "<input type='checkbox' style='width:auto' onchange=\"var t=this.checked?'text':'password';"
         "document.getElementById('pass').type=t;document.getElementById('apass').type=t\">"
         "Show passwords</label>";
    h += "<button class='action' type='submit'>Save &amp; Connect</button>";
    h += "</form>";
    h += "<p class='muted' style='margin-top:18px'>Note: the WiFi must be 2.4GHz &mdash; this device cannot join 5GHz networks.</p>";
    h += PAGE_TAIL;
    server.send(200, "text/html", h);
}

void handleSetupSave() {
    String ssid = server.arg("ssid");
    String pass = server.arg("pass");
    String au = server.arg("auser");
    String ap = server.arg("apass");
    if (ssid.length() == 0 || au.length() == 0 || ap.length() == 0) {
        server.send(400, "text/plain", "WiFi name, admin username and admin password are required.");
        return;
    }
    saveWifiAndAdmin(ssid, pass, au, ap);
    server.send(200, "text/html", pageHead("Saved") + "<h1>Saved</h1><p class='sub'>Restarting and joining " + ssid + "...</p>" + PAGE_TAIL);
    delay(800);
    ESP.restart();
}

// ---------- serial fallback (debug / recovery without the app) ----------

void handleSerialCommands() {
    if (!Serial.available()) return;
    String line = Serial.readStringUntil('\n');
    line.trim();
    if (line.length() == 0) return;

    if (line == "status") {
        Serial.print("configured="); Serial.print(configured);
        Serial.print(" wifiConnected="); Serial.print(WiFi.status() == WL_CONNECTED);
        Serial.print(" ip="); Serial.print(WiFi.localIP());
        Serial.print(" rssi="); Serial.print(WiFi.RSSI());
        Serial.print(" heap="); Serial.print(ESP.getFreeHeap());
        Serial.print(" users="); Serial.println(userCount);
        return;
    }
    if (line == "appass") {
        Serial.println("Setup AP password: " + apPassword);
        return;
    }
    if (line == "listusers") {
        Serial.println(adminUser + " (admin)");
        for (int i = 0; i < userCount; i++) Serial.println(userNames[i]);
        return;
    }
    if (line.startsWith("adduser ")) {
        String rest = line.substring(8);
        int sep = rest.indexOf('|');
        if (sep < 0) { Serial.println("usage: adduser name|password"); return; }
        bool ok = addUser(rest.substring(0, sep), rest.substring(sep + 1));
        Serial.println(ok ? "added" : "failed (duplicate, full, or empty)");
        return;
    }
    if (line.startsWith("deluser ")) {
        Serial.println(deleteUser(line.substring(8)) ? "removed" : "not found");
        return;
    }
    if (line == "resetwifi") {
        prefs.begin("doorlock", false);
        prefs.remove("ssid");
        prefs.remove("pass");
        prefs.end();
        Serial.println("WiFi cleared, restarting...");
        delay(300);
        ESP.restart();
        return;
    }
    if (line == "factoryreset") {
        clearAllConfig();
        Serial.println("All config cleared, restarting...");
        delay(300);
        ESP.restart();
        return;
    }
    Serial.println("commands: status | appass | listusers | adduser name|pass | deluser name | resetwifi | factoryreset");
}

// ---------- boot ----------

void startSetupMode() {
    WiFi.mode(WIFI_AP);
    WiFi.softAP(AP_SSID, apPassword.c_str());
    server.on("/", handleSetupRoot);
    server.on("/save", HTTP_POST, handleSetupSave);
    server.on("/manifest.json", handleManifest);
    server.begin();
    Serial.println("Setup mode. Join WiFi '" AP_SSID "' (password " + apPassword + "), then visit http://192.168.4.1/");
}

const int WIFI_CONNECT_ATTEMPTS = 3;

void startNormalMode() {
    WiFi.mode(WIFI_STA);

    for (int attempt = 1; attempt <= WIFI_CONNECT_ATTEMPTS; attempt++) {
        WiFi.disconnect(true);
        delay(200);
        WiFi.begin(savedSsid.c_str(), savedPass.c_str());
        Serial.print("Connecting to "); Serial.print(savedSsid);
        Serial.print(" (attempt "); Serial.print(attempt); Serial.print("/"); Serial.print(WIFI_CONNECT_ATTEMPTS); Serial.print(")");
        unsigned long start = millis();
        while (WiFi.status() != WL_CONNECTED && millis() - start < WIFI_CONNECT_TIMEOUT_MS) {
            delay(300);
            Serial.print(".");
        }
        Serial.println();
        if (WiFi.status() == WL_CONNECTED) break;
    }

    if (WiFi.status() != WL_CONNECTED) {
        Serial.println("Could not join saved WiFi after several tries. Falling back to setup mode.");
        startSetupMode();
        return;
    }

    Serial.print("Connected. IP: "); Serial.println(WiFi.localIP());
    if (MDNS.begin(MDNS_NAME)) {
        Serial.println("Reachable at http://" MDNS_NAME ".local/");
    } else {
        Serial.println("mDNS failed to start - use the IP address above instead.");
    }

    server.on("/", handleMainRoot);
    server.on("/unlock", HTTP_POST, handleUnlock);
    server.on("/admin", handleAdmin);
    server.on("/admin/adduser", HTTP_POST, handleAdminAddUser);
    server.on("/admin/deluser", HTTP_POST, handleAdminDelUser);
    server.on("/admin/resetwifi", HTTP_POST, handleAdminResetWifi);
    server.on("/manifest.json", handleManifest);
    server.begin();
}

void setup() {
    Serial.begin(115200);
    delay(200);
    pinMode(RELAY_PIN, OUTPUT);
    digitalWrite(RELAY_PIN, LOW); // start locked
    pinMode(BOOT_BUTTON_PIN, INPUT_PULLUP);

    apPassword = "door-" + deviceIdSuffix();

    loadConfig();

    if (digitalRead(BOOT_BUTTON_PIN) == LOW) {
        unsigned long t0 = millis();
        while (digitalRead(BOOT_BUTTON_PIN) == LOW && millis() - t0 < 3000) delay(10);
        if (millis() - t0 >= 3000) {
            clearAllConfig();
            configured = false;
            Serial.println("Factory reset (BOOT button held).");
        }
    }

    if (!configured) startSetupMode();
    else startNormalMode();
}

void loop() {
    server.handleClient();
    handleSerialCommands();
}
