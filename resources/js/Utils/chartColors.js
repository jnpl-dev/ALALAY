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
        grid: {
          display: false,
        },
        ticks: {
          font: chartFont(),
          maxRotation: 45,
        },
      },
      y: {
        beginAtZero: true,
        grid: {
          color: 'rgba(0, 0, 0, 0.06)',
          drawBorder: false,
        },
        ticks: {
          font: chartFont(),
          padding: 8,
        },
      },
    },
    ...extra,
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
