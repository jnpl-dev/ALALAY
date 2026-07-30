# ALALAY
## A Digital AICS Management and Notification System with Hybrid Submission

### System Presentation Document

**Municipality of General Mamerto Natividad**
**Nueva Ecija, Philippines**

Prepared for: The Municipal Government of General Mamerto Natividad
Date: July 27, 2026

---

## Table of Contents

1. Executive Summary
2. Background and Problem Statement
3. What is ALALAY?
4. Key Features and Benefits
5. The Application Workflow
6. User Roles and Responsibilities
7. Assistance Categories and Requirements
8. SMS Notification System
9. Document Scanner
10. Dashboards and Reports
11. Data Privacy and Security
12. Technical Requirements
13. Implementation and Onboarding Plan
14. Support and Maintenance
15. Frequently Asked Questions
16. Closing

---

## Section 1 — Executive Summary

**ALALAY** is a web-based system designed for the Municipality of General Mamerto Natividad to manage the AICS (Assistance to Individuals in Crisis Situation) program — from application submission all the way through to cheque claiming.

The system solves a critical problem: today, residents seeking financial assistance must travel to the municipal office, submit paper documents, and physically return multiple times just to check on their application status. Staff, meanwhile, manage paperwork routed by hand between offices with no central view of where any application stands.

ALALAY replaces this entirely paper-based process with a digital workflow accessible from any device with a browser. Residents can apply online from home or submit in person at the office. They receive SMS text messages automatically at each stage — submission confirmed, under review, resubmission needed, or cheque ready for claiming — using a simple reference code to track their application anytime.

For the municipal government, ALALAY means faster processing, automated notifications that reduce office walk-ins, and real-time dashboards that give the Mayor and department heads data on program performance without waiting for manually compiled reports. Each office — AICS, MSWDO, Accountant, Treasurer, and the Mayor's Office — sees only the applications and actions relevant to their role, keeping the process clear and secure.

ALALAY was built with genuine care for the residents of General Mamerto Natividad who turn to their local government in times of crisis. It respects the existing process and the people running it, while removing the friction that makes a difficult time even harder.

---

## Section 2 — Background and Problem Statement

The AICS program of General Mamerto Natividad provides financial assistance to residents facing medical, hospital, and burial crises. It is an essential social service, but the process of delivering that assistance has, until now, been entirely manual.

### The Current Process

A resident in need must travel to the municipal hall, often from distant barangays, to submit paper documents to the AICS office. From there, the paper application is physically carried from one office to the next — from AICS to MSWDO for review, to the Accountant for voucher checking, to the Treasurer for approval, and finally to the Mayor's Office for information. At each handoff, the paper file sits in a physical tray until someone picks it up.

### Specific Pain Points

**For Residents:**
- Residents must travel to the municipal office just to submit an application, even if they only need to know whether their documents were accepted
- There is no way to check application status without physically returning to the office or calling
- If documents are incomplete or need resubmission, there is no automated notification — the resident only finds out when they return to check
- Every visit to the municipal office means lost income from missed work and transportation costs

**For Staff:**
- There is no centralized view of the application pipeline — each office only sees the paper in front of them
- Compiling monthly or quarterly reports for the Mayor requires manually counting paper files
- Identifying processing bottlenecks (e.g., which stage takes the longest) is impossible without time-consuming manual analysis
- Physical documents can be misplaced or misfiled
- There is no audit trail of who handled an application, when, and what decision was made

**For the Mayor's Office:**
- No real-time visibility into how many residents are being served, how much assistance has been disbursed, or which barangays have the highest need
- Manual report preparation is slow and prone to error
- Data-driven decisions about program funding and resource allocation are difficult without timely information

### The Challenge ALALAY Was Built to Address

The municipality needed a system that would keep its existing process — which staff already know and trust — while removing the friction of paper routing, enabling residents to apply and track their applications without repeated trips to the office, and giving the Mayor and department heads the data they need to manage the program effectively. ALALAY was designed from the ground up to meet these needs while keeping the process simple and familiar to everyone involved.

---

## Section 3 — What is ALALAY?

ALALAY is a website. It can be accessed from any device with a browser — a desktop computer at the office, a laptop, a tablet, or a smartphone. There is no app to download and no software to install. Anyone with an internet connection can use it.

### Two Types of Users

**The Public (Applicants):** Residents of General Mamerto Natividad who need financial assistance can use ALALAY to apply for AICS from their own device at home, or they can come to the municipal office and submit in person with the help of AICS Staff. Either way, the process is the same — and every applicant receives a unique reference code to track their application.

**Internal Staff:** Six municipal roles use ALALAY to process applications — AICS Staff, MSWDO, Accountant, Treasurer, Mayor's Office Staff, and the System Administrator. Each role sees only the information and actions relevant to their work.

### What "Hybrid Submission" Means

ALALAY accepts applications both ways:
- **Online:** The applicant fills out the form and captures documents using their phone camera at home, then submits electronically
- **Walk-in:** The applicant comes to the municipal office with physical documents, and the AICS Staff encodes the information and scans the documents into the system

Both paths follow the same workflow once the application is in the system. Both issue a reference code to the applicant.

### What the Name "ALALAY" Means

In Filipino, "alay" means offering or dedicating something in service. ALALAY reflects the system's purpose — it is an offering of service to the residents of General Mamerto Natividad, designed to make a difficult moment in their lives just a little bit easier.

---

## Section 4 — Key Features and Benefits

### 4.1 Online and Walk-in Application Support (Hybrid Submission)

Residents can apply from anywhere — their home, a relative's smartphone, or the municipal office. Online applicants fill out forms and capture documents using their phone camera. Walk-in applicants are assisted by AICS Staff who encode their information and scan their documents. Both methods produce the same result: a complete application with a unique reference code.

