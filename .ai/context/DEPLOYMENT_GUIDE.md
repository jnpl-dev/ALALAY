# ALALAY: Production Deployment Guide
**Municipality of General Mamerto Natividad, Nueva Ecija**
**VPS — Ubuntu 22.04 LTS — Nginx + PHP-FPM + MySQL 8.x**

---

## Overview

This document covers the full production deployment architecture for ALALAY. The system is deployed on a **single VPS (Virtual Private Server)** running Ubuntu 22.04 LTS, serving both the Vue 3 frontend and the Laravel API backend under a `.gov.ph` domain with HTTPS enforced.

XAMPP is used **for local development only** and is never used in production.

---

## Production Architecture

```
VPS (Ubuntu 22.04 LTS)
├── Nginx                          # Web server + reverse proxy
├── PHP 8.2-FPM                    # PHP process manager for Laravel
├── MySQL 8.x                      # Production database
├── Supervisor                     # Persistent queue:work daemon
├── Certbot (Let's Encrypt)        # Free SSL/TLS certificates
├── UFW Firewall                   # Open ports: 22 (SSH), 80 (HTTP), 443 (HTTPS) only
└── Cron                           # Laravel scheduler (runs every minute)
```

---

## Domain Setup

### Recommended Domain Structure

```
alalay.gmn.gov.ph          →  Vue 3 frontend (Nginx static file serving)
api.alalay.gmn.gov.ph      →  Laravel REST API (Nginx + PHP-FPM)
```

Both subdomains served from the same VPS via Nginx virtual hosts. SSL via a wildcard certificate (`*.gmn.gov.ph`) or two separate Let's Encrypt certificates.

### On the `.gov.ph` Domain

ALALAY is a government web application for the Municipality of General Mamerto Natividad. The municipality is entitled to a `.gov.ph` domain under DICT policy.

- **Requesting authority:** DICT (Department of Information and Communications Technology)
- **Who requests:** The municipality's IT officer or the Office of the Mayor
- **Reference:** DICT MC 005 s. 2020 — requires all government web systems to use official government domains
- **Action required:** Submit domain request to DICT before production go-live *(organizational action — outside code scope)*

---

## Recommended VPS Providers

| Provider | Notes |
|---|---|
| **Vultr** | Has a Manila/Singapore region — lowest latency for PH users; $6–12/month |
| **DigitalOcean** | Very Laravel-friendly documentation; $6–12/month Droplet |
| **Contabo** | Cheapest specs-per-dollar; European-based but reliable |
| **AWS Lightsail** | Government-credible; pay-as-you-go; easy SSL setup |

**Minimum recommended VPS specs:**

| Resource | Minimum | Recommended |
|---|---|---|
| CPU | 1 vCPU | 2 vCPU |
| RAM | 1 GB | 2 GB |
| Storage | 25 GB SSD | 50 GB SSD |
| OS | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |

---

## Why Sanctum Still Works in Production

Sanctum SPA cookie mode requires the frontend and backend to share a **root domain**. Since both are under `.gmn.gov.ph`:

- `alalay.gmn.gov.ph` (frontend)
- `api.alalay.gmn.gov.ph` (backend)

Setting `SESSION_DOMAIN=.gmn.gov.ph` (leading dot) makes the session cookie valid across all subdomains. This is a one-line `.env` change — fully supported by Laravel Sanctum.

**JWT would only be necessary if:**
- Frontend and backend were on completely different domains (different providers, no shared root), or
- A mobile app (React Native / Flutter) needed to authenticate — mobile apps cannot use cookies.

Neither applies to ALALAY. **Sanctum + Fortify remains the correct and more secure choice for production.**

---

## Server Software Installation

### 1. System Update

```bash
sudo apt update && sudo apt upgrade -y
```

### 2. Nginx

```bash
sudo apt install nginx -y
sudo systemctl enable nginx
sudo systemctl start nginx
```

### 3. PHP 8.2 + Required Extensions

```bash
sudo apt install software-properties-common -y
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml \
  php8.2-bcmath php8.2-curl php8.2-zip php8.2-gd php8.2-intl \
  php8.2-tokenizer php8.2-ctype php8.2-json php8.2-fileinfo -y
```

