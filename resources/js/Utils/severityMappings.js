export function roleSeverity(role) {
  return ({
    admin: 'danger',
    aics_staff: 'info',
    mswdo: 'success',
    accountant: 'warn',
    treasurer: 'contrast',
    mayors_office: 'info',
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
    mayors_office: 'info',
    applications: 'info',
  }[module] || 'info')
}

export function actionSeverity(action) {
  return ({
    login: 'success',
    logout: 'contrast',
    aup_accepted: 'info',
    store: 'success',
    update: 'info',
    destroy: 'danger',
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
