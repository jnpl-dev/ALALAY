# ALALAY: NPC Circular 2023-06 Compliance Specification
**Security of Personal Data — Government Application Compliance**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Purpose

This document translates **NPC Circular 2023-06** (Security of Personal Data in the Government and the Private Sector) into concrete technical and organizational requirements for the ALALAY system. ALALAY processes **sensitive personal information** of applicants and beneficiaries (full names, addresses, contact details, birthdates, crisis-situation status, financial assistance amounts), making the Municipality of General Mamerto Natividad the **Personal Information Controller (PIC)** under the Data Privacy Act of 2012 (RA 10173).

This document is the **authoritative compliance reference** for all development work on ALALAY. Any feature, page, or panel that touches personal data must be checked against this document.

---

## Key Definitions (per the Circular)

| Term | Definition |
|---|---|
| **PIC** | Personal Information Controller — the Municipality of General Mamerto Natividad |
| **PIP** | Personal Information Processor — any third party processing data on the PIC's behalf (e.g., SMS API provider, hosting provider) |
| **DPO** | Data Protection Officer — designated and registered with the NPC |
| **Access Control Policy** | Rules defining who may access what data, and under what circumstances |
| **Acceptable Use Policy** | Rules personnel must agree to before being granted system access |
| **Privacy-by-Design** | Privacy safeguards built into system architecture from the start |
| **Privacy-by-Default** | Only data necessary for the specific purpose is processed, by default |
| **Control Framework** | The full set of organizational, physical, and technical security measures |

---

## Compliance Requirements by Rule

---

### RULE I — General Obligations (Sections 4–6)

| ID | Requirement | Scope | Status / Action |
|---|---|---|---|
| GEN-1 | Designate and register a Data Protection Officer (DPO) with the NPC | Organizational | Municipality must formally designate a DPO (e.g., MSWDO Head or designated IT/Admin officer) |
| GEN-2 | Register ALALAY as a data processing system with the NPC | Organizational | Required before/during production deployment |
| GEN-3 | Maintain a data inventory of all personal data held | Organizational + Technical | Schema dictionary (`applications`, `application_documents`, `social_case_studies`, `vouchers`) serves as the technical data inventory |
| GEN-4 | Conduct a Privacy Impact Assessment (PIA) | Organizational | Must document: data collected, purpose, storage location, identified risks. Required before go-live and after major changes |
| GEN-5 | Establish a Control Framework addressing PIA-identified risks | Organizational + Technical | This document is ALALAY's Control Framework reference |

---

### RULE II — Privacy-by-Design and Privacy-by-Default (Sections 7–8)

| ID | Requirement | Scope | Implementation in ALALAY |
|---|---|---|---|
| PBD-1 | Process only data necessary for the specific purpose by default | Technical | Schema restricted to fields required for AICS eligibility per MC 2022-015. No collection of unrelated identifiers (e.g., SSS, TIN) unless explicitly required |
| PBD-2 | Deactivate any unused or non-lawful-basis functions | Technical | Disable debug/dev routes in production (`APP_DEBUG=false`); remove unused Laravel package routes (e.g., Telescope, Horizon) from production builds |
| PBD-3 | Embed privacy requirements throughout development lifecycle | Technical | Enforce data visibility through Laravel **Policies/Gates** at the backend — never rely on frontend hiding alone. Example: a non-Accountant/Treasurer role must not be able to query `assistance_codes.amount` even via direct API call |

---

### RULE III — Storage of Personal Data (Sections 9–11)

| ID | Requirement | Scope | Implementation in ALALAY |
|---|---|---|---|
| STO-1 | Store personal data only as long as necessary; document a Retention Policy | Organizational + Technical | Define retention periods per record type, aligned with COA/DSWD records retention schedules (commonly 5–10 years for social welfare/financial records) |
| STO-2 | Adequately protect personal data via industry-standard measures | Technical | Apply Laravel `encrypted` Eloquent casts on sensitive columns: `claimant_address`, `claimant_phone`, `claimant_email`, `beneficiary_address` |
| STO-3 | Issue and enforce a Password Policy | Technical | Enforce via Laravel: `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()` on all `users` password validation (create, update, reset) |

---

### RULE IV — Access to Personal Data (Sections 12–20)