*Benefit: Residents no longer need to travel to the office just to submit an application. Walk-in applicants still receive the same level of service and get a reference code just like online applicants.*

### 4.2 Reference Code Tracking

Every application receives a unique reference code (e.g., GMN-2026-A3X7R9). The applicant can use this code on the ALALAY website to check their application status at any time — no login, no password, no phone call. The system shows a visual timeline of exactly where the application is in the process.

*Benefit: Residents check their status anytime from any device. No repeated trips to the municipal office just to ask "kamusta na po yung application ko?"*

### 4.3 Camera-Based Document Scanning

Instead of uploading files from their device, applicants use their phone camera to scan physical documents directly. The system automatically enhances the image — whitens the background, sharpens the text, removes shadows — producing a clean PDF that looks like it came from a document scanner app. Government IDs require both front and back scans; hospital bills can capture multiple pages.

*Benefit: No need for a separate scanner app or scanning device. The phone camera does everything. The result is a professional PDF suitable for official records.*

### 4.4 Automatic SMS Notifications

Applicants automatically receive text messages at five critical stages of their application:

| When | What the Applicant Receives |
|---|---|
| Application submitted | Your AICS application {code} has been received. We will notify you once it is reviewed. Track: {link} |
| AICS Staff or MSWDO approves | Good day! Your AICS application {code} is now under review by our office. We will update you on the next steps. |
| Application returned for resubmission | Your application {code} needs resubmission. Reason: {reason}. Please resubmit via {link}. |
| Cheque ready for claiming | Your AICS cheque is ready for claiming at the MSWDO office. Ref: {code}. Please bring a valid ID. |
| Scheduled claiming date | Your AICS cheque is scheduled for claiming on {date}. Please visit the MSWDO office on the said date. Ref: {code}. |

*Benefit: Applicants are informed at every step without needing to call or visit the office. Staff do not need to manually send notifications — the system handles them automatically.*

### 4.5 Step-by-Step Digital Workflow

Each stage of the AICS process is digitized and tracked. Applications move through the pipeline automatically: Applicant submits → AICS screens → MSWDO reviews → Assistance is coded → Voucher is created → Accountant checks → Treasurer approves → Cheque is ready. Each role only sees and acts on what is relevant to them. Paper is no longer routed between offices.

*Benefit: Staff know exactly what needs their attention. Nothing gets lost in transit between offices.*

### 4.6 Real-Time Status Tracking for Applicants

The Track page lets an applicant enter their reference code and see a visual timeline of their application's journey — exactly like tracking a package delivery. Each completed step is marked, and the current step is highlighted. If the application was returned, the reason and which documents need resubmission are shown clearly.

*Benefit: Residents know exactly where their application stands at all times. No uncertainty, no guessing.*

### 4.7 Role-Based Staff Panels

Each municipal role has a dedicated panel that shows only what is relevant to them:
- **AICS Staff** sees applications to screen and applications to code
- **MSWDO** sees applications to review and vouchers to create
- **Accountant** sees vouchers to check
- **Treasurer** sees vouchers to acknowledge and cheques to prepare
- **Mayor's Office** sees consolidated reports and dashboards — no workflow actions

*Benefit: Each staff member has a clear view of their tasks. No irrelevant information clutters their screen. No one accidentally accesses something they should not.*

### 4.8 Dashboards and Reports

Every role has a dashboard showing key performance indicators relevant to their work. The AICS Staff dashboard shows pending applications and screening volume for the day. The MSWDO dashboard shows applications needing review and vouchers to create. The Treasurer's dashboard shows cheques pending acknowledgment.

The Mayor's Office dashboard is the most comprehensive — it shows total applications, approved amounts, disbursed assistance, barangay-level breakdowns, application trends over time, and a pipeline analysis that reveals which stage takes the longest. All data can be filtered by date range for custom reports.

*Benefit: The Mayor and department heads get real-time program data without waiting for manually compiled reports. Bottlenecks are visible immediately.*

### 4.9 Secure Document Storage

All scanned documents — supporting documents, social case studies, and vouchers — are stored securely on the server's local storage. Documents are never publicly accessible. Only authorized staff can view them, and only through temporary links that expire after a set time.

*Benefit: Residents' personal documents are protected. Staff can view documents without downloading or printing them.*

### 4.10 Data Privacy Compliance

ALALAY is designed to comply with the Data Privacy Act of 2012 (Republic Act 10173) and the National Privacy Commission's Circular 2023-06. Personal information is encrypted in storage. Access is logged for every action. Only authorized staff can view applicant data. The system enforces a "need to know" policy — each role sees only the data required for their work.

*Benefit: The municipality meets its legal obligations under Philippine data privacy law. Residents can trust that their information is handled responsibly.*

---

## Section 5 — The Application Workflow

The complete lifecycle of an AICS application in ALALAY follows eight stages. Below is a step-by-step description of what happens at each stage, who acts, and what the applicant is notified about.

### Workflow Overview

```
APPLICANT SUBMITS (Online or In-Person)
         ↓
  [Reference Code Issued] → SMS: Submission Complete
         ↓
  STAGE 1: AICS Staff Screening
    ├── RETURNED → SMS: Resubmission Needed → Applicant Resubmits → Stage 1
    └── APPROVED → SMS: Application Under Review
                     ↓
  STAGE 2: MSWDO Review
    ├── RETURNED → SMS: Resubmission Needed → Applicant Resubmits → Stage 2
    └── VALID → Social Case Study Captured
                     ↓
  STAGE 3: AICS Staff — Assistance Coding
         ↓
  STAGE 4: MSWDO — Voucher Creation
         ↓
  STAGE 5: Accountant — Voucher Checking
    ├── RETURNED → MSWDO Re-creates Voucher (Stage 4)
    └── APPROVED
                     ↓
  STAGE 6: Treasurer — Voucher Acknowledgment
    ├── ON HOLD (pending budget)
    └── CHEQUE READY → SMS: Cheque Claiming Notice
                     ↓
  STAGE 7: Applicant Claims Cheque at MSWDO Office
```

