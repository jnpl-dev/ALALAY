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
import { CHART_COLORS, baseChartOptions, categoryColors, paletteColors } from '@/Utils/chartColors'
import { formatDate } from '@/Utils/formatDate'
import { formatCurrency } from '@/Utils/formatCurrency'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: "Mayor's Office" }, { label: 'Dashboard' }])

const props = defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const volumeData = computed(() => {
  const raw = props.dashboardData?.daily_volume ?? []
  return {
    labels: raw.map(d => d.date),
    datasets: [{
      label: 'Applications',
      data: raw.map(d => d.count),
      backgroundColor: CHART_COLORS.primary,
      borderRadius: 4,
    }],
  }
})

const volumeOptions = baseChartOptions({
  plugins: { legend: { display: false } },
  scales: {
    x: { grid: { display: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, maxRotation: 45 } },
    y: { beginAtZero: true, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, stepSize: 1 } },
  },
})

const pipelineData = computed(() => {
  const data = props.dashboardData?.pipeline_stages ?? []
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

const pipelineOptions = baseChartOptions({
  indexAxis: 'y',
  interaction: { intersect: false, mode: 'y' },
  plugins: { legend: { display: false } },
  scales: {
    x: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.06)', drawBorder: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, stepSize: 1 } },
    y: { grid: { display: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 } } },
  },
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

const barangayOptions = baseChartOptions({
  indexAxis: 'y',
  interaction: { intersect: false, mode: 'y' },
  plugins: { legend: { display: false } },
  scales: {
    x: { beginAtZero: true, grid: { color: 'rgba(0, 0, 0, 0.06)', drawBorder: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, stepSize: 1 } },
    y: { grid: { display: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 } } },
  },
})

const categoryAmountData = computed(() => {
  const data = props.dashboardData?.amount_by_category ?? []
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

const categoryAmountOptions = baseChartOptions({
  plugins: { legend: { display: false } },
  scales: {
    y: { beginAtZero: true, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, callback: (v) => '₱' + Number(v).toLocaleString() } },
    x: { grid: { display: false }, ticks: { font: { family: 'Lato, sans-serif', size: 11 }, maxRotation: 45 } },
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
  <Head title="Mayor's Office Dashboard" />

  <Deferred data="dashboardData">
    <!-- Today's KPIs -->
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Applications Today" :value="dashboardData?.applications_today ?? 0" icon="pi pi-file" color="primary" subtitle="submitted today" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Approved Today" :value="dashboardData?.approved_today ?? 0" icon="pi pi-check-circle" color="success" subtitle="moved forward" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Cheques Ready Today" :value="dashboardData?.cheques_ready_today ?? 0" icon="pi pi-verified" color="purple" subtitle="ready for release" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Claimed Today" :value="dashboardData?.claimed_today ?? 0" icon="pi pi-money-bill" color="success" subtitle="released to beneficiary" />
      </div>
    </div>

    <!-- Monthly KPIs -->
    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Total This Month" :value="dashboardData?.total_apps_month ?? 0" icon="pi pi-file" color="info" subtitle="applications this month" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Approved This Month" :value="dashboardData?.total_approved_month ?? 0" icon="pi pi-check-circle" color="success" subtitle="in pipeline" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Disbursed This Month" :value="formatCurrency(dashboardData?.total_disbursed_month ?? 0)" icon="pi pi-money-bill" color="primary" subtitle="total released" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="On Hold" :value="dashboardData?.total_on_hold ?? 0" icon="pi pi-pause-circle" color="danger" subtitle="currently on hold" />
      </div>
    </div>

    <!-- Charts row 1 -->
    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Daily Application Volume</div>
          <Chart v-if="volumeData?.labels?.length" type="bar" :data="volumeData" :options="volumeOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Pipeline Overview</div>
          <Chart v-if="pipelineData?.labels?.length" type="bar" :data="pipelineData" :options="pipelineOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts row 2 -->
    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Categories</div>
          <Chart v-if="categoryData?.labels?.length" type="doughnut" :data="categoryData" :options="doughnutOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Applications by Barangay</div>
          <Chart v-if="barangayData?.labels?.length" type="bar" :data="barangayData" :options="barangayOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-5">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Amount by Category</div>
          <Chart v-if="categoryAmountData?.labels?.length" type="bar" :data="categoryAmountData" :options="categoryAmountOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent -->
    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Activity</div>
          <DataTable :value="dashboardData?.recent_applications ?? []" striped-rows class="w-full">
            <Column field="reference_code" header="Reference">
              <template #body="{ data }">
                <span class="font-mono text-sm font-medium" style="color: var(--p-primary-color)">{{ data.reference_code }}</span>
              </template>
            </Column>
            <Column field="beneficiary_name" header="Beneficiary" />
            <Column field="category_name" header="Category" />
            <Column field="amount" header="Amount">
              <template #body="{ data }">
                {{ data.amount ? formatCurrency(data.amount) : '—' }}
              </template>
            </Column>
            <Column field="status" header="Status">
              <template #body="{ data }">
                <AppStatusBadge :status="data.status" />
              </template>
            </Column>
            <Column field="updated_at" header="Updated">
              <template #body="{ data }">
                {{ formatDate(data.updated_at) }}
              </template>
            </Column>
            <template #empty>
              <AppEmptyState icon="pi pi-inbox" message="No activity yet" />
            </template>
          </DataTable>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8">
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
        <div class="col-span-12 md:col-span-6 xl:col-span-3">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div class="col-span-12 md:col-span-6 xl:col-span-4">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div class="col-span-12 xl:col-span-5">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="260px" />
          </div>
        </div>
        <div class="col-span-12">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 6" :key="i" width="100%" height="3rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
