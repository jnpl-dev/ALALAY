<script setup>
import { computed } from 'vue'
import { Head, Deferred, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppDateRangeFilter from '@/Components/Common/AppDateRangeFilter.vue'
import { CHART_COLORS, baseChartOptions } from '@/Utils/chartColors'
import { fillMissingDates } from '@/Utils/chartDates'
import { formatCurrency } from '@/Utils/formatCurrency'
import Skeleton from 'primevue/skeleton'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Internal Audit' }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({ date_from: '', date_to: '' }) },
})

function applyFilter({ date_from, date_to }) {
  router.get(route('internal-audit.analytics'), { date_from, date_to }, { preserveState: true })
}

function clearFilter() {
  router.get(route('internal-audit.analytics'), {}, { preserveState: true })
}

const decisionTrendData = computed(() => {
  const raw = props.analyticsData?.decision_trend ?? []
  const filled = fillMissingDates(raw, props.filters.date_from, props.filters.date_to)
  const labels = filled.map(d => d.date)
  const byDecision = (decision) => labels.map(date => raw.find(r => r.date === date && r.decision === decision)?.count ?? 0)
  return {
    labels,
    datasets: [
      {
        label: 'Approved',
        data: byDecision('approved'),
        borderColor: CHART_COLORS.success,
        backgroundColor: CHART_COLORS.primaryBg,
        tension: 0.4,
        pointRadius: 3,
        pointHoverRadius: 5,
      },
      {
        label: 'Returned',
        data: byDecision('returned'),
        borderColor: CHART_COLORS.danger,
        backgroundColor: CHART_COLORS.dangerBg,
        tension: 0.4,
        pointRadius: 3,
        pointHoverRadius: 5,
      },
    ],
  }
})

const categoryColors = [CHART_COLORS.primary, CHART_COLORS.primaryLight, CHART_COLORS.accent]

const categoryData = computed(() => {
  const data = props.analyticsData?.category_distribution ?? []
  return {
    labels: data.map(d => d.category_name),
    datasets: [{
      label: 'Reviews',
      data: data.map(d => d.count),
      backgroundColor: categoryColors.slice(0, data.length || 1),
      borderWidth: 1,
      borderRadius: 4,
    }],
  }
})

const verticalBarOptions = baseChartOptions({
  plugins: {
    legend: { display: false },
  },
})

const decisionCounts = computed(() => {
  const d = props.analyticsData ?? {}
  return {
    approved: d.approved ?? 0,
    returned: d.returned ?? 0,
  }
})

const approvalRate = computed(() => {
  const total = decisionCounts.value.approved + decisionCounts.value.returned
  return total === 0 ? 0 : Math.round((decisionCounts.value.approved / total) * 100)
})

const decisionColors = { approved: CHART_COLORS.success, returned: CHART_COLORS.danger }

const approveVsReturnBars = computed(() => [
  { label: 'Approved', count: decisionCounts.value.approved, pct: approvalRate.value, color: decisionColors.approved },
  { label: 'Returned', count: decisionCounts.value.returned, pct: 100 - approvalRate.value, color: decisionColors.returned },
])

const amountByCategoryData = computed(() => {
  const data = props.analyticsData?.amount_by_category ?? []
  return {
    labels: data.map(d => d.category_name).reverse(),
    datasets: [{
      label: 'Approved Amount',
      data: data.map(d => Number(d.total)).reverse(),
      backgroundColor: CHART_COLORS.primaryLight,
      borderColor: CHART_COLORS.primaryLight,
      borderWidth: 1,
      borderRadius: 4,
    }],
  }
})

const horizontalAmountOptions = baseChartOptions({
  indexAxis: 'y',
  interaction: {
    intersect: false,
    mode: 'y',
  },
  plugins: {
    legend: { display: false },
  },
  scales: {
    x: {
      beginAtZero: true,
      grid: { color: 'rgba(0, 0, 0, 0.06)', drawBorder: false },
      ticks: {
        font: { family: 'Lato, sans-serif', size: 11 },
        callback: (v) => '₱' + Number(v).toLocaleString(),
      },
    },
    y: {
      grid: { display: false },
      ticks: { font: { family: 'Lato, sans-serif', size: 11 } },
    },
  },
})
</script>

<template>
  <Head title="Internal Audit Analytics" />

  <div class="card">
    <div class="flex items-center justify-between mb-6">
      <div class="font-semibold text-xl">Analytics</div>
    </div>
    <AppDateRangeFilter :date-from="filters.date_from" :date-to="filters.date_to" @apply="applyFilter" @clear="clearFilter" />
  </div>

  <Deferred data="analyticsData">
    <div class="flex flex-wrap gap-8 mt-8">
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Total Reviewed" :value="analyticsData?.total_reviewed ?? 0" icon="pi pi-check-square" color="primary" subtitle="coding reviews in range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Avg Reviewed / Day" :value="analyticsData?.average_reviewed_per_day ?? 0" icon="pi pi-calendar" color="info" subtitle="in date range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Approved" :value="analyticsData?.approved ?? 0" icon="pi pi-check-circle" color="success" subtitle="moved to voucher creation" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Returned" :value="analyticsData?.returned ?? 0" icon="pi pi-undo" color="danger" subtitle="sent back to AICS" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Coded Amount" :value="formatCurrency(analyticsData?.coded_amount ?? 0)" icon="pi pi-money-bill" color="success" subtitle="approved in range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Avg Coded Amount" :value="formatCurrency(analyticsData?.average_coded_amount ?? 0)" icon="pi pi-calculator" color="warn" subtitle="per approved code" />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Coding Decisions Over Time</div>
          <Chart v-if="decisionTrendData?.labels?.length" type="line" :data="decisionTrendData" :options="baseChartOptions()" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Category Distribution</div>
          <Chart v-if="categoryData?.labels?.length" type="bar" :data="categoryData" :options="verticalBarOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Approval vs Return</div>
          <div v-if="approveVsReturnBars.length" class="flex flex-col gap-5 py-2">
            <div v-for="item in approveVsReturnBars" :key="item.label" class="flex flex-col gap-1">
              <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-color">{{ item.label }}</span>
                <span class="text-muted-color">{{ item.count }} · {{ item.pct }}%</span>
              </div>
              <div class="h-2 w-full rounded-full bg-surface-200 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" :style="{ width: item.pct + '%', backgroundColor: item.color }"></div>
              </div>
            </div>
            <div class="text-3xl font-bold mt-2" :style="{ color: CHART_COLORS.success }">{{ approvalRate }}%</div>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Approved Amount by Category</div>
          <Chart v-if="amountByCategoryData?.labels?.length" type="bar" :data="amountByCategoryData" :options="horizontalAmountOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8 mt-8">
        <div v-for="i in 6" :key="i" class="col-span-12 lg:col-span-6 xl:col-span-3">
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
        <div class="col-span-12">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div v-for="i in 3" :key="i" class="col-span-12 md:col-span-6 xl:col-span-4">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>