export function roleSeverity(role) {
  return ({
    admin: 'danger',
    aics_staff: 'info',
    mswdo: 'success',
    accountant: 'warn',
    treasurer: 'contrast',
    internal_audit: 'info',
    budget_officer: 'warn',
  }[role] || 'info')
}

export function moduleSeverity(module) {
  return ({
    auth: 'info',
    users: 'info',
    admin: 'warn',
    aics: 'success',
    mswdo: 'success',
    accountant: 'warn',
    treasurer: 'contrast',
    'internal-audit': 'info',
    'budget-office': 'warn',
    applications: 'info',
  }[module] || 'info')
}

export function actionSeverity(action) {
  return ({
    login: 'success',
    logout: 'contrast',
    aup_accepted: 'info',
    approve: 'success',
    return: 'warn',
    'release-hold': 'info',
    acknowledge: 'info',
    claim: 'success',
    'store-assisted': 'success',
    store: 'success',
    update: 'info',
    destroy: 'danger',
    hold: 'warn',
    'toggle-status': 'warn',
    'revoke-sessions': 'danger',
    index: 'info',
    show: 'info',
    export: 'warn',
    verify: 'info',
    accept: 'success',
  }[action] || 'info')
}

export function statusSeverity(status) {
  return status === 'active' ? 'success' : 'danger'
}

export function smsStatusSeverity(status) {
  return ({
    sent: 'success',
    failed: 'danger',
    pending: 'warn',
  }[status] || 'info')
}

export function smsEventSeverity(event) {
  return ({
    submission_complete: 'success',
    application_under_review: 'info',
    resubmission_needed: 'warn',
    cheque_ready: 'info',
    cheque_claiming: 'warn',
    track_otp: 'info',
  }[event] || 'info')
}