### Stage 0 — Application Submission

**Who acts:** The applicant (online) or AICS Staff (on behalf of a walk-in applicant)

**What happens:**
- The applicant selects the type of assistance needed (Medical, Hospital, or Burial)
- Claimant and beneficiary information is filled in
- Supporting documents are captured using the phone camera
- The application is submitted

**What happens next:** The system generates a unique reference code and sends an SMS to the applicant confirming submission. The application appears in the AICS Staff's pending queue.

**SMS sent:** "Your AICS application {code} has been received. We will notify you once it is reviewed."

---

### Stage 1 — AICS Staff Screening

**Who acts:** AICS Staff

**What happens:** The AICS Staff reviews the application, supporting documents, and verifies that all required documents are present and valid.

**Decision options:**
- **Approve** — the application moves to MSWDO Review (Stage 2). An SMS is sent to the applicant.
- **Return** — the application is returned to the applicant for document resubmission. The AICS Staff selects which documents need correction and provides remarks.

**SMS sent (on approve):** "Good day! Your AICS application {code} is now under review."
**SMS sent (on return):** "Your application {code} needs resubmission. Reason: {reason}."

**Resubmission path:** The applicant uses their reference code to access the Track page, rescans the flagged documents, and resubmits. The application re-enters Stage 1.

---

### Stage 2 — MSWDO Review

**Who acts:** MSWDO

**What happens:** The MSWDO reviews the application and supporting documents. If valid, the MSWDO conducts a Social Case Study — a professional assessment of the applicant's situation — and captures it into the system using the document scanner.

**Decision options:**
- **Approve** — the Social Case Study is captured, and the application moves to Assistance Coding (Stage 3). An SMS is sent.
- **Return** — the application is returned to the applicant for resubmission, with specific documents flagged.

**SMS sent (on approve):** "Good day! Your AICS application {code} is now under review."
**SMS sent (on return):** "Your application {code} needs resubmission. Reason: {reason}."

**Resubmission path:** Same as Stage 1 — the applicant rescans flagged documents via the Track page. The application re-enters Stage 2.

---

### Stage 3 — Assistance Coding

**Who acts:** AICS Staff

**What happens:** The AICS Staff reviews the Social Case Study and assigns an Assistance Code — the specific type and amount of financial assistance approved for this case. The amount is based on standard code references maintained by the Admin.

**What happens next:** The application with its assigned assistance code moves to Voucher Creation (Stage 4).

**No SMS is sent at this stage** — this is an internal processing step.

---

### Stage 4 — Voucher Creation

**Who acts:** MSWDO

**What happens:** The MSWDO creates a voucher document based on the assigned assistance code. The physical voucher is scanned into the system using the document scanner.

**What happens next:** The voucher is forwarded to the Accountant for checking (Stage 5).

**No SMS is sent at this stage.**

---

### Stage 5 — Accountant Voucher Checking

**Who acts:** Accountant

**What happens:** The Accountant reviews the voucher for accuracy and validity.

**Decision options:**
- **Approve** — the voucher is forwarded to the Treasurer (Stage 6)
- **Return** — the voucher is sent back to MSWDO for re-creation. The voucher version number increments

**No SMS is sent at this stage.**

---

### Stage 6 — Treasurer Voucher Acknowledgment

**Who acts:** Treasurer

**What happens:** The Treasurer reviews the approved voucher and makes the final determination.

**Decision options:**
- **Mark as Cheque Ready** — the application is approved for claiming. An SMS is sent to the applicant
- **Place On Hold** — the application is placed on hold, typically when budget is temporarily unavailable. It can be re-evaluated when funds are available

**SMS sent (on cheque ready):** "Your AICS cheque is ready for claiming at the MSWDO office. Ref: {code}. Please bring a valid ID."

---

### Stage 7 — Applicant Claims Cheque

**Who acts:** Applicant (in person at the MSWDO office)

**What happens:** The applicant visits the MSWDO office to claim their cheque. The Treasurer marks the application as "Claimed" in the system.

**Status:** The application lifecycle is complete.

---

## Section 6 — User Roles and Responsibilities

### 6.1 Applicant (Public — No Staff Account)

**Who this is:** Any resident of General Mamerto Natividad seeking financial assistance through the AICS program.

**What they can do:**
- Browse available assistance categories and understand what documents are required
- Submit an AICS application online using their own device
- Visit the municipal office to submit a walk-in application (encoded by AICS Staff)
- Track their application status anytime using their reference code
- Resubmit documents if their application is returned
- Receive SMS notifications at each critical stage

**What they cannot do:**
- Access anyone else's application
- Modify a submitted application (only resubmit when returned)
- View internal staff actions or data

---

### 6.2 AICS Staff

**Who this typically is:** The AICS officer or staff member at the municipal hall who receives applications from walk-in residents and performs initial screening.

**What they can do:**
- View newly submitted applications requiring screening
- Review applicant information and supporting documents
- Approve applications that meet requirements, forwarding them to MSWDO
- Return applications with specific instructions on what to correct
- Encode walk-in applications on behalf of residents
- Assign assistance codes after MSWDO approval and Social Case Study

**What they cannot do:**
- Create or modify vouchers
- Approve or return vouchers
- View financial data beyond what they assign
- Access user management or system settings
- View the Mayor's Office dashboard

**Typical daily workflow:**
1. Check the dashboard for pending applications
2. Review each application and its supporting documents
3. Approve complete and valid applications; return incomplete ones with clear instructions
4. For applications returned after MSWDO review: review the Social Case Study and assign the appropriate assistance code

---

### 6.3 MSWDO (Municipal Social Welfare and Development Office)

**Who this typically is:** The MSWDO staff or social worker responsible for reviewing applications and conducting social case studies.

