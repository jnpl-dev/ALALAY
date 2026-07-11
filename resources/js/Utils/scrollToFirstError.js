export function scrollToFirstError() {
  const el = document.querySelector('[data-invalid="true"], .p-invalid, [aria-invalid="true"], .is-invalid')
  if (el) {
    el.scrollIntoView({ behavior: 'smooth', block: 'center' })
    if (typeof el.focus === 'function') el.focus()
  }
}
