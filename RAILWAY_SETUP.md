# ALALAY — Railway Hosting: Step-by-Step Setup

Everything in the **codebase** is done and committed (`4bd9e83`). This guide covers the
remaining **manual steps** in your local terminal + the Railway dashboard (~15–20 min).

---

## 0. Local cleanup + push (terminal)

**0.1 Commit pending changes (slimmed `railway.json` + copy-paste docs)**

`railway/.env.production` is gitignored — no secrets go in this commit:

```bash
git add railway.json RAILWAY_SETUP.md .ai/context/ && git status
git commit -m "deploy: slim railway.json to build-only; document dashboard copy-paste commands"
```

**0.2 Remove the GitHub token from the remote URL** ⚠️

The current remote embeds a token and would leak it on every push:

```bash
git remote set-url origin https://github.com/jnpl-dev/ALALAY.git
```

Then authenticate cleanly (pick one):

```bash
# Option A — GitHub CLI (recommended)
gh auth login

# Option B — SSH
#   ssh-keygen -t ed25519 -C "your@email.com"
#   Add ~/.ssh/id_ed25519.pub to GitHub (Settings → SSH keys)
git remote set-url origin git@github.com:jnpl-dev/ALALAY.git
```

**0.3 Push**

```bash
git push -u origin dev
```

Confirm the token string no longer appears in your git config.

---

## 1. Create the Railway project (dashboard)