**What they can do:**
- View applications forwarded by AICS Staff
- Review applicant information and supporting documents
- Approve applications and capture the Social Case Study document
- Return applications for resubmission with specific instructions
- Create vouchers based on assigned assistance codes
- View their created vouchers

**What they cannot do:**
- Assign assistance codes (AICS Staff role)
- Approve or return vouchers (Accountant and Treasurer roles)
- Manage users or system settings
- View the Mayor's Office dashboard

**Typical daily workflow:**
1. Check the dashboard for applications needing review
2. Review each application's documents
3. For valid applications: conduct and capture the Social Case Study
4. For applications that have been coded: create the voucher document
5. If Accountant returns a voucher for re-creation: make corrections and resubmit

---

### 6.4 Accountant

**Who this typically is:** The Municipal Accountant or designated accounting staff.

**What they can do:**
- View vouchers forwarded by MSWDO
- Review voucher details and supporting documents
- Approve vouchers that pass verification, forwarding them to the Treasurer
- Return vouchers for re-creation if corrections are needed
- View financial data including assistance amounts

**What they cannot do:**
- Modify application information
- Create or modify assistance codes
- Manage users or system settings
- View the Mayor's Office dashboard

**Typical daily workflow:**
1. Check the dashboard for vouchers pending review
2. Verify each voucher for accuracy and completeness
3. Approve correct vouchers; return incorrect ones with remarks

---

### 6.5 Treasurer

**Who this typically is:** The Municipal Treasurer or designated treasury staff.

**What they can do:**
- View vouchers approved by the Accountant
- Acknowledge vouchers and mark them as cheque ready
- Place applications on hold when budget is unavailable
- Re-evaluate held applications when budget becomes available
- Mark applications as claimed when the applicant receives their cheque

**What they cannot do:**
- Modify application information or assistance codes
- Create or modify vouchers
- Manage users or system settings

**Typical daily workflow:**
1. Check the dashboard for vouchers requiring acknowledgment
2. Review each voucher and verify budget availability
3. Mark as cheque ready or place on hold as appropriate
4. Process cheque claiming when applicants visit the office

---

### 6.6 Mayor's Office Staff

**Who this typically is:** Staff designated by the Mayor to monitor the AICS program.

**What they can do:**
- View consolidated dashboards and analytics
- See all applications, their current status, and amounts
- Generate date-filtered reports
- Export data for further analysis
- View barangay-level breakdowns and trend data

**What they cannot do:**
- Approve, return, or modify any application
- Create or modify vouchers
- Manage users or system settings
- Access individual applicant's personal information directly

**Typical daily workflow:**
1. Check the executive dashboard for a daily snapshot
2. Review trend reports and barangay-level data
3. Generate reports for the Mayor or for reporting purposes

---

### 6.7 System Administrator (Admin)

**Who this typically is:** The municipal IT officer or designated administrator.

**What they can do:**
- Create, edit, activate, and deactivate staff accounts
- Assign roles to staff members
- Revoke all sessions of any user (e.g., when a device is lost or a staff member resigns)
- View the complete audit log of all system actions
- Configure system settings (system name, colors, SMS templates)
- Manage assistance categories, required documents, and assistance code references

**What they cannot do:**
- Approve, return, or act on any application
- Create or modify vouchers
- Access applicant personal information through workflow

**Typical daily workflow:**
1. Check the Admin dashboard for system health and unusual activity
2. Manage user accounts (add new staff, deactivate departing staff)
3. Configure system settings as needed
4. Monitor the audit log for security purposes

---

## Section 7 — Assistance Categories and Requirements

ALALAY supports three categories of assistance under the AICS program. Each category has specific document requirements based on the municipality's actual guidelines.

### 7.1 Medical Assistance

**What it covers:** Financial assistance for outpatient medical expenses including consultations, medicines, and laboratory fees.

**Who qualifies:** Residents of General Mamerto Natividad who need financial help with medical treatment that does not require hospital admission.

**Required documents (all mandatory):**

| Document | Capture Method | Notes |
|---|---|---|
| Medical Certificate | Single scan (A4) | Issued by a licensed physician |
| Prescription | Single scan (A4) | Issued by a licensed physician |
| Applicant's Government ID | Front and back (card — rotate phone) | Any valid government-issued ID |
| Beneficiary's Government ID | Front and back (card — rotate phone) | Any valid government-issued ID |
| Applicant's Cedula | Single scan (half-sheet — rotate phone) | Community tax certificate |
| Barangay Indigency | Single scan (A4) | Issued by the barangay |

**Conditional document:**
- **Authorization Letter** — Required only when the claimant is not a direct relative of the beneficiary (spouse, parent, child, sibling, or grandparent). Single scan (A4).

---

### 7.2 Hospital Assistance

**What it covers:** Financial assistance for inpatient hospital expenses including hospital bills, medicines, and medical procedures.

**Who qualifies:** Residents of General Mamerto Natividad who need financial help with hospital confinement costs.

**Required documents (all mandatory):**

| Document | Capture Method | Notes |
|---|---|---|
| Hospital Bill | Multiple pages (A4) | May capture several pages for long bills |
| Prescription | Single scan (A4) | Issued by a licensed physician |
| Medical Certificate/Abstract | Single scan (A4) | Issued by the attending physician |
| Applicant's Government ID | Front and back (card — rotate phone) | Any valid government-issued ID |
| Beneficiary's Government ID | Front and back (card — rotate phone) | Any valid government-issued ID |
| Applicant's Cedula | Single scan (half-sheet — rotate phone) | Community tax certificate |
| Barangay Indigency | Single scan (A4) | Issued by the barangay |

**Conditional document:**
- **Authorization Letter** — Required only when the claimant is not a direct relative.

---

### 7.3 Burial Assistance

