import { useConfirm as usePrimeConfirm } from 'primevue/useconfirm'

export function useConfirm() {
  const confirm = usePrimeConfirm()

  function require(options) {
    confirm.require(options)
  }

  function destroy(summary, message, acceptFn) {
    confirm.require({
      message,
      header: summary,
      icon: 'pi pi-exclamation-triangle',
      rejectProps: { label: 'Cancel', outlined: true },
      acceptProps: { label: 'Delete', severity: 'danger' },
      accept: acceptFn,
    })
  }

  function approve(summary, message, acceptFn) {
    confirm.require({
      message,
      header: summary,
      icon: 'pi pi-check-circle',
      rejectProps: { label: 'Cancel', outlined: true },
      acceptProps: { label: 'Confirm', severity: 'success' },
      accept: acceptFn,
    })
  }

  return { require, destroy, approve }
}
