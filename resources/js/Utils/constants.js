export const APPLICATION_STATUSES = [
  'submitted', 'returned_to_applicant', 'mswdo_review',
  'social_case_study_uploaded', 'assistance_coding', 'internal_audit_review',
  'returned_assistance_coding', 'voucher_creation', 'budget_checking',
  'voucher_on_hold', 'voucher_recording', 'with_treasurer',
  'cheque_ready', 'claimed',
] 

export const ROLES = [
  { value: 'admin', label: 'Admin' },
  { value: 'aics_staff', label: 'AICS Staff' },
  { value: 'mswdo', label: 'MSWDO' },
  { value: 'accountant', label: 'Accountant' },
  { value: 'treasurer', label: 'Treasurer' },
  { value: 'internal_audit', label: 'Internal Audit' },
  { value: 'budget_officer', label: 'Budget Office' },
]

export const USER_STATUSES = ['active', 'inactive']

export const SUBMISSION_TYPES = [
  { value: 'online', label: 'Online' },
  { value: 'walk_in', label: 'Walk-in' },
]