**This is the highest-priority section for ALALAY's system architecture.**

| ID | Requirement | Scope | Implementation in ALALAY |
|---|---|---|---|
| ACC-1 | Personal data accessed/modified only via authorized software | Technical | All database access goes through the Laravel application layer only. No direct phpMyAdmin/MySQL access for non-Admin roles in production. Disable remote MySQL root access in XAMPP `my.ini` for production |
| ACC-2 | Implement an Access Control Policy on a "need to know" basis | Technical | Enforced via role-based panel architecture: Laravel `RoleMiddleware` on all route groups + Laravel **Policies** per model (`ApplicationPolicy`, `VoucherPolicy`, `AssistanceCodePolicy`, etc.). Example: AICS Staff cannot query the `vouchers` table; Accountant cannot edit `applications` claimant fields |
| ACC-3 | Issue security clearance to authorized personnel; file copy with DPO | Organizational | Admin maintains a record of each user's granted access level (can extend `system_settings` or a dedicated log), filed with the DPO |
| ACC-4 | PIP access governed by formal contracts | Organizational | Applies if ALALAY engages third-party processors (e.g., SMS API provider, cloud hosting). Contracts must include DPA-compliant data protection clauses |
| ACC-5 | Maintain and explain an Acceptable Use Policy; users must sign/acknowledge before access | Technical + Organizational | Add `acceptable_use_policy_accepted_at` column to `users` table. Force a one-time acknowledgment screen on first login before any panel access is granted |
| ACC-6 | Secure authentication (MFA or secure encrypted links) for access to sensitive/high-volume personal data | Technical | **Critical requirement.** Implement Multi-Factor Authentication (MFA) via Email OTP — a 6-digit code sent to the user's registered email upon login — for all internal panel roles: Admin, AICS Staff, MSWDO, Accountant, Treasurer. Recommended (not just optional) for Mayor's Office Staff as well |
| ACC-7 | User access rights and authentication defined/controlled by a System Management Tool | Technical | `users.role` + Laravel Policies/Gates function as this tool. Document the full role-permission matrix (see Appendix A) |
| ACC-8 | Only known, properly configured devices authorized to access personal data | Technical + Organizational | For municipal deployment: restrict internal panel access to office network/VPN where feasible (IP allowlisting at Apache/firewall level). Track `sessions.ip_address` and `sessions.user_agent` to detect anomalous device access (already in schema — surface in Admin "Unusual Activities" table) |
| ACC-9 | Enable remote disconnection/deletion for lost or compromised devices | Technical | Add an Admin action to force-logout a user by invalidating all their `sessions` rows. Surface this in User Management panel as a "Revoke All Sessions" action |
| ACC-10 | Maintain access logs for physical filing systems | Organizational | For hybrid (walk-in) submissions, any physical document handling at MSWDO/AICS office must be logged manually (who accessed, when, whether copied). Digital trail already covered by `audit_logs` + `application_documents.created_at` |
| ACC-11 | Data Sharing Agreements required before sharing data with other parties | Organizational | Required before any future integration (e.g., reporting data to DSWD central, API exports). Not currently in scope — flag for future development |

---

### RULE V — Business Continuity (Sections 21–22)

| ID | Requirement | Scope | Implementation in ALALAY |
|---|---|---|---|
| BCP-1 | Maintain a Business Continuity Plan covering backup, restoration, and remedial time | Organizational + Technical | Implement scheduled automated MySQL/MariaDB backups (daily `mysqldump` via cron/Task Scheduler), stored encrypted, off the production server |
| BCP-2 | Periodically review and test the BCP | Organizational | Schedule quarterly restore-drill testing to confirm backups are actually restorable |
| BCP-3 | Document RTO/RPO and critical contact information | Organizational | Document Recovery Time Objective and Recovery Point Objective as part of the BCP |
| BCP-4 | Secure telecommuting/remote access practices | Technical + Organizational | If staff access ALALAY remotely, enforce MFA (ACC-6) + device security guidance. No use of personal/public computers for processing applications |

---

### RULE VI — Transfer of Personal Data (Sections 23–27)