**What it covers:** Financial assistance for burial and funeral expenses of indigent residents.

**Who qualifies:** Families of deceased residents of General Mamerto Natividad who need financial help with burial costs.

**Required documents (all mandatory):**

| Document | Capture Method | Notes |
|---|---|---|
| Certified Copy of Birth Certificate | Single scan (A4) | PSA or local civil registry copy |
| Applicant's Government ID | Front and back (card — rotate phone) | Any valid government-issued ID |
| Applicant's Cedula | Single scan (half-sheet — rotate phone) | Community tax certificate |
| Beneficiary's Barangay Residency | Single scan (A4) | Confirms deceased was a GMN resident |
| Barangay Indigency | Single scan (A4) | Issued on behalf of the bereaved family |

**Conditional document:**
- **Authorization Letter** — Required only when the claimant is not a direct relative of the deceased.

---

### Summary of Requirements per Category

| Category | Mandatory Documents | Conditional Document |
|---|---|---|
| Medical Assistance | 6 | 1 (Authorization Letter) |
| Hospital Assistance | 7 | 1 (Authorization Letter) |
| Burial Assistance | 5 | 1 (Authorization Letter) |

The Authorization Letter is conditionally required because many applicants are direct relatives of the beneficiary and do not need it. The system intelligently requires it only when the claimant's relationship to the beneficiary is not a direct relative.

---

## Section 8 — SMS Notification System

ALALAY automatically sends SMS messages to applicants at critical stages of their application. No staff action is required to trigger these messages — the system sends them automatically when an application reaches a specific stage.

### How It Works

- Messages are sent to the phone number provided by the applicant during application
- Each message includes the application's reference code for easy identification
- Messages are sent through a Philippine SMS service provider
- There is no cost to the applicant — the municipality covers the messaging cost
- Every SMS sent is logged for accountability and troubleshooting

### Notification Triggers and Message Content

| When the Notification is Sent | Message the Applicant Receives |
|---|---|
| **Application Submitted** — Immediately after a successful application submission | "Your AICS application {reference_code} has been received. We will notify you once it is reviewed. Track: {track_url}" |
| **Application Under Review** — When AICS Staff or MSWDO approves the application and it moves to the next stage | "Good day! Your AICS application {reference_code} is now under review by our office. We will update you on the next steps." |
| **Resubmission Needed** — When AICS Staff or MSWDO returns the application for document correction | "Your application {reference_code} needs resubmission. Reason: {remarks}. Please resubmit via {track_url}." |
| **Cheque Ready for Claiming** — When the Treasurer marks the application as approved and the cheque is ready | "Your AICS cheque is ready for claiming at the MSWDO office. Ref: {reference_code}. Please bring a valid ID." |
| **Scheduled Claiming Date** — When Admin triggers a mass claiming notification with a specific date | "Your AICS cheque is scheduled for claiming on {claiming_date}. Please visit the MSWDO office on the said date. Ref: {reference_code}." |

### What the Placeholder Codes Mean

- `{reference_code}` — The unique code assigned to the application (e.g., GMN-2026-A3X7R9)
- `{track_url}` — A direct link to the Track page with the reference code pre-filled
- `{remarks}` — The specific reason the application was returned (only in the resubmission message)
- `{claiming_date}` — The date scheduled for cheque claiming

### Features of the SMS System

- **Editable Templates:** The Admin can edit the text of each SMS template through the system settings — no programming needed to change the message content
- **Full Logging:** Every SMS sent is recorded in the system with the recipient number, message content, date and time, and delivery status
- **Queue-Based Sending:** SMS messages are sent in the background so staff never have to wait for a message to be dispatched before continuing their work
- **Mass Claiming Notification:** The Admin can send claiming notifications to all applicants with cheque-ready applications at once, specifying a scheduled claiming date

---

## Section 9 — Document Scanner

ALALAY uses a camera-based document scanner instead of traditional file uploads for capturing supporting documents. This means applicants use their phone camera to scan physical documents directly — no separate scanner app needed.

### Why Scanning Instead of Uploading

- **Security:** Freshly captured documents are more likely to be genuine than files selected from storage
- **Simplicity:** Most applicants have a phone camera. Few know how to use a scanner or scan app
- **Quality:** The system automatically enhances the image to produce clean, readable documents

### How It Works Step by Step

**Step 1 — Align the Document:** The camera screen shows a guide frame shaped like the document being scanned. The applicant aligns the document within this frame.

**Step 2 — Capture:** The applicant taps the capture button. The photo is taken instantly.

**Step 3 — Automatic Enhancement:** The system processes the image in the phone's browser:
- The image is adjusted to a standard size
- Colors are converted to black and white
- Contrast is enhanced — the paper background becomes white, text becomes sharp black
- Shadows and uneven lighting are removed
- The result looks like a professional document scanner output

**Step 4 — Preview and Confirm:** The applicant sees the enhanced image. They can either confirm ("Use This") or retake the photo ("Retake").

**Step 5 — PDF Generation:** Once confirmed, the captured image is automatically placed into a standard A4 portrait PDF — the same format used by government offices.

### Special Scanning Cases

**Government IDs (Front and Back):** When scanning a government ID, the system first captures the front side. Once confirmed, it automatically opens the camera again for the back side. Both sides are combined into a single two-page PDF.

**Hospital Bills (Multiple Pages):** For documents with multiple pages (e.g., a hospital bill), the applicant can scan one page, confirm it, then tap "Add Another Page" to continue scanning. When all pages are done, they tap "Done" and all pages are combined into one multi-page PDF.

**Cedula and ID Cards:** For smaller documents like the Cedula (community tax certificate), the system prompts the applicant to rotate their phone sideways for a better fit within the guide frame.

### What the Result Looks Like

Every captured document is saved as a standard A4 portrait PDF with:
- White background
- Clear, readable text
- Professional formatting suitable for official records
- File size typically between 150KB and 350KB per page