1. Open https://railway.app → **New Project** → **Empty Project**.
2. Click **+ New** → **Database** → **MySQL**. Wait for it to provision.
3. Add the app service **three times** — Railway creates exactly **one** service per click,
   and config-as-code covers a single service per repo. Each time:
   **+ New → GitHub Repo** → select `jnpl-dev/ALALAY` → branch `dev`.
   Repeat until you have 3 repo-based services. (All three use root directory `/` — the whole
   app lives at the repo root, so no "Empty Service + Connect Repo + Root Directory" steps, which
   you'd only need for a monorepo.)

You now have 4 items: 1 MySQL database + 3 app services. Rename them:
- the three repo-based services → `web`, `worker`, `cron`
- the database → `mysql`

---

## 2. Configure the `web` service

**Settings → Source:** repo `ALALAY`, branch `dev` (auto-detected).

**Variables (Settings → Variables):** copy the block from
[`railway/.env.production`](railway/.env.production) → click **RAW Editor** → paste → Save.
Then set the two values that depend on your services:

- `APP_URL` → your generated domain (set in step 5)
- `${{mysql.*}}` references → Railway auto-fills them if the DB service is named `mysql`

> `railway/.env.production` is gitignored (contains real secrets, not committed). It carries the
> production overrides (`APP_ENV=production`, `SMS_DRIVER=philsms`, `FILESYSTEM_DISK=supabase`,
> `RAILPACK_SKIP_MIGRATIONS=true`, fresh `APP_KEY`/`BACKUP_ENCRYPT_PASS`/`APP_MAINTENANCE_SECRET`).

**Settings → Deploy** — `railway.json` only pins the Build Command; everything below is
editable in the dashboard. Copy-paste these exactly:

**Build Command:**
```bash
chmod +x ./railway/init.sh && sh ./railway/init.sh
```

**PreDeploy Command** (runs on every deploy, in order: caches → migrate → seed):
```bash
php artisan config:cache && php artisan event:cache && php artisan route:cache && php artisan view:cache && php artisan migrate --force && php artisan db:seed --force
```

**Start Command:** *(leave empty)* — Railpack auto-detects Laravel and boots FrankenPHP.
**Healthcheck Path:** `/up`

> `${{mysql.VARIABLE}}` references use your database service name. If you renamed it,
> use that name in the reference.

---

## 3. Configure `worker` service

Same **Source** + same **Variables** (paste the same
[`railway/.env.production`](railway/.env.production) block into its RAW Editor).

Deploy settings — copy-paste (overrides web's; **no migrate/seed here**):

**PreDeploy Command:**
```bash
php artisan config:cache
```

**Start Command:**
```bash
sh ./railway/worker.sh
```

**Healthcheck:** leave off / not applicable

---

## 4. Configure `cron` service

Same **Source** + same **Variables** (paste the same
[`railway/.env.production`](railway/.env.production) block into its RAW Editor).

Deploy settings — copy-paste:

**PreDeploy Command:**
```bash
php artisan config:cache
```

**Start Command:**
```bash
sh ./railway/cron.sh
```

**Healthcheck:** leave off

---

## 5. Attach custom domain + HTTPS

1. In `web` service **Settings → Networking → Generate Domain** (get the `*.up.railway.app` URL first).
2. Optionally attach your `.gov.ph` domain (Settings → Networking → Custom Domain, then set the DNS CNAME at your DNS provider).
3. Set `APP_URL` to the final URL. Railway enforces HTTPS automatically.

---

## 6. Deploy + verify

1. **Deploy** the `web` service. PreDeploy runs caches → migrations → seeds on first boot.
2. **Deploy** `worker` and `cron` services.

Verification checklist:

- [ ] `GET /up` → `200` (healthcheck green in web service)
- [ ] Login with the seeded admin → OTP flow → SMS delivered
- [ ] Document upload → signed URL → view/download works (Supabase)
- [ ] `php artisan queue:work` processing (check worker logs for `SendSmsJob`, `BackupDatabaseJob`)
- [ ] Scheduler: `backup:run` writes an encrypted dump to `alalay-backups`
- [ ] All role panels reachable per role matrix
- [ ] Admin force-logout / session revocation works

**First-deploy watch-outs:**

- If an Excel/job fails, add PHP extensions: set `RAILPACK_PHP_EXTENSIONS=gd,zip,intl,iconv`
  (Railpack PHP images include most; this is the escape hatch).
- `php.ini` caps uploads at **20 MB** — if documents/vouchers exceed this, raise
  `upload_max_filesize`/`post_max_size` in the repo `php.ini` and redeploy.

---

## 7. Post go-live (security)

- **Rotate the seeded admin password immediately** — seeded accounts use `12345`.
  Login as admin → change password, or reset via tinker (`php artisan tinker`).
- Replace the seeded demo accounts (`internalaudit@example.com`, `budgetofficer@example.com`)
  with real staff accounts; deactivate the placeholders.
- Rotate `BACKUP_ENCRYPT_PASS` to a production-grade passphrase.

---

## 8. Trial hosting — online testing only (SMS on, scale-to-0)

Use this when the goal is **letting testers use the app online** on Railway's **Free Trial**
($5 credit, 30 days — whichever ends first; no credit card).

### Trial prerequisites

- ⚠️ **Start the trial with your GitHub account connected.** This unlocks the **Full Trial**.
  A *Limited Trial* blocks outbound network access — which breaks Supabase storage, PhilSMS,
  mail, and backups. The app is on GitHub, so this is just connecting it at sign-up.
- Confirm in the dashboard that the trial shows **Full Trial** / unrestricted network access.
- Set a **hard usage limit** (`Workspace → Usage`) so the $5 credit is never exceeded.

### Services to deploy

All 4 from this guide: **web, worker, cron, MySQL**.

- **web** + **worker** + **MySQL** are required for any online test with live SMS.
- **cron** runs only `backup:run` (daily) + `backup:verify` (weekly) — optional, but cheap
  and it validates the scheduler. **Keep it if you want real backups of the test data.**

### Why SMS needs the worker (important)

SMS is sent via a **queued job** (`SendSmsJob`, database queue). The **worker service must be
running** for messages to actually go out — otherwise submissions just accumulate in the `jobs`
table. Scale order when turning testing on:

```
MySQL → worker → web
```

MySQL first (it already has data), then the worker (starts draining queued SMS jobs), then web.

### On/off routine (scale-to-0)

**Testing OFF (default):** scale replicas to **0** on **all services** (`Service → Settings → Scale`,
set replicas to 0). Compute billing stops instantly; env vars, service config, and the MySQL
volume all persist. The URL stops responding until you turn services back on.

**Testing ON:** scale the services you need back to **1**:
- web, worker, MySQL (+ cron if you kept it). No redeploy, no rebuild, data intact — just share
  the URL with testers.

> There is no "pause" button on Railway. Scaling replicas to 0 is the recommended way to stop a
> service without losing its configuration. `Remove Deployment` (Deployments tab → ⋮) also works
> but requires a manual redeploy to come back.
>
> Do **not** use **Serverless mode** here — the cron service pings outbound every 60 s and the
> worker holds sockets, so services would never sleep, and the first request after a wake
> returns a cold-boot 502.

### Cost while testing

| Set | Approx. cost/hr | $5 trial budget |
|---|---|---|
| web + worker + MySQL | **$0.06/hr** | ~80 hours of live testing |
| + cron | $0.07–0.08/hr | ~65 hours |

MySQL volume storage keeps billing while "off" but negligibly (~$0.15/GB-month). Disciplined
scale-to-0 is what makes the trial last the full 30 days.

### Trial → production

When the trial ends you drop to the **Free** plan ($1 credit/month, 3 services, auto-stop on
budget) or **Hobby** ($5/month) — or migrate to a VPS. No code changes are required either way;
the deploy config is identical.

---

## Troubleshooting quick reference

| Symptom | Likely cause | Fix |
|---|---|---|
| Crash loop on start | Railpack re-runs `migrate --seed` → duplicate users | Set `RAILPACK_SKIP_MIGRATIONS=true` on **all** services |
| 500 after deploy, `?error` shows cached old env | caches baked with wrong env | Recheck `APP_KEY`/`DB_*`; redeploy so preDeploy re-caches |
| Seeded data missing | `db:seed` not in web PreDeploy | Confirm PreDeploy has `db:seed --force` |
| `route:cache` failure | a Closure route | (not expected — verified locally) |
| Excel download/job error | missing PHP ext | `RAILPACK_PHP_EXTENSIONS=gd,zip,intl,iconv` |
| Upload rejected >20 MB | `php.ini` cap | Raise limits in repo `php.ini`, redeploy |

---

*ALALAY System — Municipality of General Mamerto Natividad, Nueva Ecija*