| ID | Requirement | Scope | Implementation in ALALAY |
|---|---|---|---|
| TRF-1 | Secure transmission for emails containing personal data | Technical | If ALALAY sends emails (beyond SMS), use TLS-enabled SMTP. Do not include full personal data in plain email body — link to secure tracking page instead |
| TRF-2 | Restrict copying personal data to personal productivity software | Technical + Organizational | Restrict CSV/export permissions appropriately — raw PII exports limited to Admin and relevant role only (not aggregate Mayor's Office views). Log every export action to `audit_logs` |
| TRF-3 | Encrypt personal data on removable/portable storage media | Organizational | If documents/backups are transferred via USB, require encryption (e.g., encrypted container/ZIP) |
| TRF-4 | No use of fax machines for personal data | N/A | Not applicable — ALALAY does not use fax. Documented for compliance completeness only |
| TRF-5 | Secure transmittal procedures for physical documents | Organizational | For hybrid/walk-in submissions, physical document handoff between AICS Staff → MSWDO → Accountant offices should follow a chain-of-custody log, complementing the digital `reviews` trail |

---

### RULE VII — Disposal of Personal Data (Sections 28–31)

| ID | Requirement | Scope | Implementation in ALALAY |
|---|---|---|---|
| DIS-1 | Establish disposal policy considering retention period, applicable laws, and de-identification techniques | Organizational | Define retention period per record type. Records past retention should be **flagged for review**, not auto-deleted |
| DIS-2 | Retain logs appropriately; security-related logs retained longer than general logs | Technical | `audit_logs` and `reviews` (append-only in our schema) must be retained longer than general logs — minimum 5 years recommended, longer if tied to an active investigation. Implement archiving (move to `audit_logs_archive`) rather than deletion |
| DIS-3 | Establish secure disposal/destruction procedures | Technical + Organizational | ALALAY should **never hard-delete** application records during normal operation — use soft-disable/archive only. Actual disposal must be a deliberate, logged, Admin-authorized action with documentation |
| DIS-4 | Engage PIP for disposal under contractual data protection terms | Organizational | Applies only if outsourcing data/hardware destruction (e.g., decommissioned servers). Not currently in scope |

---

### RULE VIII — Miscellaneous (Sections 32–35)

| ID | Requirement | Scope | Implementation in ALALAY |
|---|---|---|---|
| MSC-1 | Continuous threat monitoring and vulnerability management | Technical | Keep Laravel, PHP, MySQL/MariaDB, and XAMPP components patched. Run periodic dependency vulnerability scans (`composer audit`, `npm audit`). Monitor NCERT advisories |
| MSC-2 | Personal Data Breach Management procedure | Organizational | Establish a documented breach response plan: detect (via `audit_logs` anomaly monitoring), contain, assess, notify NPC within required timeframe, notify affected data subjects |
| MSC-3 | Support independent audit/verification | Organizational | `audit_logs` table directly supports this — must be tamper-evident (append-only; no UPDATE/DELETE grants at the DB user level for any application role) |
| MSC-4 | Comply with NPC enforcement actions and penalties | Organizational | Institutional accountability — reinforces the need for all measures above |

---

## Priority Technical Implementation Checklist

Ordered by impact and dependency. Use this as the implementation roadmap.

| Priority | Measure | Requirement ID | Laravel Implementation |
|---|---|---|---|
| 1 | Role-based access control ("need to know") | ACC-2 | Laravel Policies + Gates per model; `RoleMiddleware` on all route groups |
| 2 | Multi-Factor Authentication | ACC-6 | Email OTP (6-digit code sent via Laravel Mail) for all internal panel roles |
| 3 | Encryption of sensitive fields at rest | STO-2 | Eloquent `encrypted` casts on PII columns (phone, address, email) |
| 4 | Strong password policy | STO-3 | `Password::min(12)->mixedCase()->numbers()->symbols()->uncompromised()` |
| 5 | Append-only, tamper-evident audit logs | MSC-3 | No UPDATE/DELETE grants on `audit_logs` / `reviews` at DB user level |
| 6 | Session security & forced logout | ACC-9 | Admin "Revoke All Sessions" action; track `ip_address` / `user_agent`; session timeout policy |
| 7 | Automated encrypted backups | BCP-1 | Scheduled `mysqldump` + offsite encrypted storage; tested restore drills |
| 8 | No hard deletes — soft archive only | DIS-3 | `is_active` / status flags instead of `DELETE`; Admin-authorized disposal workflow |
| 9 | Production hardening | PBD-2, ACC-1 | `APP_DEBUG=false`; disable remote DB root access; enforce HTTPS/TLS + HSTS headers |
| 10 | Export/print controls + logging | TRF-2 | Every CSV export and document view logged to `audit_logs` with user, timestamp, record accessed |
| 11 | Acceptable Use Policy acknowledgment | ACC-5 | Forced one-time acknowledgment screen on first login per user |
| 12 | Retention & disposal policy | STO-1, DIS-1 | Documented retention schedule; scheduled flagging job; no auto-deletion |

---

## Appendix A — Role-Permission Matrix (System Management Tool Reference)

Per **Section 16**, this matrix documents how user access rights and authentication mechanisms are defined and controlled.

| Role | MFA Required | Applications (View) | Applications (Decide) | Vouchers (View) | Vouchers (Decide) | Assistance Amounts (View) | User Management | Audit Logs | System Settings |
|---|---|---|---|---|---|---|---|---|---|
| Applicant | No (public, reference-code based) | Own application only | N/A | No | No | No | No | No | No |
| AICS Staff | Yes | Assigned stage only | Yes (screening, coding) | No | No | Yes (own coding actions) | No | No | No |
| MSWDO | Yes | Assigned stage only | Yes (review, voucher creation) | Yes (own created) | No | Yes (read, for voucher creation) | No | No | No |
| Accountant | Yes | Read-only (voucher-linked) | No | Yes | Yes (approve/return, budget check) | Yes | No | No | No |
| Treasurer | Yes | Read-only (voucher-linked) | No | Yes (acknowledgment) | Yes (acknowledge only) | Yes | No | No | No |
| Mayor's Office Staff | Recommended | Read-only (all, aggregate) | No | Read-only (all, aggregate) | No | Yes (aggregate only) | No | No | No |
| Admin | Yes | No (not in workflow) | No | No (not in workflow) | No | No (not in workflow) | Yes (full CRUD) | Yes (full read) | Yes (full CRUD) |

---

## Appendix B — Data Classification

| Data Element | Classification | Applicable Measures |
|---|---|---|
| `claimant_*`, `beneficiary_*` name fields | Personal Information | Access control, encryption at rest, audit logging |
| `claimant_address`, `beneficiary_address` | Personal Information | Encryption at rest |
| `claimant_phone`, `claimant_email` | Personal Information | Encryption at rest, MFA-gated access |
| `claimant_dob`, `beneficiary_dob` | Personal Information | Access control |
| `social_case_studies` file content | Sensitive Personal Information (crisis-situation details) | Strictest access control (MSWDO + AICS Staff only), encrypted storage, audit logging on every view |
| `application_documents` (supporting docs) | Sensitive Personal Information (may contain IDs, medical/financial proof) | Same as above |
| `assistance_codes.amount`, `vouchers` | Financial Information | Restricted to AICS Staff (assign), MSWDO (voucher), Accountant, Treasurer only |
| `users.password` | Authentication Credential | Hashed (bcrypt), never logged, never exported |
| `audit_logs`, `reviews` | Accountability Record | Append-only, extended retention, tamper-evident |

---

## Appendix C — Items Requiring Organizational (Non-Code) Action

These cannot be solved through code alone and require Municipality-level policy decisions before or alongside development:

1. Formal designation and NPC registration of the Data Protection Officer (GEN-1).
2. NPC registration of ALALAY as a data processing system (GEN-2).
3. Completion of a documented Privacy Impact Assessment (GEN-4).
4. Issuance of a signed Acceptable Use Policy for all staff (ACC-5).
5. Issuance of security clearances filed with the DPO (ACC-3).
6. Defined data retention schedule aligned with COA/DSWD requirements (STO-1, DIS-1).
7. Documented Business Continuity Plan with RTO/RPO (BCP-1, BCP-3).
8. Documented Personal Data Breach Management / incident response procedure (MSC-2).
9. Chain-of-custody log for physical documents in hybrid submission (ACC-10, TRF-5).
10. Any Data Sharing Agreement before integrating with DSWD central or other external systems (ACC-11).

---

*Document prepared for AI consumption and development reference — ALALAY System compliance with NPC Circular 2023-06, Municipality of General Mamerto Natividad, Nueva Ecija.*
