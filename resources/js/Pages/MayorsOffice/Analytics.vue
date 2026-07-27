<script setup>
import { computed } from 'vue'
import { Head, Deferred, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppDateRangeFilter from '@/Components/Common/AppDateRangeFilter.vue'
import { CHART_COLORS, baseChartOptions, categoryColors } from '@/Utils/chartColors'
import { fillMissingDates } from '@/Utils/chartDates'
import { formatCurrency } from '@/Utils/formatCurrency'
import Skeleton from 'primevue/skeleton'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: "Mayor's Office" }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({ date_from: '', date_to: '' }) },
})

function applyFilter({ date_from, date_to }) {
  router.get(route('mayors-office.analytics'), { date_from, date_to }, { preserveState: true })
}

function clearFilter() {
  router.get(route('mayors-office.analytics'), {}, { preserveState: true })
}

const trendData = computed(() => {
  const raw = props.analyticsData?.trend ?? []
  const filled = fillMissingDates(raw, props.filters.date_from, props.filters.date_to)
  return {
    labels: filled.map(d => d.date),
    datasets: [{
      label: 'Applications',
      data: filled.map(d => d.count),
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

const categoryData = computed(() => {
  const data = props.analyticsData?.category_distribution ?? []
  return {
    labels: data.map(d => d.category_name),
    datasets: [{
      data: data.map(d => d.count),
      backgroundColor: categoryColors.slice(0, data.length || 1),
      borderWidth: 2,
      borderColor: '#FFFFFF',
    }],
  }
})

const submissionTypeData = computed(() => {
  const data = props.analyticsData?.submission_type ?? []
  return {
    labels: data.map(d => d.submission_type === 'online' ? 'Online' : 'Walk-in'),
    datasets: [{
      data: data.map(d => d.count),
      backgroundColor: data.map(d => d.submission_type === 'online' ? CHART_COLORS.primary : CHART_COLORS.muted),
      borderWidth: 2,
      borderColor: '#FFFFFF',
    }],
  }
})

const barangayData = computed(() => {
  const data = props.analyticsData?.barangay_distribution ?? []
  return {
    labels: data.map(d => d.barangay).reverse(),
    datasets: [{
      label: 'Applications',
      data: data.map(d => d.count).reverse(),
      backgroundColor: CHART_COLORS.primaryLight,
      borderColor: CHART_COLORS.primaryLight,
      borderWidth: 1,
      borderRadius: 4,
    }],
  }
})

const monthlyDisbursementData = computed(() => {
  const data = props.analyticsData?.monthly_disbursement ?? []
  return {
    labels: data.map(d => d.month),
    datasets: [{
      label: 'Disbursed',
      data: data.map(d => Number(d.total)),
      backgroundColor: CHART_COLORS.primary,
      borderRadius: 4,
    }],
  }
})

const bottleneckData = computed(() => {
  const data = props.analyticsData?.pipeline_bottleneck ?? []
  const colors = [
    CHART_COLORS.muted, CHART_COLORS.warning, CHART_COLORS.accent,
    CHART_COLORS.primary, CHART_COLORS.purple, CHART_COLORS.success,
    CHART_COLORS.primary, CHART_COLORS.danger, CHART_COLORS.muted,
  ]
  return {
    labels: data.map(d => d.stage).reverse(),
    datasets: [{
      label: 'Applications',
      data: data.map(d => d.count).reverse(),
      backgroundColor: colors.slice(0, data.length).reverse(),
      borderWidth: 1,
      borderRadius: 4,
    }],
  }
})

const bottleneckOptions = baseChartOptions({
  indexAxis: 'y',
  interaction: { intersect: false, mode: 'y' },
  plugins: { legend: { display: false } },
  scales: {
    x: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.06)', drawBorder: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, stepSize: 1 } },
    y: { grid: { display: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 } } },
  },
})

const monthlyOptions = baseChartOptions({
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: true, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, callback: (v) => '₱' + Number(v).toLocaleString() } },
    x: { grid: { display: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, maxRotation: 45 } },
  },
})

const horizontalBarOptions = baseChartOptions({
  indexAxis: 'y',
  interaction: { intersect: false, mode: 'y' },
  plugins: { legend: { display: false } },
  scales: {
    x: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.06)', drawBorder: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, stepSize: 1 } },
    y: { grid: { display: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 } } },
  },
})

const doughnutOptions = baseChartOptions({
  cutout: '65%',
  plugins: {
    legend: { position: 'bottom', labels: { font: { family: 'Lato, sans-serif', size: 12 }, padding: 16, usePointStyle: true, pointStyle: 'rectRounded' } },
  },
})
</script>

<template>
  <Head title="Mayor's Office Analytics" />

  <div class="card">
    <div class="flex items-center justify-between mb-6">
      <div class="font-semibold text-xl">Analytics</div>
    </div>
    <AppDateRangeFilter :date-from="filters.date_from" :date-to="filters.date_to" @apply="applyFilter" @clear="clearFilter" />
  </div>

  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Total Applications" :value="analyticsData?.total_applications ?? 0" icon="pi pi-file" color="primary" subtitle="in date range" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Approved" :value="analyticsData?.total_approved ?? 0" icon="pi pi-check-circle" color="success" subtitle="in pipeline" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Claimed" :value="analyticsData?.total_claimed ?? 0" icon="pi pi-verified" color="purple" subtitle="released to beneficiary" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="On Hold" :value="analyticsData?.total_on_hold ?? 0" icon="pi pi-pause-circle" color="danger" subtitle="currently on hold" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Disbursed" :value="formatCurrency(analyticsData?.total_disbursed ?? 0)" icon="pi pi-money-bill" color="success" subtitle="total released" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Avg Amount" :value="formatCurrency(analyticsData?.average_amount ?? 0)" icon="pi pi-calculator" color="info" subtitle="per claimed application" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Approval Rate" :value="(analyticsData?.approval_rate ?? 0) + '%'" icon="pi pi-percentage" color="primary" subtitle="of total applications" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Avg Days to Ready" :value="analyticsData?.avg_days_to_cheque_ready ?? 0" icon="pi pi-calendar-clock" color="warn" subtitle="from submission to cheque" />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Application Trend</div>
          <Chart v-if="trendData?.labels?.length" type="line" :data="trendData" :options="baseChartOptions()" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Category Distribution</div>
          <Chart v-if="categoryData?.labels?.length" type="doughnut" :data="categoryData" :options="doughnutOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Online vs Walk-in</div>
          <Chart v-if="submissionTypeData?.labels?.length" type="doughnut" :data="submissionTypeData" :options="doughnutOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Applications by Barangay</div>
          <Chart v-if="barangayData?.labels?.length" type="bar" :data="barangayData" :options="horizontalBarOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Monthly Disbursement</div>
          <Chart v-if="monthlyDisbursementData?.labels?.length" type="bar" :data="monthlyDisbursementData" :options="monthlyOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Pipeline Bottleneck</div>
          <Chart v-if="bottleneckData?.labels?.length" type="bar" :data="bottleneckData" :options="bottleneckOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8 mt-8">
        <div v-for="i in 8" :key="i" class="col-span-12 lg:col-span-6 xl:col-span-3">
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
        <div class="col-span-12 md:col-span-4">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div class="col-span-12 md:col-span-4">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div class="col-span-12 md:col-span-4">
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
