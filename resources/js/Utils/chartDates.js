export function generateWeekLabels() {
  const labels = []
  for (let i = 6; i >= 0; i--) {
    const d = new Date()
    d.setDate(d.getDate() - i)
    labels.push(d.toLocaleDateString('en-PH', { weekday: 'short', month: 'short', day: 'numeric' }))
  }
  return labels
}

export function fillMissingDates(data, dateFrom, dateTo) {
  const map = {}
  for (const row of data) {
    const key = typeof row.date === 'string' ? row.date.slice(0, 10) : row.date
    map[key] = row
  }
  const result = []
  const current = new Date(dateFrom)
  const end = new Date(dateTo)
  while (current <= end) {
    const key = current.toISOString().slice(0, 10)
    result.push(map[key] ?? { date: key, count: 0 })
    current.setDate(current.getDate() + 1)
  }
  return result
}