### 4. MySQL 8.x

```bash
sudo apt install mysql-server -y
sudo systemctl enable mysql
sudo mysql_secure_installation
```

Create ALALAY database and user:

```sql
CREATE DATABASE alalay CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'alalay_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON alalay.* TO 'alalay_user'@'localhost';

-- Restrict audit_logs and reviews from UPDATE/DELETE (NPC MSC-3)
REVOKE UPDATE, DELETE ON alalay.audit_logs FROM 'alalay_user'@'localhost';
REVOKE UPDATE, DELETE ON alalay.reviews FROM 'alalay_user'@'localhost';

FLUSH PRIVILEGES;
```

> **NPC Compliance Note (MSC-3):** Revoking UPDATE and DELETE on `audit_logs` and `reviews` at the database user level enforces append-only behavior regardless of application-layer bugs or misuse.

### 5. Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

### 6. Node.js 20 LTS

```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install nodejs -y
```

### 7. Supervisor (Queue Worker Daemon)

```bash
sudo apt install supervisor -y
sudo systemctl enable supervisor
sudo systemctl start supervisor
```

### 8. Certbot (Let's Encrypt SSL)

```bash
sudo apt install certbot python3-certbot-nginx -y
```

### 9. UFW Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## Laravel Backend Deployment

### 1. Clone Repository

```bash
cd /var/www
sudo git clone https://github.com/your-org/alalay.git alalay
sudo chown -R www-data:www-data /var/www/alalay
sudo chmod -R 755 /var/www/alalay
```

### 2. Install PHP Dependencies

```bash
cd /var/www/alalay
composer install --optimize-autoloader --no-dev
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
nano .env
```

Production `.env` values:

```env
# Application
APP_NAME="ALALAY"
APP_ENV=production
APP_KEY=                          # generated above
APP_DEBUG=false
APP_URL=https://api.alalay.gmn.gov.ph

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=alalay
DB_USERNAME=alalay_user
DB_PASSWORD=strong_password_here

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_DOMAIN=.gmn.gov.ph
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Sanctum
SANCTUM_STATEFUL_DOMAINS=alalay.gmn.gov.ph

# Queue
QUEUE_CONNECTION=database

# Cache
CACHE_DRIVER=file

# Mail (OTP, Password Reset)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@gmn.gov.ph
MAIL_FROM_NAME="ALALAY System"

# Supabase Storage (S3-compatible)
SUPABASE_URL=
SUPABASE_KEY=
SUPABASE_STORAGE_BUCKET=alalay-docs
SUPABASE_STORAGE_ENDPOINT=https://<project-ref>.supabase.co/storage/v1/s3
SUPABASE_STORAGE_REGION=ap-southeast-1

# SMS API
SMS_API_KEY=
SMS_API_ENDPOINT=
SMS_SENDER_NAME=ALALAY

# CORS
FRONTEND_URL=https://alalay.gmn.gov.ph
```

### 4. Run Migrations and Seeders

```bash
php artisan migrate --force
php artisan db:seed --force
```

### 5. Storage Link

```bash
php artisan storage:link
```

### 6. Optimize for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 7. Storage and Cache Permissions

```bash
sudo chown -R www-data:www-data /var/www/alalay/storage
sudo chown -R www-data:www-data /var/www/alalay/bootstrap/cache
sudo chmod -R 775 /var/www/alalay/storage
sudo chmod -R 775 /var/www/alalay/bootstrap/cache
```

---

## Vue 3 Frontend Deployment

### 1. Install JS Dependencies and Build

```bash
cd /var/www/alalay/frontend
npm install
npm run build
```

This outputs static files to `/var/www/alalay/frontend/dist`.

### 2. Nginx will serve the `dist` folder as static files (configured below).

---

## Nginx Configuration

### Laravel API Virtual Host

Create `/etc/nginx/sites-available/alalay-api`:

```nginx
server {
    listen 80;
    server_name api.alalay.gmn.gov.ph;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.alalay.gmn.gov.ph;

    root /var/www/alalay/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/api.alalay.gmn.gov.ph/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.alalay.gmn.gov.ph/privkey.pem;

    # Security headers (NPC PBD-2, ACC-1)
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin";
    add_header Content-Security-Policy "default-src 'self'; script-src 'self'; object-src 'none';";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Block direct access to storage (files served via signed URLs only)
    location /storage {
        deny all;
    }
}
```

