# ALALAY: Client Presentation Document Generation Task
**For AI Agent — Analyze Codebase and Generate Full Presentation Document**
**Municipality of General Mamerto Natividad, Nueva Ecija**

---

## Task Overview

Your job is to generate a complete, professional, client-facing system
presentation document for ALALAY. This document will be presented to
the municipal government of General Mamerto Natividad, Nueva Ecija —
specifically to the Municipal Mayor, MSWDO head, and key department
staff who will use the system.

The audience is NOT technical. They are government officials and office
staff. Write everything in plain, professional language. No code, no
technical terms, no developer jargon.

---

## How to Get the Information

Do NOT ask the developer for information. Instead, analyze the codebase
directly to extract everything you need. Here is where to find each
piece of information:

```
System overview and workflow:
  .ai/context/01_user_story.md

Database structure and data fields:
  .ai/context/02_schema_dictionary.md

What each panel shows, what each role does:
  .ai/context/03_panels_pages_content.md

Security and data privacy measures:
  .ai/context/04_npc_compliance.md

Technology used (for the technical requirements section only):
  .ai/context/05_tech_stack.md

Assistance categories and required documents:
  database/seeders/AssistanceCategorySeeder.php
  database/seeders/RequiredDocumentSeeder.php

SMS notification messages and triggers:
  .ai/context/09_sms_templates.md

Document scanner behavior:
  .ai/context/alalay_document_scanner_spec.md

Dashboard and analytics content per role:
  .ai/context/alalay_dashboard_analytics_spec.md

Roles and their permissions:
  .ai/context/07_role_permission_matrix.md

Routes and page structure:
  routes/web.php

Actual Vue pages for each role (to understand what staff see):
  resources/js/Pages/
```

Read as many of these files as needed before writing. Do not write
anything until you have read at least the user story, panels spec,
schema dictionary, and seeder files. The quality of the document
depends entirely on how well you understand the actual system.

---

## Document Specifications

### Format
- Output as a well-structured Markdown document
- Use headers, subheaders, bullet points, and tables where appropriate
- Include a table of contents at the beginning
- Page breaks indicated by `---` between major sections
- Ready to be converted to Word or PDF for printing

### Length
- Target 15–25 pages when printed at standard font size
- Do not pad with unnecessary content
- Do not truncate important sections to save space

### Language
- English throughout
- Professional but warm tone
- Write as if explaining to a respected government official
- Avoid condescension — these are educated professionals,
  just not software developers
- Use Filipino context where appropriate
  (e.g. "barangay" not "neighborhood", "MSWDO" not "social welfare office",
  "Cedula" not "community tax certificate")

### Tone
- The system serves people in financial crisis — the document should
  reflect that ALALAY was built with genuine care for that mission
- Highlight efficiency gains and applicant convenience
- Acknowledge the municipality's existing process with respect —
  ALALAY improves it, not replaces the people running it

---

## Document Structure

Generate the document in exactly this order:

---

### COVER PAGE

```
ALALAY
A Digital AICS Management and Notification System
with Hybrid Submission

System Presentation Document

Municipality of General Mamerto Natividad
Nueva Ecija, Philippines

Prepared for: The Municipal Government of General Mamerto Natividad
Date: [Current date]
```

---

### TABLE OF CONTENTS

List all sections with section numbers.

---

### SECTION 1 — EXECUTIVE SUMMARY

One page maximum. Answer these questions in plain prose:
- What is ALALAY?
- What problem does it solve?
- Who uses it?
- What is the benefit to the municipality and to its residents?

Write this so a Municipal Mayor can read it in 60 seconds and
understand the point completely.

Do NOT mention: Laravel, Vue, databases, APIs, or any technology.

---

### SECTION 2 — BACKGROUND AND PROBLEM STATEMENT

Describe the current manual AICS process and its pain points.
Extract the workflow from the user story document and describe
what it currently looks like WITHOUT the system:

- Applicants must go to the municipal office in person to submit
- Documents are physically routed between offices
- Applicants have no way to track their application status
  without physically returning to the office
- Staff have no centralized view of where each application is
  in the pipeline
- The Mayor's office has no real-time visibility into program data
  without requesting manual reports

Frame this section as: "Here is the challenge ALALAY was built to address."

---

### SECTION 3 — WHAT IS ALALAY?

