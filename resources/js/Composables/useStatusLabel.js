import { getStatusLabel } from '@/Utils/statusLabels'

export function useStatusLabel() {
  function label(status) {
    return getStatusLabel(status)
  }

  function severity(status) {
    return getStatusLabel(status).severity
  }

  return { label, severity }
}