### Vue 3 Frontend Virtual Host

Create `/etc/nginx/sites-available/alalay-frontend`:

```nginx
server {
    listen 80;
    server_name alalay.gmn.gov.ph;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name alalay.gmn.gov.ph;

    root /var/www/alalay/frontend/dist;
    index index.html;

    ssl_certificate /etc/letsencrypt/live/alalay.gmn.gov.ph/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/alalay.gmn.gov.ph/privkey.pem;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Vue Router — all routes fall back to index.html
    location / {
        try_files $uri $uri/ /index.html;
    }

    # Cache static assets
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

Enable both sites:

```bash
sudo ln -s /etc/nginx/sites-available/alalay-api /etc/nginx/sites-enabled/
sudo ln -s /etc/nginx/sites-available/alalay-frontend /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

## SSL Certificates (Let's Encrypt)

```bash
sudo certbot --nginx -d alalay.gmn.gov.ph -d api.alalay.gmn.gov.ph
```

Certbot auto-renews certificates. Verify auto-renewal:

```bash
sudo certbot renew --dry-run
```

---

## Supervisor — Queue Worker Configuration

Create `/etc/supervisor/conf.d/alalay-worker.conf`:

```ini
[program:alalay-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/alalay/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/alalay/storage/logs/worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start alalay-worker:*
```

---

## Laravel Scheduler — Cron Job

```bash
sudo crontab -e -u www-data
```

Add:

```cron
* * * * * cd /var/www/alalay && php artisan schedule:run >> /dev/null 2>&1
```

This runs the Laravel scheduler every minute, which handles:
- Daily automated MySQL backups (`BackupDatabaseJob`)
- Retention flagging for records past their defined retention period
- `is_online` reset for users with no recent session activity

---

## Automated Database Backup

### Backup Script

Create `/var/www/alalay/scripts/backup.sh`:

```bash
#!/bin/bash

TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
BACKUP_DIR="/var/backups/alalay"
DB_NAME="alalay"
DB_USER="alalay_user"
DB_PASS="strong_password_here"
ENCRYPT_PASS="strong_encryption_passphrase_here"

mkdir -p $BACKUP_DIR

# Dump and encrypt
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | \
  gzip | \
  openssl enc -aes-256-cbc -salt -pbkdf2 -pass pass:$ENCRYPT_PASS \
  > $BACKUP_DIR/alalay_$TIMESTAMP.sql.gz.enc

# Retain only last 30 days of backups
find $BACKUP_DIR -name "*.sql.gz.enc" -mtime +30 -delete

echo "Backup completed: alalay_$TIMESTAMP.sql.gz.enc"
```

```bash
chmod +x /var/www/alalay/scripts/backup.sh
```

Register in Laravel Scheduler (`app/Console/Kernel.php`):

```php
$schedule->exec('/var/www/alalay/scripts/backup.sh')->dailyAt('02:00');
```

> **NPC Compliance Note (BCP-1):** Backups run daily at 2:00 AM, AES-256 encrypted, retained for 30 days. Store copies offsite (e.g., upload to a separate Supabase Storage bucket or external drive) for full BCP compliance.

---

## CORS Configuration (Production)

In `config/cors.php`:

