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

useBreadcrumb([{ label: 'Budget Office' }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({ date_from: '', date_to: '' }) },
})

function applyFilter({ date_from, date_to }) {
  router.get(route('budget-office.analytics'), { date_from, date_to }, { preserveState: true })
}

function clearFilter() {
  router.get(route('budget-office.analytics'), {}, { preserveState: true })
}

const decisionTrendData = computed(() => {
  const raw = props.analyticsData?.decision_trend ?? []
  const filled = fillMissingDates(raw, props.filters.date_from, props.filters.date_to)
  const labels = filled.map(d => d.date)
  const byDecision = (decision) => labels.map(date => raw.find(r => r.date === date && r.decision === decision)?.count ?? 0)
  const held = labels.map((date, i) => byDecision('hold')[i] + byDecision('on_hold')[i])
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
        label: 'Held',
        data: held,
        borderColor: CHART_COLORS.warning,
        backgroundColor: CHART_COLORS.accentBg,
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
    held: d.held ?? 0,
  }
})

const approveRate = computed(() => {
  const total = decisionCounts.value.approved + decisionCounts.value.held
  return total === 0 ? 0 : Math.round((decisionCounts.value.approved / total) * 100)
})

const decisionColors = { approved: CHART_COLORS.success, held: CHART_COLORS.warning }

const approveVsHoldBars = computed(() => [
  { label: 'Approved', count: decisionCounts.value.approved, pct: approveRate.value, color: decisionColors.approved },
  { label: 'Held', count: decisionCounts.value.held, pct: 100 - approveRate.value, color: decisionColors.held },
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
  <Head title="Budget Office Analytics" />

  <div class="card">
    <div class="flex items-center justify-between mb-6">
      <div class="font-semibold text-xl">Analytics</div>
    </div>
    <AppDateRangeFilter :date-from="filters.date_from" :date-to="filters.date_to" @apply="applyFilter" @clear="clearFilter" />
  </div>

  <Deferred data="analyticsData">
    <div class="flex flex-wrap gap-8 mt-8">
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Total Checked" :value="analyticsData?.total_checked ?? 0" icon="pi pi-check-square" color="primary" subtitle="budget checks in range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Avg Checked / Day" :value="analyticsData?.average_checked_per_day ?? 0" icon="pi pi-calendar" color="info" subtitle="in date range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Approved" :value="analyticsData?.approved ?? 0" icon="pi pi-check-circle" color="success" subtitle="forwarded to accountant" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Held" :value="analyticsData?.held ?? 0" icon="pi pi-pause-circle" color="danger" subtitle="vouchers put on hold" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Under-Review Amount" :value="formatCurrency(analyticsData?.under_review_amount ?? 0)" icon="pi pi-money-bill" color="success" subtitle="pending budget checks + holds" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Avg per Voucher" :value="formatCurrency(analyticsData?.average_per_voucher ?? 0)" icon="pi pi-calculator" color="warn" subtitle="per under-review voucher" />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Budget Decisions Over Time</div>
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
          <div class="font-semibold text-xl mb-4">Approve vs Hold</div>
          <div v-if="approveVsHoldBars.length" class="flex flex-col gap-5 py-2">
            <div v-for="item in approveVsHoldBars" :key="item.label" class="flex flex-col gap-1">
              <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-color">{{ item.label }}</span>
                <span class="text-muted-color">{{ item.count }} · {{ item.pct }}%</span>
              </div>
              <div class="h-2 w-full rounded-full bg-surface-200 overflow-hidden">
                <div class="h-full rounded-full transition-all duration-500" :style="{ width: item.pct + '%', backgroundColor: item.color }"></div>
              </div>
            </div>
            <div class="text-3xl font-bold mt-2" :style="{ color: CHART_COLORS.success }">{{ approveRate }}%</div>
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