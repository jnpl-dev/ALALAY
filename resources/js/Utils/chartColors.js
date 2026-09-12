export const CHART_COLORS = {
  primary: '#059669',
  primaryLight: '#34d399',
  accent: '#D4A843',
  success: '#059669',
  warning: '#D97706',
  danger: '#DC2626',
  purple: '#8B5CF6',
  muted: '#6B8B7A',

  medical: '#059669',
  hospital: '#34d399',
  burial: '#D4A843',

  pending: '#D97706',
  approved: '#059669',
  returned: '#DC2626',
  onHold: '#6B8B7A',
  processing: '#8B5CF6',

  primaryBg: 'rgba(5, 150, 105, 0.15)',
  accentBg: 'rgba(212, 168, 67, 0.15)',
  successBg: 'rgba(5, 150, 105, 0.15)',
  dangerBg: 'rgba(220, 38, 38, 0.15)',
}

export function chartFont() {
  return {
    family: 'Lato, sans-serif',
    size: 12,
    lineHeight: 1.4,
  }
}

export function baseChartOptions(extra = {}) {
  const { scales: extraScales, ...rest } = extra

  const baseX = {
    grid: { display: false },
    ticks: { font: chartFont(), maxRotation: 45, precision: 0 },
  }
  const baseY = {
    beginAtZero: true,
    grid: { color: 'rgba(0, 0, 0, 0.06)', drawBorder: false },
    ticks: { font: chartFont(), padding: 8, precision: 0 },
  }

  return {
    responsive: true,
    maintainAspectRatio: false,
    interaction: {
      intersect: false,
      mode: 'index',
    },
    plugins: {
      legend: {
        position: 'bottom',
        labels: {
          font: chartFont(),
          padding: 16,
          usePointStyle: true,
          pointStyle: 'circle',
        },
      },
      tooltip: {
        titleFont: chartFont(),
        bodyFont: chartFont(),
        padding: 10,
        cornerRadius: 6,
        titleMarginBottom: 6,
      },
    },
    scales: {
      x: {
        ...baseX,
        ...(extraScales?.x ?? {}),
        ticks: { ...baseX.ticks, ...(extraScales?.x?.ticks ?? {}) },
      },
      y: {
        ...baseY,
        ...(extraScales?.y ?? {}),
        ticks: { ...baseY.ticks, ...(extraScales?.y?.ticks ?? {}) },
      },
    },
    ...rest,
  }
}

export const categoryColors = [CHART_COLORS.primary, CHART_COLORS.primaryLight, CHART_COLORS.accent]
export const paletteColors = [
  CHART_COLORS.primary,
  '#34d399',
  '#6ee7b7',
  '#047857',
  CHART_COLORS.accent,
  '#10b981',
  '#065f46',
  '#a7f3d0',
]

export const ALL_CATEGORIES = ['Medical Assistance', 'Hospital Assistance', 'Burial Assistance']
export const ALL_CATEGORY_COLORS = [CHART_COLORS.primary, CHART_COLORS.primaryLight, CHART_COLORS.accent]

export const ALL_SUBMISSION_TYPES = [
  { key: 'online', label: 'Online', color: CHART_COLORS.primary },
  { key: 'walk-in', label: 'Walk-In', color: CHART_COLORS.muted },
]

export function getCategoryTagSeverity(categoryName) {
  const map = {
    'Medical Assistance': 'info',
    'Hospital Assistance': 'warn',
    'Burial Assistance': 'danger',
  }
  return map[categoryName] ?? 'secondary'
}

export function getTypeTagSeverity(submissionType) {
  return submissionType === 'online' ? 'success' : 'secondary'
}

export function getTypeLabel(submissionType) {
  return submissionType === 'online' ? 'Online' : 'Walk-In'
}

export const emptyChartPlugin = {
  id: 'emptyChart',
  afterDraw(chart) {
    const { ctx, data } = chart
    const hasData = data.datasets.some(ds => ds.data?.length > 0 && ds.data.some(v => v > 0))
    if (hasData) return
    const { left, top, right, bottom } = chart.chartArea
    ctx.save()
    ctx.textAlign = 'center'
    ctx.textBaseline = 'middle'
    ctx.fillStyle = '#9CA3AF'
    ctx.font = '14px Lato, sans-serif'
    ctx.fillText('No data available', (left + right) / 2, (top + bottom) / 2)
    ctx.restore()
  },
}
