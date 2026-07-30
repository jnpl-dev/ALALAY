<script setup>
import { computed } from 'vue'
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import { CHART_COLORS, baseChartOptions, categoryColors } from '@/Utils/chartColors'
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
  const data = props.dashboardData?.category_distribution ?? []
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
  const data = props.dashboardData?.submission_type_distribution ?? []
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

  <Deferred data="dashboardData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Pending Applications" :value="dashboardData?.pending_applications ?? 0" icon="pi pi-clock" color="primary" subtitle="needs AICS action" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Screened Today" :value="dashboardData?.screened_today ?? 0" icon="pi pi-check-circle" color="success" subtitle="forwarded to MSWDO" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Pending Assistance Coding" :value="dashboardData?.pending_coding ?? 0" icon="pi pi-qrcode" color="warn" subtitle="needs assistance code" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Coded Today" :value="dashboardData?.coded_today ?? 0" icon="pi pi-verified" color="purple" subtitle="vouchers created" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Applications This Week</div>
          <Chart v-if="trendData?.labels?.length" type="line" :data="trendData" :options="baseChartOptions()" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Categories This Week</div>
          <Chart v-if="categoryData?.labels?.length" type="doughnut" :data="categoryData" :options="doughnutOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Submission Type This Week</div>
          <Chart v-if="submissionTypeData?.labels?.length" type="doughnut" :data="submissionTypeData" :options="doughnutOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Barangay Distribution This Week</div>
          <Chart v-if="barangayData?.labels?.length" type="bar" :data="barangayData" :options="horizontalBarOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Applications</div>
          <DataTable :value="dashboardData?.recent_applications ?? []" striped-rows class="w-full">
            <Column field="reference_code" header="Code">
              <template #body="{ data }">
                <span class="font-mono text-sm font-medium" style="color: #1B4F72">{{ data.reference_code }}</span>
              </template>
            </Column>
            <Column field="beneficiary_first_name" header="Beneficiary">
              <template #body="{ data }">
                {{ data.beneficiary_first_name }} {{ data.beneficiary_last_name }}
              </template>
            </Column>
            <Column field="category_name" header="Category">
              <template #body="{ data }">
                {{ data.category?.category_name || '—' }}
              </template>
            </Column>
            <Column field="submission_type" header="Type">
              <template #body="{ data }">
                <span class="capitalize">{{ data.submission_type }}</span>
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