Explain the system in plain terms:
- A website accessible from any browser on any device
- No special app to download or software to install
- Two types of users: the public (applicants) and internal staff
- What "hybrid submission" means — online from home OR in-person
  at the office (both options are supported)
- What the name "ALALAY" means in the context of the system's mission

Derive the content from the user story and tech stack documents.
Describe the technology only at the level of: "It runs on a secure
web server and can be accessed from any internet browser."

---

### SECTION 4 — KEY FEATURES AND BENEFITS

Write as a list of features with a one-paragraph explanation each.
Use benefit language — not "the system has a queue worker" but
"SMS notifications are sent automatically — no staff action required."

Features to cover (derive details from the context documents):

1. **Online and Walk-in Application Support (Hybrid Submission)**
   Explain both paths. Applicant at home submits online.
   Walk-in applicant is encoded by AICS Staff.

2. **Reference Code Tracking**
   Every application gets a unique reference code.
   Applicant can check their status anytime from any device.
   No need to return to the office just to ask about status.

3. **Camera-Based Document Scanning**
   Derive from alalay_document_scanner_spec.md.
   Explain in plain terms: applicant uses their phone camera to
   scan physical documents. The system automatically enhances the
   image like a document scanner app. No file uploads from storage
   — documents are always freshly captured.

4. **Automatic SMS Notifications**
   Derive trigger events from sms_templates context file.
   Applicant receives a text message at each critical stage.
   List all four trigger events with the plain-language description
   of when each fires.

5. **Step-by-Step Digital Workflow**
   Each stage of the AICS process is digitized and tracked.
   Each role only sees and acts on what is relevant to them.
   No paper routing between offices.

6. **Real-Time Status Tracking for Applicants**
   The Track page — applicant enters reference code,
   sees a visual timeline of exactly where their application is.
   Like package tracking for their assistance application.

