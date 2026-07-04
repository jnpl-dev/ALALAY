export function formatCurrency(value) {
  if (value == null || isNaN(value)) return '—'
  return 'PHP ' + Number(value).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })
}
