const LABELS = {
  admin: 'Admin',
  aics_staff: 'AICS',
  mswdo: 'MSWDO',
  accountant: 'Accountant',
  treasurer: 'Treasurer',
  internal_audit: 'Internal Audit',
  budget_officer: 'Budget Office',
}

export function roleLabel(role) {
  return LABELS[role] || role.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase())
}
