# FitHub — Google Play Console submission worksheet

Everything below is copy-paste-ready for whoever has access to the company's
Play Console account. Where a field needs a judgment call only Google's own
flow can make (e.g. content rating), it says so instead of guessing.

## 0. Readiness checklist (as of 2026-08-04)

| Item | Status |
|---|---|
| Signed production `.aab`, pointed at the live backend | ✅ Built |
| App icon, splash, in-app logo (new dumbbell mark) | ✅ Done, baked into the build above |
| Play Store hi-res icon (512×512) + feature graphic (1024×500) | ✅ Regenerated from the new logo |
| Store listing text (short + long description) | ✅ Written below (plain English, no hardware mentions) |
| Privacy policy page | ✅ Live at `fithub.ranksol.net/legal/privacy` |
| Data deletion page | ✅ Live at `fithub.ranksol.net/legal/data-deletion` |
| Signing keystore | ✅ Already configured (`android/key.properties`) |
| **Screenshots** | ❌ **Blocking.** The 5 ChatGPT images can't be submitted — see §3, real ones needed |
| **Reviewer test account** | ❌ Needs a dedicated login created for Google's reviewer — see §6 |
| Content rating questionnaire | ⏳ Fill in Play Console at submission time — see §4 |
| Data safety form | ⏳ Fill in Play Console using the table in §5 |

Everything on the ❌ row has to happen before you can submit. Everything ⏳
happens inside the Play Console flow itself when you get there — nothing to
prepare in advance beyond what's already written up in §4/§5.

## 1. The file to upload

`mobile/build/app/outputs/bundle/release/app-release.aab`

This is the production build — signed with the app's real upload key (not a
debug key), pointed at the live backend (`fithub.ranksol.net`), and built
with the new logo/branding (rebuilt 2026-08-04, after the branding update —
if you rebuild again later, the old file at this path gets overwritten, so
always re-check the timestamp before uploading). Upload this exact file to
Play Console's "Production" (or "Internal testing") release track.

There's also an installable `.apk` at
`mobile/build/app/outputs/flutter-apk/app-release.apk` — use this to install
the real release build on an Android phone for testing and screenshots
before submitting (an `.aab` can't be installed directly, only `.apk` can).
This `.apk` is also built with the new logo/branding.

## 2. Store listing text

**App name:** FitHub

**Short description** (max 80 characters):
> Your gym membership, classes, attendance, and progress in one app.

**Full description** (max 4000 characters):
> FitHub is the app for gym members whose gym uses the FitHub system.
>
> If your gym uses FitHub, just log in with the account your gym gave you.
> Then you can:
>
> • Check your membership status and payment history
> • Book classes and see your upcoming bookings
> • See your attendance and daily streaks
> • Track your weight, body measurements, and BMI over time
> • Get reminders for class times and membership renewal
>
> FitHub is simple and easy to use, made for real gym members — not a
> general fitness tracker. You need an account from a gym that uses FitHub
> to sign in.

*(Written in plain, simple English on request — no mention of hardware
features, since those aren't relevant to every gym on the platform.)*

**Category:** Health & Fitness

**Contact details:** support email `ranksolcompany@gmail.com`, phone
`+92 315 6340085` — both already live on the marketing site footer.

**Privacy policy URL:** `https://fithub.ranksol.net/legal/privacy` (confirmed
live).

## 3. Graphic assets

Both regenerated from the new logo (2026-08-04), already sized to Google's
exact spec:

| Asset | Spec | File |
|---|---|---|
| Hi-res icon | 512×512, no alpha | `mobile/store-assets/play-store-icon-512.png` |
| Feature graphic | 1024×500 | `mobile/store-assets/play-store-feature-graphic.png` |

**⚠️ Screenshots: the ChatGPT-generated images can't be used.** Google Play's
policy on store listing content requires screenshots to show the actual app
running — not mockups, not AI-generated composites, not renders. Submitting
AI-generated "screenshots" risks the listing being rejected at review, or a
policy strike against the account (which has 59 other apps riding on it) if
it's caught later. This isn't a style preference, it's a hard platform rule.

The good news: real screenshots take 5 minutes and look better anyway (real
UI, your real theme, no AI artifacts). To capture them:

1. Install `mobile/build/app/outputs/flutter-apk/app-release.apk` on a real
   Android phone (copy it over and tap to install — you may need to allow
   "install from unknown sources" once). This build already has the new logo
   and splash screen.
2. Log in and take screenshots of 4–6 screens that show what the app
   actually does — good picks: the new splash screen, Home, Classes,
   Attendance, Progress/BMI.
3. Use the phone's normal screenshot gesture (power + volume-down on most
   Android phones). Google requires **at least 2**, recommends 4–8, portrait,
   JPEG or 24-bit PNG.

If it's easier, plug the phone into this PC via USB with USB debugging on
and ask — screenshots can be pulled straight off the device with `adb`
instead of doing it by hand.

## 4. Content rating questionnaire

This is an interactive form inside Play Console (IARC questionnaire) — no
one can pre-fill it, but here's what to expect: FitHub has no violence,
gambling, user-generated content sharing, or mature themes, so answering
"No" throughout should land it at the lowest rating tier (Everyone). Answer
honestly based on the actual app, not this note — this is just so the
questionnaire doesn't feel like a surprise.

## 5. Data safety section

This is the part reviewers (and users) actually read closely. Based on what
the app's code actually collects and sends — not a guess:

| Data type | Collected? | Shared with 3rd party? | Purpose |
|---|---|---|---|
| Name, email, phone number | Yes | No | Account functionality |
| Payment history / status | Yes (view-only — no in-app purchases) | No | App functionality |
| Health & fitness (attendance, measurements, BMI) | Yes | No | App functionality |
| Photos (profile picture) | Yes, if user uploads one | No | App functionality |
| Device/app identifiers (push notification token) | Yes | Yes — Firebase Cloud Messaging (Google), solely to deliver notifications | App functionality |
| Location | No | — | — |
| Biometric data | No — fingerprint scanning happens on separate gym hardware (a fingerprint sensor device), never through the phone's camera or sensors | — | — |

Also confirm in the flow:
- **Data encrypted in transit:** Yes (app enforces HTTPS to the backend)
- **Users can request data deletion:** Yes — link to
  `https://fithub.ranksol.net/legal/data-deletion`
- **Data collection is required for app functionality**, not optional —
  select "no" where it asks if collection can be avoided (an account is
  required to use the app)

## 6. Other declarations

- **Ads:** No — the app shows no ads and includes no ad SDK.
- **In-app purchases:** No — membership payment happens outside the app
  (at the gym, or via the web dashboard); the app only *displays* payment
  history, so Google Play Billing does not apply here.
- **Government app:** No.
- **App access — reviewer login:** Play reviewers need to actually sign in
  to review the app. Create one dedicated test account (don't reuse a real
  member's or gym's login) and enter its email/password in Play Console's
  "App access" section so reviewers aren't stuck at the login screen.

## 7. After it's live

Once this first version is approved, every future update just means:
bump `version:` in `mobile/pubspec.yaml` (e.g. `1.0.1+2`), rebuild the AAB
with the same command, and upload the new file to the same listing — no
new keystore, no new listing, none of today's setup repeats.
