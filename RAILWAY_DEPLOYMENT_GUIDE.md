# ALALAY — Railway Post-Deployment & Redeploy Guide

This guide is for **after** the app is live on Railway (see
[RAILWAY_SETUP.md](RAILWAY_SETUP.md) for the initial setup). It explains how to redeploy
when you make code or config changes, how to update environment variables, and how to
troubleshoot/debug the live app.

> **Important:** Railway deploys are **CLI / `railway up` based** in this project. Pushing
> new commits to GitHub / the `dev` branch does **not** trigger a deploy. Every change must be
> followed by `railway up`.

---

## 1. Architecture summary

| Service | Role | Deploy command |
|---|---|---|
| `web` | Vue/Inertia frontend + Laravel HTTP app (FrankenPHP) | `railway up --service web` |
| `worker` | Queue worker — sends OTP emails, SMS jobs, backups | `railway up --service worker` |
| `cron` | Scheduler — runs `schedule:run` every 60 s | `railway up --service cron` |
| `MySQL` | MySQL 9.4 DB with persistent 5 GB volume | *data service, no redeploy* |

Notes:

- **Only `web` runs PreDeploy with `migrate --force` + `db:seed --force`.** The worker and cron
  run only `php artisan config:cache` as PreDeploy. That is intentional — do not add migrations
  or seeds to worker/cron.
- The database lives on board; neither worker nor cron attaches a separate DB.

---

## 2. Redeploying after a code change

Full cycle, from edit to live:

```bash
# 1. (optional but recommended) sanity-check locally
php artisan config:cache
php artisan route:cache
php artisan migrate --force
php artisan db:seed --force

# 2. deploy
railway up --service web
railway up --service worker
railway up --service cron     # only if the change affects scheduled jobs/config
```

What actually happens on Railway:

1. `railway up` uploads the **current local directory** (the whole repo) and builds with Railpack.
2. `web` PreDeploy runs: `config:cache && event:cache && route:cache && view:cache && migrate --force && db:seed --force`.
3. The new deployment replaces the old one when it becomes `SUCCESS` (no downtime; Railway
   swaps traffic once healthy).

### Minimal deploy set by change type

| What changed | Deploy this |
|---|---|
| Frontend / controllers / routes / models / migrations / seeders | `web` (+ `worker` if it uses the new code paths) |
| Mailables, queued jobs, senders (OTP/SMS) | `worker` (and `web` so its dispatching matches) |
| Scheduler commands / backup config | `cron` |
| Env vars only | see §4 (no rebuild needed) |

> When in doubt, deploy **web + worker together** — the queue jobs (OTP mail) are dispatched by
> web and executed by the worker, so both should run the same code.

---

## 3. Watching a deploy

Track status from the CLI:

```bash
railway up --service web --yes     # --yes skips the confirm prompt
railway logs --service worker       # live logs for the current deployment
railway logs --service worker --deployment <id>   # logs of a specific deployment
```

The `--deployment <id>` id is the 8-hex slug shown after `railway up` / in the dashboard
(`Packaging` → deployment URL). A deployment must reach **`SUCCESS`** before traffic is
switched; `CRASHED` / `FAILED` deployments roll back or stop the service.

---

## 4. Changing environment variables

Variables are **not** applied by `railway up`. Set them directly (this also works without
rebuilding):

```bash
railway variables --service web --set "KEY=value"
railway variables --service worker --set "KEY=value"
railway variables --service cron --set "KEY=value"
```

- Mirror the block in `railway/.env.production` (gitignored) so local/prod stay consistent.
- After changing a variable, **redeploy the affected service(s)** so PreDeploy re-runs
  `config:cache` with the new value:

```bash
railway up --service web
```

### Current mail/OTP configuration (as deployed)

| Variable | Value |
|---|---|
| `MAIL_MAILER` | `resend` |
| `RESEND_API_KEY` | *(Resend project key — secret)* |
| `MAIL_FROM_ADDRESS` | `onboarding@resend.dev` |
| `MAIL_TEST_RECIPIENT` | `johnpaullaureano.neust@gmail.com` |
| `SESSION_PATH` | `/` |

- `MAIL_MAILER=resend` + `RESEND_API_KEY` use the **Resend HTTPS API**. Railway blocks all
  outbound **SMTP** (ports 25/465/587) on deployed services, so Gmail/SMTP will never work live —
  don't switch back to `MAIL_MAILER=smtp`.
