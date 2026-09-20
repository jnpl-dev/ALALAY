<script setup>
import { computed } from 'vue'
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppGreeting from '@/Components/Common/AppGreeting.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import Tag from 'primevue/tag'
import { CHART_COLORS, baseChartOptions, ALL_CATEGORIES, ALL_CATEGORY_COLORS, ALL_SUBMISSION_TYPES, getCategoryTagSeverity, getTypeTagSeverity, getTypeLabel } from '@/Utils/chartColors'
import { formatDate } from '@/Utils/formatDate'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'AICS' }, { label: 'Dashboard' }])

const props = defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const trendData = computed(() => {
  const raw = props.dashboardData?.weekly_trend ?? []
  return {
    labels: raw.map(d => d.date),
    datasets: [{
      label: 'Applications Submitted',
      data: raw.map(d => d.count),
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
  const raw = props.dashboardData?.category_distribution ?? []
  const map = Object.fromEntries(raw.map(d => [d.category_name, d.count]))
  return {
    labels: ALL_CATEGORIES,
    datasets: [{
      data: ALL_CATEGORIES.map(c => map[c] ?? 0),
      backgroundColor: ALL_CATEGORY_COLORS,
      borderWidth: 2,
      borderColor: '#FFFFFF',
    }],
  }
})

const submissionTypeData = computed(() => {
  const raw = props.dashboardData?.submission_type_distribution ?? []
  const map = Object.fromEntries(raw.map(d => [d.submission_type, d.count]))
  return {
    labels: ALL_SUBMISSION_TYPES.map(t => t.label),
    datasets: [{
      data: ALL_SUBMISSION_TYPES.map(t => map[t.key] ?? 0),
      backgroundColor: ALL_SUBMISSION_TYPES.map(t => t.color),
      borderWidth: 2,
      borderColor: '#FFFFFF',
    }],
  }
})

const submissionTypePercent = computed(() => {
  const counts = ALL_SUBMISSION_TYPES.map(t => {
    const raw = props.dashboardData?.submission_type_distribution ?? []
    const found = raw.find(d => d.submission_type === t.key)
    return found?.count ?? 0
  })
  const total = counts.reduce((sum, n) => sum + n, 0)
  return total === 0 ? counts.map(() => 0) : counts.map(n => Math.round((n / total) * 100))
})

const barangayData = computed(() => {
  const data = props.dashboardData?.barangay_distribution ?? []
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
  interaction: { mode: 'nearest', intersect: true },
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
  <Head title="AICS Dashboard" />

  <AppGreeting />

  <Deferred data="dashboardData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Pending Applications" :value="dashboardData?.pending_applications ?? 0" :change="dashboardData?.pending_applications_change" change-label="vs last week" icon="pi pi-clock" color="primary" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Screened Today" :value="dashboardData?.screened_today ?? 0" :change="dashboardData?.screened_change" change-label="vs yesterday" icon="pi pi-check-circle" color="success" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Pending Assistance Coding" :value="dashboardData?.pending_coding ?? 0" :change="dashboardData?.pending_coding_change" change-label="vs last week" icon="pi pi-qrcode" color="warn" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Coded Today" :value="dashboardData?.coded_today ?? 0" :change="dashboardData?.coded_change" change-label="vs yesterday" icon="pi pi-verified" color="purple" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Applications This Week</div>
          <Chart type="line" :data="trendData" :options="baseChartOptions()" class="h-72" />
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Categories This Week</div>
          <Chart type="doughnut" :data="categoryData" :options="doughnutOptions" class="h-72" />
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Submission Type This Week</div>
          <div class="flex flex-col gap-4 py-2 h-72">
            <div v-for="(item, index) in submissionTypeData.labels" :key="item" class="flex flex-col gap-1">
              <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-color">{{ item }}</span>
                <span class="text-muted-color">
                  {{ submissionTypeData.datasets[0].data[index] }} · {{ submissionTypePercent[index] }}%
                </span>
              </div>
              <div class="h-3 w-full rounded-full overflow-hidden" style="background-color: var(--p-surface-200)">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :style="{ width: (submissionTypePercent[index] || 0) + '%', backgroundColor: submissionTypeData.datasets[0].backgroundColor[index] }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-span-12">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Barangay Distribution This Week</div>
          <Chart type="bar" :data="barangayData" :options="horizontalBarOptions" class="h-72" />
        </div>
      </div>

      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Applications</div>
          <DataTable :value="dashboardData?.recent_applications ?? []" striped-rows class="w-full">
            <Column field="reference_code" header="Code">
              <template #body="{ data }">
                <span class="font-mono text-sm font-medium" style="color: var(--p-primary-color)">{{ data.reference_code }}</span>
              </template>
            </Column>
            <Column field="beneficiary_first_name" header="Beneficiary">
              <template #body="{ data }">
                {{ data.beneficiary_first_name }} {{ data.beneficiary_last_name }}
              </template>
            </Column>
            <Column field="category_name" header="Category">
              <template #body="{ data }">
                <Tag v-if="data.category" :severity="getCategoryTagSeverity(data.category.category_name)" :value="data.category.category_name" rounded />
                <span v-else class="text-muted-color text-xs">—</span>
              </template>
            </Column>
            <Column field="submission_type" header="Type">
              <template #body="{ data }">
                <Tag :severity="getTypeTagSeverity(data.submission_type)" :value="getTypeLabel(data.submission_type)" rounded />
              </template>
            </Column>
            <Column field="status" header="Status">
              <template #body="{ data }">
                <AppStatusBadge :status="data.status" />
              </template>
            </Column>
            <Column field="created_at" header="Date">
              <template #body="{ data }">
                {{ formatDate(data.created_at) }}
              </template>
            </Column>
            <template #empty>
              <AppEmptyState icon="pi pi-inbox" message="No applications yet" />
            </template>
          </DataTable>
        </div>
      </div>
    </div>

    <template #fallback>
    <div class="grid grid-cols-12 gap-8 auto-rows-fr">
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
            <div class="space-y-3">
              <Skeleton v-for="i in 4" :key="i" width="100%" height="3rem" />
            </div>
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