```php
return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [env('FRONTEND_URL', 'http://localhost:5173')],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

---

## Security Hardening Checklist (Production)

| Item | Action | NPC Ref |
|---|---|---|
| `APP_DEBUG=false` | Set in `.env` | PBD-2 |
| HTTPS enforced | Nginx redirects HTTP → HTTPS | ACC-1 |
| HSTS header | `Strict-Transport-Security` in Nginx config | ACC-1 |
| Security response headers | `X-Frame-Options`, `X-Content-Type-Options`, `CSP` in Nginx | PBD-2 |
| MySQL remote root disabled | `sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH auth_socket;"` | ACC-1 |
| MySQL app user has no UPDATE/DELETE on audit_logs/reviews | Granted during DB setup | MSC-3 |
| Nginx blocks direct storage access | `location /storage { deny all; }` in Nginx config | TRF-2 |
| UFW firewall — only ports 22, 80, 443 open | UFW configuration | ACC-1 |
| SSH key-based auth only (disable password SSH) | Edit `/etc/ssh/sshd_config`: `PasswordAuthentication no` | ACC-8 |
| Fail2ban for SSH brute force | `sudo apt install fail2ban -y` | ACC-6 |
| Laravel rate limiting on login | `throttle:5,1` on `/api/login` route | ACC-6 |
| File permissions — storage/bootstrap writable by www-data only | `chown -R www-data:www-data` | ACC-1 |
| `.env` not accessible via web | Nginx `deny all` for dotfiles | ACC-1 |
| `composer install --no-dev` | No dev packages in production | PBD-2 |
| Queue worker managed by Supervisor | Never dies silently | BCP-1 |
| Daily encrypted backup | Via cron + backup script | BCP-1 |
| SSL auto-renewal | Certbot cron | ACC-1 |

---

## Deployment Checklist (Go-Live)

```
PRE-DEPLOYMENT
  [ ] VPS provisioned (Ubuntu 22.04 LTS)
  [ ] Domain .gov.ph requested from DICT
  [ ] DNS A records pointed to VPS IP (alalay.gmn.gov.ph, api.alalay.gmn.gov.ph)
  [ ] Supabase Storage project created, buckets configured as private
  [ ] SMS API credentials obtained from provider
  [ ] SMTP mail credentials configured

SERVER SETUP
  [ ] Nginx installed and running
  [ ] PHP 8.2-FPM installed with all extensions
  [ ] MySQL 8.x installed, secured, database + restricted user created
  [ ] Composer installed
  [ ] Node.js 20 LTS installed
  [ ] Supervisor installed and running
  [ ] Certbot installed, SSL certificates issued
  [ ] UFW firewall enabled (ports 22, 80, 443 only)
  [ ] Fail2ban installed

APPLICATION DEPLOYMENT
  [ ] Repository cloned to /var/www/alalay
  [ ] composer install --optimize-autoloader --no-dev
  [ ] .env configured with all production values
  [ ] php artisan key:generate
  [ ] php artisan migrate --force
  [ ] php artisan db:seed --force
  [ ] php artisan storage:link
  [ ] php artisan config:cache
  [ ] php artisan route:cache
  [ ] php artisan view:cache
  [ ] File permissions set (www-data)
  [ ] Vue 3 frontend built (npm run build)
  [ ] Nginx virtual hosts configured and enabled
  [ ] Supervisor worker configured and running
  [ ] Cron job for scheduler registered

POST-DEPLOYMENT VERIFICATION
  [ ] HTTPS enforced on both subdomains
  [ ] Login + MFA flow working
  [ ] File upload to Supabase Storage working
  [ ] SMS notification delivered successfully (test application)
  [ ] Queue worker processing jobs (check storage/logs/worker.log)
  [ ] Scheduler running (check php artisan schedule:list)
  [ ] Audit log writing on user actions
  [ ] Backup script running and producing encrypted output
  [ ] Admin force-logout working (session revocation)
  [ ] All role-based access restrictions verified per role-permission matrix
```

---

## Local vs Production Environment Comparison

| Concern | Local (XAMPP) | Production (VPS) |
|---|---|---|
| Web server | Apache (XAMPP) | Nginx + PHP-FPM |
| PHP | XAMPP bundled PHP | PHP 8.2+ (`ppa:ondrej/php`) |
| Database | XAMPP MySQL | MySQL 8.x (hardened) |
| Queue worker | Manual `php artisan queue:work` | Supervisor daemon (auto-restart) |
| Task scheduler | Manual | System cron |
| HTTPS | None | Let's Encrypt (enforced) |
| `APP_DEBUG` | `true` | `false` |
| File storage | Local disk (for testing) | Supabase Storage (S3) |
| Session domain | Not needed | `SESSION_DOMAIN=.gmn.gov.ph` |
| CORS origin | `http://localhost:5173` | `https://alalay.gmn.gov.ph` |
| Backups | Manual | Automated daily encrypted |
| Firewall | None | UFW (ports 22, 80, 443) |
| SSH | Password login | Key-based only |

---

*Document prepared for AI consumption and system development reference — ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*