- `MAIL_TEST_RECIPIENT` is the **sandbox override** implemented in
  `app/Providers/AppServiceProvider.php` (a `MessageSending` listener rewrites the recipient of
  every outgoing email). It exists because Resend's free test sender can only deliver to the
  account owner's inbox. **Leave it empty in real production** so mail goes to actual recipients.
- If you verify a domain in Resend, set `MAIL_FROM_ADDRESS=noreply@<domain>` and
  `MAIL_TEST_RECIPIENT` can then be removed.

---

## 5. Common redeploy pitfalls (learned live)

| Symptom | Cause | Fix |
|---|---|---|
| Login POST returns **400 empty body** from `Pingora` | A header/cookie value contains a stray `\r` or the `XSRF-TOKEN` payload is corrupted in the request chain | Fresh session cookie jar; ensure URL-decoded token is CRLF-stripped |
| Session cookie path shows a Windows path (`C:/Program Files/Git/`) | `SESSION_PATH` was accidentally set to the local clone path | `railway variables --service web --set "SESSION_PATH=/"` then redeploy |
| OTP mail job `FAIL` in worker logs | Recipient not whitelisted by Resend test sender, or SMTP was in use | Use `resend` mailer + `MAIL_TEST_RECIPIENT` (see §4) |
| `Connection timed out` to `ssl://smtp.gmail.com:465` | Railway **blocks outbound SMTP** on real deployments (sandbox `railway run` still allows it) | Keep `MAIL_MAILER=resend` (HTTPS API) |
| Config change not reflected after deploy | PreDeploy `config:cache` cached old env | Set the variable, then `railway up` the service so it re-caches |

### Debugging a live OTP/send path

1. Watch the worker: `railway logs --service worker` — look for
   `App\Mail\SendOtpMail ... DONE` (success) vs `FAIL`/`ERROR` (with the exact exception).
2. Login flow order verified working:
   - `POST /login` → `302 /otp-challenge`
   - job queued (`jobs` table) → worker processes → Resend HTTPS send
   - `POST /otp-challenge` with the 6-digit code → `302` dashboard
3. If the queue ever backs up, `jobs` table rows accumulate; the worker drains them on next
   boot. Check `queue:work` is actually running: `railway logs --service worker`.
4. **Logging in twice in a row (`logout → login`) asks for a code that "doesn't work":**
   each `POST /login` used to expire all earlier pending OTPs (`EmailOtpService::generate()`),
   so the code in your email from the *first* login was dead by the time you entered it.
   Fixed in `d5d370a`: `verify()` now accepts *any* valid pending code; older codes stay
   usable until their 5-minute expiry, then all remaining pending codes are expired once one
   matches. Regression test: `test_multiple_pending_otps_any_valid_code_succeeds`.

### Local development vs. production mail

- `.env` (local) now uses `MAIL_MAILER=resend` + `RESEND_API_KEY` so OTP emails behave like
  live (they land in `MAIL_TEST_RECIPIENT`'s inbox). Requires `php artisan queue:work` locally
  (queue driver is `database`). For pure offline dev you can switch back to `MAIL_MAILER=log`
  — the OTP text is written to `storage/logs/laravel.log` instead of being sent.
- `.env.example` documents the Resend + `MAIL_TEST_RECIPIENT` variables; keep it in sync when
  you change mail config.

---

## 6. Turning the app off / on

There is **no pause button** on Railway. Scale replicas to **0** per service to stop compute
billing (env vars, config, and the MySQL volume all persist). MySQL is usually left at 1.

```
On  (testing):  MySQL → worker → web   (worker first so queued jobs drain)
Off (default):  replica scale to 0 on web, worker, cron (MySQL stays)
```

Pod label / scaling via dashboard: `Service → Settings → Scale → Replicas → 0`.

Do **not** use Serverless mode: the cron pings outbound every 60 s and the worker holds open
sockets, so those services would never sleep and would cold-boot with 502s.

---

## 7. Restoring / rolling back a bad deployment

- **Roll back:** in the dashboard, `Service → Deployments → <bad deploy> → ⋮ → Restore previous
  deployment`. Data (MySQL volume) is untouched.
- **After a code revert in git:** just `railway up` each affected service from the reverted
  checkout — same upload flow.
- Database migration on live DB is additive-only (PreDeploy `migrate --force`). Do not remove
  columns/tables the running app still references before redeploying web+worker together.

---

*ALALAY System — Municipality of General Mamerto Natividad, Nueva Ecija*