### If the Camera Is Not Available

If the applicant's device does not have a camera or if camera permission is denied, the system falls back to a simple file upload option where the applicant can select images from their device. These images are also converted to PDF format for consistency.

### Staff Use of the Scanner

MSWDO staff also use the document scanner to capture:
- **Social Case Study documents** — the printed assessment is scanned into the system
- **Voucher documents** — the physical voucher is scanned and attached to the application

---

## Section 10 — Dashboards and Reports

Every role in ALALAY has a dedicated dashboard showing key performance indicators (KPIs) and charts relevant to their work. Analytics pages provide deeper, date-filtered reporting for trend analysis.

### 10.1 AICS Staff Dashboard

**Purpose:** Monitor the daily screening workload.

**What they see:**
- Number of pending applications requiring screening
- Applications screened today
- Applications pending assistance coding
- Applications coded today
- Weekly trend of application submissions
- Breakdown by assistance category
- Online vs. walk-in submission ratio
- Applications by barangay

**Analytics:** Date-filtered view of total applications, average per day, coding volume, and total assistance amount assigned.

---

### 10.2 MSWDO Dashboard

**Purpose:** Monitor review and voucher creation workload.

**What they see:**
- Number of applications pending review
- Applications approved today
- Vouchers pending creation
- Vouchers created today
- Weekly application trends
- Category and barangay distribution

**Analytics:** Date-filtered view of total and pending applications, approval rates, and voucher creation volume.

---

### 10.3 Accountant Dashboard

**Purpose:** Monitor voucher checking workload.

**What they see:**
- Number of vouchers pending review
- Vouchers approved today
- Vouchers returned today
- Weekly voucher trend
- Voucher status distribution (pending vs. approved vs. returned)
- Category distribution of vouchers

**Analytics:** Date-filtered view of total vouchers, approval and return rates, total assistance amount, and average amount per voucher.

---

### 10.4 Treasurer Dashboard

**Purpose:** Monitor cheque preparation and acknowledgment workload.

**What they see:**
- Number of vouchers pending acknowledgment
- Cheques marked ready today
- Applications on hold
- Weekly cheque status trends
- Distribution of applications by status
- Total assistance amount by category

**Analytics:** Date-filtered view of total processed applications, cheque-ready count, hold count, claimed count, and total amount disbursed.

---

### 10.5 Mayor's Office Dashboard and Reports

**Purpose:** Executive-level consolidated view of the entire AICS program.

This is the most data-rich dashboard in ALALAY, designed to give the Mayor and department heads full visibility into the program without needing to request manual reports.

**KPIs — Today's Snapshot:**
- Applications received today
- Applications approved today
- Cheques marked ready today
- Applications claimed today

**KPIs — Monthly Totals:**
- Total applications this month
- Total approved this month
- Total amount disbursed this month
- Total applications currently on hold

**Charts and Visualizations:**
- **Daily application volume** (bar chart) — last 7 days of activity at a glance
- **Pipeline status distribution** (doughnut chart) — see exactly where applications are piling up across all stages (intake, screening, processing, review, ready, on hold, claimed)
- **Category breakdown** (doughnut chart) — Medical vs. Hospital vs. Burial distribution for the current month, showing what type of crisis the community is experiencing
- **Applications by Barangay** (horizontal bar chart) — top 10 barangays by number of applicants, showing which areas need the most assistance
- **Assistance amount by category** (bar chart) — total financial assistance per category

**Analytics (Date-Filtered Reports):**

The analytics page allows generating reports for any custom date range. Key metrics include:
- Total applications received
- Total approved
- Total claimed
- Total on hold
- Total assistance amount disbursed
- Average assistance amount per application
- Approval rate (percentage)
- Average processing time (days from submission to cheque ready)

**Analytics Charts:**
- Application trend over time (line chart)
- Category distribution (doughnut)
- Online vs. walk-in submission breakdown (doughnut)
- Applications by barangay (horizontal bar)
- Monthly disbursement trend (bar chart)
- **Pipeline bottleneck analysis** (horizontal bar) — shows the average number of days applications spend at each stage, revealing exactly where the process is slowest

*Benefit: The Mayor can see, at a glance, how many residents are being served, how much assistance has been disbursed, which barangays need the most support, and where the process has bottlenecks — all without requesting a manual report.*

---

## Section 11 — Data Privacy and Security

ALALAY is designed to comply with the Data Privacy Act of 2012 (Republic Act 10173) and the National Privacy Commission's Circular 2023-06 on the Security of Personal Data. This section explains what this means for applicants and for the municipality.

### 11.1 Data Privacy Act Compliance

ALALAY processes personal information of applicants and beneficiaries — names, addresses, contact details, birth dates, and scanned documents. Under the Data Privacy Act, the Municipality of General Mamerto Natividad is the Personal Information Controller, meaning it is responsible for how this data is collected, stored, used, and disposed of.

The system has been designed with privacy safeguards built in from the start, not added as an afterthought.

### 11.2 What Personal Information is Collected

Only the information needed for AICS processing is collected:
- Claimant name, address, date of birth, phone number, email (optional), and relationship to the beneficiary
- Beneficiary name, address, and date of birth
- Scanned supporting documents (medical certificates, IDs, etc.)
- Social case study assessment (conducted by MSWDO)

No unnecessary information is collected — no SSS numbers, TIN numbers, or other identifiers unrelated to the AICS process.

### 11.3 How Information is Protected

**Encryption at Rest:** Sensitive fields — phone numbers, email addresses, and physical addresses — are encrypted in the database. Even if someone gained direct access to the database, they could not read this information.

**Role-Based Access:** Staff only see what they need for their work. AICS Staff cannot access financial data. The Accountant cannot modify application information. The Mayor's Office sees only aggregated data and cannot view individual personal details. The Admin has no access to the application workflow at all.

