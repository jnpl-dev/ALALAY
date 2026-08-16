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

useBreadcrumb([{ label: 'AICS' }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({ date_from: '', date_to: '' }) },
})

function applyFilter({ date_from, date_to }) {
  router.get(route('aics.analytics'), { date_from, date_to }, { preserveState: true })
}

function clearFilter() {
  router.get(route('aics.analytics'), {}, { preserveState: true })
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

const submissionTypePercent = computed(() => {
  const counts = (props.analyticsData?.submission_type ?? []).map(d => d.count)
  const total = counts.reduce((sum, n) => sum + n, 0)
  return total === 0 ? counts.map(() => 0) : counts.map(n => Math.round((n / total) * 100))
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

const horizontalBarOptions = baseChartOptions({
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
      ticks: { font: { family: 'Lato, sans-serif', size: 11 } },
    },
    y: {
      grid: { display: false },
      ticks: { font: { family: 'Lato, sans-serif', size: 11 } },
    },
  },
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
  <Head title="AICS Analytics" />

  <div class="card">
    <div class="flex items-center justify-between mb-6">
      <div class="font-semibold text-xl">Analytics</div>
    </div>
    <AppDateRangeFilter :date-from="filters.date_from" :date-to="filters.date_to" @apply="applyFilter" @clear="clearFilter" />
  </div>

  <Deferred data="analyticsData">
    <div class="flex flex-wrap gap-8 mt-8">
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Total Applications" :value="analyticsData?.total_applications ?? 0" icon="pi pi-file" color="primary" subtitle="in date range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Average per Day" :value="analyticsData?.average_per_day ?? 0" icon="pi pi-calendar" color="info" subtitle="in date range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Total Coded" :value="analyticsData?.total_coded ?? 0" icon="pi pi-qrcode" color="purple" subtitle="assistance codes" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Total Amount" :value="formatCurrency(analyticsData?.total_amount ?? 0)" icon="pi pi-money-bill" color="success" subtitle="in date range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Average Amount" :value="formatCurrency(analyticsData?.average_amount ?? 0)" icon="pi pi-calculator" color="warn" subtitle="per assistance code" />
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

      <div class="col-span-12 md:col-span-6 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Category Distribution</div>
          <Chart v-if="categoryData?.labels?.length" type="doughnut" :data="categoryData" :options="doughnutOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Online vs Walk-in</div>
          <div v-if="submissionTypeData?.labels?.length" class="flex flex-col gap-4 py-2">
            <div v-for="(item, index) in submissionTypeData.labels" :key="item" class="flex flex-col gap-1">
              <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-color">{{ item }}</span>
                <span class="text-muted-color">
                  {{ submissionTypeData.datasets[0].data[index] }} · {{ submissionTypePercent[index] }}%
                </span>
              </div>
              <div class="h-2 w-full rounded-full bg-surface-200 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :style="{ width: (submissionTypePercent[index] || 0) + '%', backgroundColor: submissionTypeData.datasets[0].backgroundColor[index] }"
                ></div>
              </div>
            </div>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Applications by Barangay</div>
          <Chart v-if="barangayData?.labels?.length" type="bar" :data="barangayData" :options="horizontalBarOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8 mt-8">
        <div v-for="i in 5" :key="i" class="col-span-12 lg:col-span-6 xl:col-span-3">
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
