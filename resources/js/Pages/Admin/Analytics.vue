<script setup>
import { computed } from 'vue'
import { Head, Deferred, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppDateRangeFilter from '@/Components/Common/AppDateRangeFilter.vue'
import { CHART_COLORS, baseChartOptions } from '@/Utils/chartColors'
import { fillMissingDates } from '@/Utils/chartDates'
import { formatCurrency } from '@/Utils/formatCurrency'
import { getStatusLabel } from '@/Utils/statusLabels'
import { roleLabel } from '@/Utils/roleLabel'
import Skeleton from 'primevue/skeleton'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Admin' }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({ date_from: '', date_to: '' }) },
})

function applyFilter({ date_from, date_to }) {
  router.get(route('admin.analytics'), { date_from, date_to }, { preserveState: true })
}

function clearFilter() {
  router.get(route('admin.analytics'), {}, { preserveState: true })
}

const trendData = computed(() => {
  const raw = props.analyticsData?.application_trend ?? []
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

const statusData = computed(() => {
  const data = props.analyticsData?.application_status_distribution ?? []
  return {
    labels: data.map(d => getStatusLabel(d.status).label).reverse(),
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

const userRegData = computed(() => {
  const raw = props.analyticsData?.user_registration_trend ?? []
  const filled = fillMissingDates(raw, props.filters.date_from, props.filters.date_to)
  return {
    labels: filled.map(d => d.date),
    datasets: [{
      label: 'New Users',
      data: filled.map(d => d.count),
      borderColor: CHART_COLORS.success,
      backgroundColor: CHART_COLORS.successBg,
      tension: 0.4,
      fill: true,
      pointRadius: 3,
      pointHoverRadius: 5,
      pointBackgroundColor: CHART_COLORS.success,
    }],
  }
})

const roleData = computed(() => {
  const data = props.analyticsData?.users_by_role ?? []
  return {
    labels: data.map(d => roleLabel(d.role)).reverse(),
    datasets: [{
      label: 'Users',
      data: data.map(d => d.count).reverse(),
      backgroundColor: CHART_COLORS.success,
      borderColor: CHART_COLORS.success,
      borderWidth: 1,
      borderRadius: 4,
    }],
  }
})

const disbursementData = computed(() => {
  const data = props.analyticsData?.monthly_disbursement_trend ?? []
  return {
    labels: data.map(d => d.month),
    datasets: [{
      label: 'Disbursed',
      data: data.map(d => d.total),
      backgroundColor: CHART_COLORS.accentBg,
      borderColor: CHART_COLORS.accent,
      borderWidth: 1,
      borderRadius: 4,
      barPercentage: 0.6,
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
</script>

<template>
  <Head title="Admin Analytics" />

  <div class="card">
    <div class="flex items-center justify-between mb-6">
      <div class="font-semibold text-xl">Analytics</div>
    </div>
    <AppDateRangeFilter :date-from="filters.date_from" :date-to="filters.date_to" @apply="applyFilter" @clear="clearFilter" />
  </div>

  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Users Registered" :value="analyticsData?.total_users_registered ?? 0" icon="pi pi-users" color="info" subtitle="in date range" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Applications" :value="analyticsData?.total_applications ?? 0" icon="pi pi-file" color="primary" subtitle="in date range" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Vouchers" :value="analyticsData?.total_vouchers ?? 0" icon="pi pi-verified" color="purple" subtitle="in date range" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Total Disbursed" :value="formatCurrency(analyticsData?.total_disbursed ?? 0)" icon="pi pi-money-bill" color="success" subtitle="claimed applications" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Application Trend</div>
          <Chart v-if="trendData?.labels?.length" type="line" :data="trendData" :options="baseChartOptions()" class="h-80" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Applications by Status</div>
          <Chart v-if="statusData?.labels?.length" type="bar" :data="statusData" :options="horizontalBarOptions" class="h-80" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">User Registration Trend</div>
          <Chart v-if="userRegData?.labels?.length" type="line" :data="userRegData" :options="baseChartOptions()" class="h-80" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Users by Role</div>
          <Chart v-if="roleData?.labels?.length" type="bar" :data="roleData" :options="horizontalBarOptions" class="h-80" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Monthly Disbursement Trend</div>
          <Chart v-if="disbursementData?.labels?.length" type="bar" :data="disbursementData" :options="baseChartOptions()" class="h-80" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8">
        <div v-for="i in 4" :key="i" class="col-span-12 lg:col-span-6 xl:col-span-3">
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
        <div v-for="i in 2" :key="i" class="col-span-12 xl:col-span-6">
          <div class="card">
            <Skeleton width="40%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="280px" />
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