**Multi-Factor Authentication:** All staff accounts require a second verification step beyond their password — a 6-digit code sent to their registered email — before they can access the system. This prevents unauthorized access even if a password is compromised.

**Session Security:** The Admin can instantly force-logout any user if a device is lost or a staff member leaves. All sessions are tracked with IP address and device information.

**Audit Trail:** Every action taken by every staff member — every approval, return, view, export, and login — is recorded with a timestamp, the user's identity, and their IP address. This provides full accountability for the processing of every application.

**Secure Document Storage:** All scanned documents are stored on the server's local storage. Documents are never made publicly accessible. When a staff member views a document, a temporary link is generated that expires after a set time.

**Export Controls:** Every time data is exported (e.g., CSV download), the action is logged. Raw personal data exports are restricted to authorized roles only.

### 11.4 The Municipality's Responsibilities

While ALALAY provides the technical privacy safeguards, some requirements must be fulfilled by the municipality itself:

1. **Data Protection Officer (DPO):** The municipality must formally designate a DPO and register them with the National Privacy Commission
2. **NPC Registration:** ALALAY must be registered with the NPC as a data processing system
3. **Privacy Impact Assessment:** A documented assessment must be completed before going live
4. **Acceptable Use Policy:** Staff must read and acknowledge the rules for using the system before they can access it
5. **Data Retention Policy:** The municipality must define how long application records are kept, in line with COA and DSWD requirements
6. **Breach Management Plan:** A documented procedure for responding to any data breach

### 11.5 Audit Trail

Every action in the system is logged:

| What is Logged | Details Recorded |
|---|---|
| Staff login/logout | User, timestamp, IP address, device information |
| Application approval | Who approved, when, which application, remarks |
| Application return | Who returned, reason, which documents flagged |
| Voucher creation/approval/return | Who acted, when, outcome |
| Data export | Who exported, what data, when |
| User management | Admin who created/edited/deactivated user |
| System setting changes | Admin who changed what, when |

This log is append-only — no one can delete or modify past entries. It provides a complete, tamper-evident record of all system activity for auditing and accountability purposes.

---

## Section 12 — Technical Requirements

### For Applicants (Public)

- A smartphone, tablet, or computer with a web browser (Chrome, Firefox, Safari, or Edge)
- An internet connection
- A working camera on the device (for scanning documents)
- A Philippine mobile number (for SMS notifications)

*No app download, no software installation, no account registration required.*

### For Staff

- A desktop computer or laptop with a modern web browser (Chrome, Firefox, Edge — any recent browser works)
- An internet connection at the municipal office
- A valid email address (for login and multi-factor authentication)

*No special software to install. No VPN required. Staff access the system through a web browser just like visiting any website.*

### For the System to Run

