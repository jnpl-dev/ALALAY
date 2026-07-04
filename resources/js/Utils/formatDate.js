import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(utc)
dayjs.extend(timezone)
dayjs.extend(relativeTime)

const TZ = 'Asia/Manila'

export function formatDate(date, format = 'MMM D, YYYY') {
  if (!date) return '—'
  return dayjs(date).tz(TZ).format(format)
}

export function formatDateTime(date, format = 'MMM D, YYYY h:mm A') {
  if (!date) return '—'
  return dayjs(date).tz(TZ).format(format)
}

export function formatRelative(date) {
  if (!date) return '—'
  return dayjs(date).tz(TZ).fromNow()
}

export function formatDateShort(date) {
  return formatDate(date, 'MMM D')
}

export function formatDateFull(date) {
  return formatDate(date, 'MMMM D, YYYY')
}

export function now() {
  return dayjs().tz(TZ)
}
