# Deploying to cPanel

Checklist for getting a new push live on `fithub.ranksol.net`. Run everything
below from the `backend/` directory on the server (cPanel Terminal or SSH).

## Every deploy

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link          # safe to re-run if it already exists
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- `migrate --force` — check `database/migrations/` for anything new before
  you deploy; nothing in a new migration is live until this runs.
- `storage:link` — creates `public/storage` → `storage/app/public`. Needed
  for profile photos and any other public-disk upload. Gitignored, so a
  code-only deploy never creates it on its own.
- Frontend assets (`public/build/`, Vite output) are compiled, not
  interpreted at runtime. Either run `npm ci && npm run build` on the
  server (if Node is available there), or build locally and make sure
  `public/build/` is part of whatever gets pushed — check it isn't silently
  gitignored out of the deploy.
- Static branding assets (`public/images/branding/*`, `public/favicon.ico`)
  are plain files, not compiled — they just need to be included in the push
  like any other file under `public/`.
- No queue worker needed — nothing in this codebase implements
  `ShouldQueue`.

## One-time server setup

Verify these are in place; they don't travel via git or a normal deploy.

**1. Cron — the task scheduler.** One cPanel cron job:

```
* * * * * cd /home/USER/path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

Drives every `Schedule::command(...)` entry in `routes/console.php`: class /
progress / renewal / trial-ending reminders, and the nightly Spatie backup
(`backup:run` 01:30, `backup:clean` 02:15, `backup:monitor` 02:30). Nothing
else needs its own cron entry.

**2. Firebase service account.** Gitignored, never deployed automatically.
Upload manually via File Manager/SFTP to:

```
storage/app/firebase/service-account.json
```

**3. Production `.env`.** Copy `.env.production.example` → `.env` and fill
in every `CHANGE ME`. The values that actually gate functionality:

| Value | Why |
|---|---|
| `APP_KEY` | generate fresh on the server: `php artisan key:generate --show` — don't reuse the local dev key |
| `APP_URL` | `https://fithub.ranksol.net` |
| `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | cPanel MySQL credentials |
| `STRIPE_KEY` / `STRIPE_SECRET` / `STRIPE_WEBHOOK_SECRET` | billing is dead without these; `AppServiceProvider` logs a critical error every request in prod if the webhook secret is missing |
| `MAIL_MAILER` / `MAIL_HOST` / `MAIL_USERNAME` / `MAIL_PASSWORD` | use a transactional provider (Postmark/SES/Mailgun/Resend) with SPF/DKIM/DMARC on the sending domain — **not** personal Gmail SMTP (rate-limited, gets flagged, hurts deliverability) |
| `TWILIO_SID` / `TWILIO_AUTH_TOKEN` / `TWILIO_FROM_NUMBER` | SMS reminders |
| `BACKUP_NOTIFICATION_EMAIL` / `BACKUP_ARCHIVE_PASSWORD` | where backup failure alerts go |
| `SENTRY_LARAVEL_DSN` | optional but recommended — `APP_DEBUG=false` in prod means errors only show up in log files without this |

**4. Stripe webhook.** Register an endpoint in the Stripe dashboard at
`https://fithub.ranksol.net/stripe/webhook` and copy its signing secret into
`STRIPE_WEBHOOK_SECRET` above. The `.env` value and the dashboard
registration are two separate steps — both are required.

**5. Backup disk space.** `config/backup.php` writes to `local` only, no S3.
Nightly backups accumulate on the server's own disk (pruned only by
`backup:clean`'s retention policy) — keep an eye on cPanel disk quota over
time.

## Post-deploy sanity check

- Visit `https://fithub.ranksol.net` — favicon should be the current logo
- Upload a profile photo — confirms `storage:link` is working
- Trigger a Stripe test event — confirms the webhook is wired end to end