- A dedicated desktop computer at the municipal office configured as the server
- An internet connection at the server location
- A .gov.ph domain name (requested from the Department of Information and Communications Technology — DICT)
- An SSL certificate for secure, encrypted connections (available for free through Let's Encrypt)

### What the Municipality Provides

- Server hardware (a desktop computer dedicated to running the system)
- Internet connection
- Staff computers or devices
- Coordination with DICT for the .gov.ph domain

### Technical Architecture (Brief Summary)

ALALAY runs on a standard web server with a database. It uses a modern architecture where the application and its user interface are part of the same system, which simplifies deployment and maintenance. Files are stored securely on the server's local storage, with a future option to migrate to a dedicated storage setup as needs grow. SMS messages are sent through a Philippine SMS provider. The system is designed to handle the municipality's current and anticipated volume of AICS applications.

---

## Section 13 — Implementation and Onboarding Plan

The following is a proposed timeline from system setup to full go-live. Each phase builds on the previous one.

### Phase 1 — System Setup (Week 1–2)

| Activity | Description |
|---|---|
| Server setup | Prepare the server (cloud or on-premise) and install necessary software |
| Domain configuration | Set up the .gov.ph domain and SSL certificate |
| System installation | Install and configure ALALAY on the server |
| Initial configuration | Set up system name, colors, and other branding |
| Admin account creation | Create the initial Administrator account |

**Deliverable:** A working ALALAY installation accessible via the web.

---

### Phase 2 — Data Setup and Testing (Week 2–3)

| Activity | Description |
|---|---|
| Staff accounts created | The Admin creates accounts for all staff who will use the system |
| Assistance categories verified | Confirm Medical, Hospital, and Burial categories and their required documents are correct |
| Test applications submitted | Submit several test applications through the system |
| Workflow tested end-to-end | Test the complete pipeline from submission to claiming |
| SMS notifications verified | Confirm SMS messages are being sent correctly at each trigger point |
| Document scanner tested | Test the camera-based scanning on various devices |

**Deliverable:** A fully configured system with working test data.

---

### Phase 3 — Staff Training (Week 3–4)

| Role | Training Duration | Focus |
|---|---|---|
| AICS Staff | Half day | Screening applications, encoding walk-ins, assistance coding |
| MSWDO | Half day | Reviewing applications, capturing social case studies, creating vouchers |
| Accountant | Half day | Voucher checking, approval, and return process |
| Treasurer | Half day | Voucher acknowledgment, cheque management, hold/re-evaluate |
| Mayor's Office Staff | 2 hours | Dashboard navigation, report generation, data interpretation |
| Admin/IT Officer | Full day | User management, system settings, SMS template editing, audit log review |

All training sessions include hands-on practice with the system. Printed user guides are provided for each role.

**Deliverable:** All staff confident in using ALALAY for their daily work.

---

### Phase 4 — Parallel Run (Week 4–6)

- The system runs alongside the existing manual process
- Both digital and paper records are maintained temporarily
- Staff process applications in both systems to verify accuracy
- Any issues, discrepancies, or missing features are identified and resolved
- Feedback from staff is collected and incorporated

**Deliverable:** A validated system that staff trust to replace the manual process.

---

### Phase 5 — Go-Live (Week 6+)

- ALALAY becomes the primary system for AICS processing
- The paper process is phased out
- New applications are accepted only through ALALAY (online or walk-in encoded by staff)
- Support is available for the first month to address any issues

**Deliverable:** A fully operational digital AICS management system serving the municipality.

---

## Section 14 — Support and Maintenance

### Ongoing Support

- **Technical Contact:** The development team is available for technical issues and questions
- **Response Time:** Critical issues (system down, data loss) are addressed within 24 hours. General questions and minor issues are addressed within 2 business days
- **Bug Reports and Feature Requests:** Issues can be reported through the designated contact channel. Feature requests are evaluated for inclusion in future updates

### System Maintenance

- **Automated Daily Backups:** The system automatically backs up all data every day. Backups are encrypted and stored securely
- **Software Updates:** Updates and security patches are applied as needed to keep the system stable and secure
- **Normal Operation:** The system is designed for continuous operation. No scheduled downtime is expected during normal business hours

### Municipality Responsibilities After Go-Live

| Responsibility | Description |
|---|---|
| Server maintenance | Keep the server running (if on-premise) and ensure it has adequate power and cooling |
| Internet connectivity | Maintain a reliable internet connection at the municipal office |
| Staff account management | Add new staff accounts and deactivate accounts of departing staff through the Admin panel |
| System monitoring | Monitor the system through the Admin dashboard for any issues |
| NPC compliance | File required reports with the National Privacy Commission as a registered data processing system |
| Data retention | Follow the municipality's data retention policy for record keeping |
| Staff training | Train new staff members on using the system |

---

## Section 15 — Frequently Asked Questions

### 1. What happens if an applicant does not have a smartphone?

Walk-in applicants can visit the municipal office and submit their application in person. The AICS Staff will encode their information and scan their physical documents into the system using the office device. The applicant still receives a reference code for tracking and SMS notifications on their mobile phone.

### 2. What if the internet goes down at the office?

Staff should document applications manually during an internet outage and encode them into the system once the connection is restored. The system is designed to accept applications with timestamps, so there is no data loss. For online applicants, they can submit when the internet is available.

### 3. Can the system handle multiple AICS Staff members?

Yes. ALALAY supports any number of staff accounts for each role. Multiple AICS Staff members can work simultaneously — each sees the same queue of pending applications. When one staff member takes action on an application, it is immediately removed from others' views.

### 4. How does the system prevent duplicate applications?

The system checks whether a beneficiary with the same full name already has an active application. If an application is already in progress for that beneficiary (from submitted to cheque ready), a new application cannot be submitted. After an application is claimed, a three-month cooldown period applies before a new application can be submitted for the same beneficiary.

### 5. Where is the data stored?

Application data and scanned documents are stored on a dedicated desktop computer at the municipal office that serves as the system's server. The municipality has full physical control over the hardware and the data it contains. In the future, the system can be configured to use a separate storage setup if needed.

### 6. What happens to the data if we stop using the system?

The municipality retains full ownership of all data. The data can be exported from the system at any time, including all application records, documents, and audit logs. The development team can assist with data migration if needed.

### 7. Can we add new assistance categories later?

Yes. The Admin can add, edit, or deactivate assistance categories through the system settings panel. New categories can have their own set of required documents, capture types, and scanner presets. No programming is needed.

### 8. Who can see the applicant's personal information?

Only authorized staff with a legitimate "need to know" can see applicant information:
- AICS Staff sees what they need for screening
- MSWDO sees what they need for review and case studies
- Accountant sees only voucher-related information
- Treasurer sees only voucher and amount information
- Mayor's Office sees only aggregated statistics (not individual personal details)
- Admin has no access to the application workflow

All access is logged for accountability.

### 9. What if a staff member leaves the municipality?

The Admin can deactivate that staff member's account instantly. All their sessions can be revoked, logging them out of every device immediately. The audit log preserves a permanent record of all actions they took while employed.

### 10. Can the Mayor see specific applicants' personal information?

No. The Mayor's Office role has view-only access to aggregated data and reports. They can see statistics, trends, totals, and barangay breakdowns but cannot view individual applicant names, addresses, phone numbers, or personal documents. This protects applicant privacy while still providing the Mayor with the data needed for program oversight.

### 11. Is the system accessible to persons with disabilities?

The system is built using standard web technologies that are compatible with screen readers and other assistive technologies. The public website is designed for simplicity and ease of use, with clear labels, large touch targets for mobile users, and straightforward navigation.

### 12. How long does it take to process an application from submission to claiming?

Processing time varies depending on the completeness of the application and the current workload of each office. The system gives the Mayor's Office visibility into the average processing time and can identify which stage takes the longest, helping the municipality make targeted improvements.

---

## Closing

ALALAY was built with a single purpose: to make the AICS program of General Mamerto Natividad more accessible, more efficient, and more dignified for the residents who need it most.

For the resident facing a medical crisis, ALALAY means not having to choose between traveling to the municipal office and seeking treatment. For the family arranging a burial, it means one less burden during an already difficult time. For the MSWDO staff managing dozens of cases, it means a clear view of what needs their attention. For the Mayor, it means the data needed to make informed decisions about how the municipality serves its most vulnerable residents.

The system does not replace the compassion and dedication of the people who run the AICS program. It removes the friction that gets in their way.

We remain committed to:
- Protecting the privacy and personal data of every resident who uses ALALAY
- Continuously improving the system based on feedback from staff and applicants
- Supporting the municipality in its mission to serve the people of General Mamerto Natividad

**Development Team Contact:**
(Contact information to be provided)

---

*ALALAY System — Municipality of General Mamerto Natividad, Nueva Ecija*
*"Maalalahanin na serbisyo, para sa bawat Mamaleña."*
