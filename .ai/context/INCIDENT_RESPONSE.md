# ALALAY — Incident Response Procedure

**Municipality of General Mamerto Natividad, Nueva Ecija**

Print this document and store a physical copy in the MSWDO/IT office.

---

## Emergency Commands

Run these from the server terminal (`/var/www/alalay`).

### Put System in Maintenance Mode
```bash
php artisan down --secret="your_maintenance_secret"
```

### Bring System Back Online
```bash
php artisan up
```

### Force Logout ALL Active Users (Session Breach)
```bash
php artisan tinker
DB::table('sessions')->truncate();
exit
```

### Check Who is Logged In
```sql
SELECT u.first_name, u.last_name, u.role, s.ip_address, s.last_activity
FROM sessions s
JOIN users u ON u.id = s.user_id
ORDER BY s.last_activity DESC;
```

### Check Recent Audit Logs
```sql
SELECT * FROM audit_logs ORDER BY created_at DESC LIMIT 50;
```

### Restart Services
```bash
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
sudo supervisorctl restart alalay-worker:*
```

### View Application Error Log
```bash
tail -f /var/www/alalay/storage/logs/laravel.log
```

---

## Incident Types

### 1. Data Breach Suspected
1. Immediately put system in maintenance mode.
2. Force logout all active sessions.
3. Check audit logs for suspicious activity.
4. Check who was logged in at the time.
5. Contact the Municipal Administrator and DPO.
6. Preserve server logs for investigation.

### 2. System Compromised / Unresponsive
1. Restart Nginx and PHP.
2. Check application error log.
3. If queue is stuck: restart supervisor.
4. If database issue: check `phpmyadmin` or `mysql` CLI.

### 3. Application Bug / Data Corruption
1. Put system in maintenance mode.
2. Restore latest backup to test database and verify integrity:
   ```bash
   php artisan backup:verify
   ```
3. If verified, restore to production:
   ```bash
   # Decrypt and restore to production
   openssl enc -d -aes-256-cbc -pbkdf2 -pass pass:"$BACKUP_ENCRYPT_PASS" \
     -in /var/www/alalay/storage/app/backups/alalay_*.sql.gz.enc \
     | gunzip | mysql -u root alalay
   ```

### 4. SMS API Failure
1. Check `.env` has `PHILSMS_API_TOKEN` set.
2. Retry failed SMS via queue:
   ```bash
   php artisan queue:retry all
   ```
3. Check SMS logs in `sms_notifications` table.

---

## Contacts

| Role | Person |
|------|--------|
| System Administrator | (TBD) |
| Municipal DPO | (TBD) |
| Municipal Administrator's Office | (TBD) |
| IT Support | (TBD) |

---

## Documentation

Every incident must be documented including:
- Date and time of discovery
- What happened
- How it was detected
- Steps taken to resolve
- Root cause
- Prevention measures applied

Store incident reports in `.ai/incidents/` in the repository.

---

*Document prepared for AI consumption and development reference — ALALAY System,
Municipality of General Mamerto Natividad, Nueva Ecija.*
