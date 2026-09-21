# NPC Compliance Checklist

System-level items needed for full Data Privacy Act (RA 10173) compliance.
Excludes external/organizational requirements (DPAs, NDAs, DPO designation, formal policy documents).

---

## Privacy Notice & Consent

- [ ] **Privacy Notice Screen** — Display before the Apply form: what data is collected, purposes, scope, recipients, storage period, and data subject rights
- [ ] **Consent Recording** — Store proof that data subjects consented to data processing (timestamp + IP address)
- [ ] **Privacy Policy Page** — Public-facing page accessible from the Apply page footer/header

## Data Subject Rights

- [ ] **Data Subject Access Request** — Allow applicants to request a full copy of their personal data (via reference code + OTP verification)
- [ ] **Right to Correction** — Allow applicants to request corrections to their personal information
- [ ] **Right to Blocking/Erasure** — Add mechanism to request deletion/anonymization of personal data
- [ ] **Data Export for Data Subjects** — Provide downloadable copy of their data in electronic format (JSON/CSV)
- [ ] **Access Audit Trail for Data Subjects** — Show applicants when their data was last accessed and by whom

## Data Retention & Disposal

- [ ] **Data Retention Policy Enforcement** — Define and enforce retention periods per record type (e.g., X years after last application)
- [ ] **Automated Data Purging** — Scheduled job to flag/archive/delete data past retention period
- [ ] **Secure Disposal** — Ensure deleted data is irrecoverable (not just soft-deleted)

## Breach Notification

- [ ] **Breach Detection** — Automated monitoring for suspicious access patterns (unusual queries, bulk data access)
- [ ] **Breach Notification Pipeline** — Workflow to notify NPC and affected data subjects within 72 hours
- [ ] **Breach Incident Log** — Dedicated log for tracking breach incidents and response actions

## Transparency & Accountability

- [ ] **Purpose Limitation Enforcement** — System-level check ensuring data is only used for its declared purpose
- [ ] **Automated Decision Disclosure** — If any automated process affects the applicant, disclose the logic involved
- [ ] **Data Processing Activity Log** — Record all processing activities as required by NPC (what data, why, who accessed, when)

## Already Implemented (for reference)

- [x] PII encrypted at rest (addresses, phone, email)
- [x] Role-based access control with policy authorization
- [x] MFA (email OTP with hashing, attempts limit, expiry)
- [x] Comprehensive audit logging (every action tracked)
- [x] 12 rate limiters (brute force protection)
- [x] Security headers (CSP, X-Frame-Options, etc.)
- [x] Signed URLs for document access (15-min expiry)
- [x] Encrypted database backups (AES-256-CBC)
- [x] Input sanitization (strip_tags, XSS protection)
- [x] CSRF protection
- [x] Turnstile CAPTCHA on public forms
- [x] Honeypot bot detection
- [x] File upload validation (MIME whitelist, size limits)
- [x] 100% Eloquent ORM (SQL injection protected)
- [x] Session regeneration on login, invalidation on logout
- [x] Admin session revocation capability
- [x] Acceptable Use Policy acknowledgment
- [x] Beneficiary eligibility enforcement
- [x] Backup verification system