7. **Role-Based Staff Panels**
   Each role (AICS Staff, MSWDO, Accountant, Treasurer,
   Mayor's Office) has a dedicated panel showing only what is
   relevant to them. Derive from panels_pages_content spec.

8. **Dashboards and Reports**
   Derive from alalay_dashboard_analytics_spec.md.
   Each role sees KPIs relevant to their work.
   Mayor's Office sees municipal-level data and can generate
   date-filtered reports without requesting them manually.

9. **Secure Document Storage**
   All scanned documents stored securely in cloud storage.
   Only authorized staff can view documents.
   Documents never publicly accessible.

10. **Data Privacy Compliance**
    Derive from npc_compliance.md.
    Explain in plain terms: personal information is encrypted,
    access is logged, and the system is designed to comply with
    the Data Privacy Act of 2012 (RA 10173) and NPC Circular 2023-06.

---

### SECTION 5 — THE APPLICATION WORKFLOW

This is the most important section. Write a clear, numbered
step-by-step description of the complete application lifecycle.

Derive entirely from user_story.md and panels_pages_content.md.

Format as numbered stages with:
- Stage name
- Who acts at this stage
- What they do
- What happens next
- What the applicant is notified about (if applicable)

Include a simple text-based flowchart showing the complete pipeline:
```
Applicant Submits → AICS Screening → MSWDO Review → Assistance Coding
→ Voucher Creation → Voucher Checking → Treasurer → Budget Checking
→ Cheque Ready → Applicant Claims
```

Also include the two return/resubmission paths clearly:
- AICS Staff returns → applicant resubmits
- MSWDO returns → applicant resubmits

And the on-hold path:
- Budget unavailable → On Hold → Re-evaluated when budget is available

Write this so an MSWDO staff member reading it recognizes
their own process exactly.

---

### SECTION 6 — USER ROLES AND RESPONSIBILITIES

One section per role. For each role write:
- Role title
- Who this typically is in the municipal office
- What they can do in ALALAY
- What they cannot do (briefly — for context)
- What their typical daily workflow looks like

Roles to cover:
1. Applicant (public — not a staff account)
2. AICS Staff
3. MSWDO (Municipal Social Welfare and Development Office)
4. Accountant
5. Treasurer
6. Mayor's Office Staff
7. System Administrator (Admin)

Derive from panels_pages_content.md and role_permission_matrix.md.

Do not mention technical terms like "middleware" or "policy."
Write as job descriptions.

---

### SECTION 7 — ASSISTANCE CATEGORIES AND REQUIREMENTS

Three subsections — one per category:
1. Medical Assistance
2. Hospital Assistance
3. Burial Assistance

For each:
- What it covers (plain description)
- Who qualifies (general — people in crisis situations)
- Required documents (from the seeder files — list all mandatory
  documents and note that Authorization Letter is required when
  the claimant is not a direct relative)
- Note which documents require both front and back (Government IDs)
- Note which documents may have multiple pages (Hospital Bill)

Derive from:
  database/seeders/RequiredDocumentSeeder.php
  database/seeders/AssistanceCategorySeeder.php
  .ai/context/alalay_document_scanner_spec.md

---

### SECTION 8 — SMS NOTIFICATION SYSTEM

Explain that applicants automatically receive SMS messages at
critical stages of their application — no staff action required.

Derive actual message content from sms_templates context file.

Present as a table:

| When | Message Sent |
|---|---|
| Application submitted | [actual SMS content] |
| Application under review | [actual SMS content] |
| Resubmission needed | [actual SMS content] |
| Cheque ready for claiming | [actual SMS content] |

Explain that:
- Messages are sent to the phone number provided at application
- Messages include the reference code for easy tracking
- The SMS service uses a Philippine SMS API provider
- No additional cost to the applicant — messages are sent by the system

---

### SECTION 9 — DOCUMENT SCANNER

Explain the camera-based document capture feature in plain terms.
Derive from alalay_document_scanner_spec.md.

Cover:
- Why scanning instead of uploading (security + simplicity)
- How it works step by step (in plain language — point camera,
  capture, automatic enhancement, preview, confirm or retake)
- What "automatic enhancement" means (makes the image clearer,
  like a document scanner app — white background, sharp text)
- Multi-page support (Government IDs capture front and back as
  one document; Hospital Bills can capture multiple pages)
- The result is always a PDF — professional format suitable
  for official records
- Fallback for when camera is not available

Do not mention: canvas, MediaDevices API, jsPDF, adaptive thresholding.

---

### SECTION 10 — DASHBOARDS AND REPORTS

Explain what each role sees on their dashboard and analytics page.
Derive from alalay_dashboard_analytics_spec.md.

Present as one subsection per role:

**AICS Staff Dashboard:**
What KPIs they see, what charts, what the purpose is.

**MSWDO Dashboard:**
Same.

**Accountant Dashboard:**
Same.

**Treasurer Dashboard:**
Same.

**Mayor's Office Dashboard and Reports:**
Give this the most detail — it is the most relevant for the
client meeting audience. Emphasize:
- Real-time data without requesting manual reports
- Barangay-level breakdown (which barangays have most applicants)
- Total assistance disbursed
- Application trend over time
- Date-filtered analytics for any period

---

### SECTION 11 — DATA PRIVACY AND SECURITY

Write this section carefully — it is required for a government system.
Derive from npc_compliance.md.

Cover in plain language:

1. **Data Privacy Act Compliance**
   ALALAY is designed to comply with Republic Act 10173 (Data Privacy Act
   of 2012) and NPC Circular 2023-06. Explain what this means for applicants
   and for the municipality without using legal jargon.

2. **What Personal Information is Collected**
   Name, address, date of birth, phone number, email (optional),
   and scanned supporting documents. Nothing beyond what is needed
   for AICS processing.

3. **How Information is Protected**
   - Sensitive fields (address, phone, email) are encrypted in storage
   - Only authorized staff can access applicant information
   - Every staff action is recorded in an audit log
   - Files are stored in secure private cloud storage —
     not accessible without authorization
   - Multi-factor authentication for all staff accounts

4. **The Municipality's Responsibilities**
   The municipality must designate a Data Protection Officer (DPO),
   register ALALAY with the National Privacy Commission (NPC),
   and complete a Privacy Impact Assessment (PIA).
   These are organizational requirements separate from the system itself.

5. **Audit Trail**
   Every action taken by every staff member is logged with a timestamp.
   This provides full accountability for the processing of every application.

Do not mention: UUIDs, bcrypt, Redis, Eloquent, database permissions.

---

### SECTION 12 — TECHNICAL REQUIREMENTS

Write this for the municipal IT officer or Admin, not for developers.

**For Applicants (public):**
- A smartphone, tablet, or computer with a browser
- Internet connection
- A working camera (for document scanning)
- A Philippine mobile number for SMS notifications

**For Staff:**
- A desktop computer or laptop with a modern browser
  (Chrome, Firefox, Edge — any modern browser works)
- Internet connection at the municipal office
- No special software to install

**For the System to Run:**
- A server (either a cloud server or a dedicated office desktop
  running as a server)
- Internet connection at the server location
- A .gov.ph domain (requested from DICT)
- An SSL certificate (for secure HTTPS connection — free via Let's Encrypt)

**What the Municipality Provides:**
- Server hardware or cloud subscription
- Internet connection
- Staff devices (computers/tablets)
- .gov.ph domain request coordination with DICT

Do not mention: Ubuntu, PHP, Laravel, MySQL, Nginx, Redis.
Do say: "runs on a standard web server with a database."

---

### SECTION 13 — IMPLEMENTATION AND ONBOARDING PLAN

A proposed timeline from system setup to go-live.

Structure as phases:

**Phase 1 — System Setup (Week 1–2)**
- Server setup and domain configuration
- System installation and initial configuration
- Admin account creation
- Assistance categories and staff accounts configured

**Phase 2 — Data Setup and Testing (Week 2–3)**
- All staff accounts created by Admin
- Test applications submitted through the system
- Workflow tested end-to-end
- SMS notifications verified

**Phase 3 — Staff Training (Week 3–4)**
- AICS Staff training (half day)
- MSWDO training (half day)
- Accountant training (half day)
- Treasurer training (half day)
- Mayor's Office Staff training (2 hours — dashboard and reports only)
- Admin/IT Officer training (full day)

**Phase 4 — Parallel Run (Week 4–6)**
- System runs alongside the existing manual process
- Both paper and digital records maintained temporarily
- Issues identified and resolved

**Phase 5 — Go-Live (Week 6+)**
- Digital process is the primary system
- Paper process phased out
- Support available for the first month

---

### SECTION 14 — SUPPORT AND MAINTENANCE

**Ongoing Support:**
- Who to contact for technical issues (developer/vendor contact)
- Response time expectations
- How to report bugs or request changes

**System Maintenance:**
- Automated daily backups — data is backed up every day
- Software updates applied as needed
- No downtime expected during normal operation

**Municipality Responsibilities After Go-Live:**
- Keep the server running (if on-premise)
- Maintain internet connectivity
- Add/remove staff accounts via the Admin panel
- Monitor the system via the Admin dashboard
- File required reports with the NPC as a registered data processor

---

### SECTION 15 — FREQUENTLY ASKED QUESTIONS

Anticipate questions the client will ask at the presentation.
Write 8–12 Q&A pairs based on what a Municipal Mayor and department
heads would realistically ask. Derive answers from the context documents.

Example questions to include:
- What happens if an applicant does not have a smartphone?
- What if the internet goes down at the office?
- Can the system handle multiple AICS Staff members?
- How does the system prevent duplicate applications?
- Is the data stored in the Philippines?
- What happens to the data if we stop using the system?
- Can we add new assistance categories later?
- Who can see the applicant's personal information?
- What if a staff member leaves the municipality?
- Can the Mayor see specific applicants' information?

---

### CLOSING

A brief closing statement about the system's purpose aligned with
the municipality's mission to serve its residents.

Include:
- A commitment to data privacy and responsible data handling
- Openness to feedback and improvements
- Contact information placeholder for the development team

---

## Output Instructions

1. Generate the complete document in one response if possible.
   If it must be split, indicate clearly where to continue.

2. Use proper Markdown heading hierarchy:
   `#` for document title
   `##` for section numbers
   `###` for subsections
   `####` for sub-subsections

3. Use tables where comparison or lists of items benefit from structure.

4. Use `>` blockquote for important callouts or highlighted notes.

5. After completing the document, list at the end:
   - Which context files you read
   - Any information you could not find in the codebase
     (so the developer can fill it in manually)
   - Any section that needed assumptions
     (so the developer can verify accuracy)

6. Do not add a "generated by AI" disclaimer — this is a professional
   document that will be printed and presented to a government client.

---

## What NOT to Include Anywhere in the Document

- Laravel, Vue, Inertia, PHP, JavaScript, MySQL, Redis, Supabase
- API, endpoint, controller, migration, seeder, component
- UUID, foreign key, index, query, cache
- GitHub, npm, composer, Vite
- Any code snippets or technical syntax
- Version numbers of any software
- The word "backend" or "frontend"
- "Database" can be used once in the technical requirements section
  as "a database to store application records" — no more than that

---

*This task document is for AI agent use only —
ALALAY System, Municipality of General Mamerto Natividad, Nueva Ecija.*
