export const STATUS_LABELS = {
  submitted:             { label: 'Submitted',             severity: 'info' },
  returned_to_applicant: { label: 'Returned',              severity: 'danger' },
  mswdo_review:          { label: 'MSWDO Review',          severity: 'info' },
  social_case_study_uploaded: { label: 'Case Study Uploaded', severity: 'info' },
  assistance_coding:     { label: 'Assistance Coding',     severity: 'warn' },
  internal_audit_review: { label: 'Internal Audit Review', severity: 'warn' },
  returned_assistance_coding: { label: 'Returned for Coding', severity: 'danger' },
  voucher_creation:      { label: 'Voucher Creation',      severity: 'warn' },
  budget_checking:       { label: 'Budget Checking',       severity: 'warn' },
  voucher_on_hold:       { label: 'Voucher On Hold',       severity: 'danger' },
  voucher_recording:     { label: 'Voucher Recording',     severity: 'info' },
  with_treasurer:        { label: 'With Treasurer',        severity: 'info' },
  cheque_ready:          { label: 'Cheque Ready',          severity: 'success' },
  claimed:               { label: 'Claimed',               severity: 'success' },
}

export function getStatusLabel(status) {
  return STATUS_LABELS[status] ?? { label: status ?? 'Unknown', severity: 'contrast' }
}
