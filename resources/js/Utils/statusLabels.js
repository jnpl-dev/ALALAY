export const STATUS_LABELS = {
  submitted:             { label: 'Submitted',             severity: 'info' },
  screening:             { label: 'Screening',             severity: 'warn' },
  returned_to_applicant: { label: 'Returned',              severity: 'danger' },
  mswdo_review:          { label: 'MSWDO Review',          severity: 'info' },
  social_case_study_uploaded: { label: 'Case Study Uploaded', severity: 'info' },
  assistance_coding:     { label: 'Assistance Coding',     severity: 'warn' },
  voucher_creation:      { label: 'Voucher Creation',      severity: 'warn' },
  voucher_checking:      { label: 'Voucher Checking',      severity: 'info' },
  voucher_returned:      { label: 'Voucher Returned',      severity: 'danger' },
  with_treasurer:        { label: 'With Treasurer',        severity: 'info' },
  budget_checking:       { label: 'Budget Checking',       severity: 'warn' },
  on_hold:               { label: 'On Hold',               severity: 'danger' },
  cheque_ready:          { label: 'Cheque Ready',          severity: 'success' },
  claimed:               { label: 'Claimed',               severity: 'success' },
}

export function getStatusLabel(status) {
  return STATUS_LABELS[status] ?? { label: status ?? 'Unknown', severity: 'contrast' }
}
