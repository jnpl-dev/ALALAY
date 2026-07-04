export const APPLICATION_STATUSES = [
  'submitted', 'screening', 'returned_to_applicant', 'mswdo_review',
  'social_case_study_uploaded', 'assistance_coding', 'voucher_creation',
  'voucher_checking', 'voucher_returned', 'with_treasurer',
  'budget_checking', 'on_hold', 'cheque_ready', 'claimed',
] 

export const ROLES = [
  { value: 'admin', label: 'Admin' },
  { value: 'aics_staff', label: 'AICS Staff' },
  { value: 'mswdo', label: 'MSWDO' },
  { value: 'accountant', label: 'Accountant' },
  { value: 'treasurer', label: 'Treasurer' },
  { value: 'mayors_office', label: "Mayor's Office" },
]

export const USER_STATUSES = ['active', 'inactive']

export const SUBMISSION_TYPES = [
  { value: 'online', label: 'Online' },
  { value: 'walk_in', label: 'Walk-in' },
]
