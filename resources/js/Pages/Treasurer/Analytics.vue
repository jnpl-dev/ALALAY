<script setup>
import { computed } from 'vue'
import { Head, Deferred, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppDateRangeFilter from '@/Components/Common/AppDateRangeFilter.vue'
import { CHART_COLORS, baseChartOptions, paletteColors } from '@/Utils/chartColors'
import { fillMissingDates } from '@/Utils/chartDates'
import { formatCurrency } from '@/Utils/formatCurrency'
import Skeleton from 'primevue/skeleton'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Treasurer' }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({ date_from: '', date_to: '' }) },
})

function applyFilter({ date_from, date_to }) {
  router.get(route('treasurer.analytics'), { date_from, date_to }, { preserveState: true })
}

function clearFilter() {
  router.get(route('treasurer.analytics'), {}, { preserveState: true })
}

const statusLabels = { with_treasurer: 'Pending', cheque_ready: 'Ready', claimed: 'Claimed' }
const statusColors = {
  with_treasurer: CHART_COLORS.warning,
  cheque_ready: CHART_COLORS.success,
  claimed: CHART_COLORS.primary,
}

const trendData = computed(() => {
  const raw = props.analyticsData?.status_trend ?? []
  const { date_from, date_to } = props.filters
  const grouped = {}
  for (const item of raw) {
    if (!grouped[item.status]) grouped[item.status] = {}
    grouped[item.status][item.date] = Number(item.count)
  }
  const allDates = [...new Set(raw.map(d => d.date))].sort()
  const statuses = Object.keys(grouped)
  if (!allDates.length) return { labels: [], datasets: [] }
  return {
    labels: allDates,
    datasets: statuses.map(status => ({
      label: statusLabels[status] || status,
      data: allDates.map(d => grouped[status]?.[d] ?? 0),
      borderColor: statusColors[status] || CHART_COLORS.muted,
      backgroundColor: statusColors[status] || CHART_COLORS.muted,
      tension: 0.4,
      pointRadius: 3,
      pointHoverRadius: 5,
    })),
  }
})

const statusData = computed(() => {
  const data = props.analyticsData?.status_distribution ?? []
  return {
    labels: data.map(d => statusLabels[d.status] || d.status),
    datasets: [{
      data: data.map(d => d.count),
      backgroundColor: data.map(d => statusColors[d.status] || CHART_COLORS.muted),
      borderWidth: 2,
      borderColor: '#FFFFFF',
    }],
  }
})

const amountByCategoryData = computed(() => {
  const data = props.analyticsData?.amount_by_category ?? []
  return {
    labels: data.map(d => d.category_name),
    datasets: [{
      label: 'Total Amount',
      data: data.map(d => Number(d.total)),
      backgroundColor: CHART_COLORS.primaryLight,
      borderColor: CHART_COLORS.primaryLight,
      borderWidth: 1,
      borderRadius: 4,
    }],
  }
})

const amountByCategoryOptions = baseChartOptions({
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: {
        font: { family: 'Lato, sans-serif', size: 11 },
        callback: (v) => '₱' + Number(v).toLocaleString(),
      },
    },
    x: {
      grid: { display: false },
      ticks: { font: { family: 'Lato, sans-serif', size: 11 }, maxRotation: 45 },
    },
  },
})

const amountOverTimeData = computed(() => {
  const raw = props.analyticsData?.amount_over_time ?? []
  const filled = fillMissingDates(raw, props.filters.date_from, props.filters.date_to)
  return {
    labels: filled.map(d => d.date),
    datasets: [{
      label: 'Total Disbursed',
      data: filled.map(d => Number(d.total) || 0),
      borderColor: CHART_COLORS.primary,
      backgroundColor: CHART_COLORS.primaryBg,
      tension: 0.4,
      fill: true,
      pointRadius: 3,
      pointHoverRadius: 5,
      pointBackgroundColor: CHART_COLORS.primary,
    }],
  }
})

const doughnutOptions = baseChartOptions({
  cutout: '65%',
  plugins: {
    legend: {
      position: 'bottom',
      labels: {
        font: { family: 'Lato, sans-serif', size: 12 },
        padding: 16,
        usePointStyle: true,
        pointStyle: 'rectRounded',
      },
    },
  },
})
</script>

<template>
  <Head title="Treasurer Analytics" />

  <div class="card">
    <div class="flex items-center justify-between mb-6">
      <div class="font-semibold text-xl">Analytics</div>
    </div>
    <AppDateRangeFilter :date-from="filters.date_from" :date-to="filters.date_to" @apply="applyFilter" @clear="clearFilter" />
  </div>

  <Deferred data="analyticsData">
    <div class="flex flex-wrap gap-8 mt-8">
      <div class="flex-1 min-w-[160px]">
        <AppKpiCard title="Total Processed" :value="analyticsData?.total_processed ?? 0" icon="pi pi-file" color="primary" subtitle="in date range" />
      </div>
      <div class="flex-1 min-w-[160px]">
        <AppKpiCard title="Cheque Ready" :value="analyticsData?.total_cheque_ready ?? 0" icon="pi pi-check-circle" color="success" subtitle="ready for release" />
      </div>
      <div class="flex-1 min-w-[160px]">
        <AppKpiCard title="Claimed" :value="analyticsData?.total_claimed ?? 0" icon="pi pi-verified" color="purple" subtitle="released to beneficiary" />
      </div>
      <div class="flex-1 min-w-[160px]">
        <AppKpiCard title="Amount Disbursed" :value="formatCurrency(analyticsData?.total_amount_disbursed ?? 0)" icon="pi pi-money-bill" color="success" subtitle="total released" />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Status Trend</div>
          <Chart v-if="trendData?.labels?.length" type="line" :data="trendData" :options="baseChartOptions()" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Status Distribution</div>
          <Chart v-if="statusData?.labels?.length" type="doughnut" :data="statusData" :options="doughnutOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Amount by Category</div>
          <Chart v-if="amountByCategoryData?.labels?.length" type="bar" :data="amountByCategoryData" :options="amountByCategoryOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Disbursement Over Time</div>
          <Chart v-if="amountOverTimeData?.labels?.length" type="line" :data="amountOverTimeData" :options="baseChartOptions()" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8 mt-8">
        <div v-for="i in 5" :key="i" class="col-span-12 lg:col-span-6 xl:col-span-2">
          <div class="card">
            <div class="flex items-center gap-3">
              <Skeleton shape="circle" size="3rem" />
              <div class="flex-1 space-y-2">
                <Skeleton width="60%" height="1rem" />
                <Skeleton width="40%" height="0.75rem" />
              </div>
            </div>
          </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div class="col-span-12 md:col-span-6 xl:col-span-3">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div class="col-span-12 md:col-span-6 xl:col-span-3">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
