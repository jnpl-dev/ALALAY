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
import { CHART_COLORS, baseChartOptions, paletteColors } from '@/Utils/chartColors'
import { generateWeekLabels } from '@/Utils/chartDates'
import { formatDate } from '@/Utils/formatDate'
import { formatCurrency } from '@/Utils/formatCurrency'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Treasurer' }, { label: 'Dashboard' }])

const props = defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const statusColors = { with_treasurer: CHART_COLORS.warning, cheque_ready: CHART_COLORS.success, claimed: CHART_COLORS.primary }
const statusLabels = { with_treasurer: 'Pending', cheque_ready: 'Ready', claimed: 'Claimed' }

const claimedCount = computed(() => {
  return (props.dashboardData?.status_distribution ?? []).find(d => d.status === 'claimed')?.count ?? 0
})

const weekLabels = generateWeekLabels()

const trendData = computed(() => {
  const raw = props.dashboardData?.weekly_status_trend ?? []
  const grouped = {}
  for (const item of raw) {
    if (!grouped[item.status]) grouped[item.status] = {}
    grouped[item.status][item.date] = Number(item.count)
  }
  return {
    labels: weekLabels,
    datasets: ['with_treasurer', 'cheque_ready', 'claimed'].map(status => ({
      label: statusLabels[status] || status,
      data: weekLabels.map(d => grouped[status]?.[d] ?? 0),
      borderColor: statusColors[status] || CHART_COLORS.muted,
      backgroundColor: statusColors[status] || CHART_COLORS.muted,
      tension: 0.4,
      pointRadius: 3,
      pointHoverRadius: 5,
    })),
  }
})

const statusData = computed(() => {
  const data = props.dashboardData?.status_distribution ?? []
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
  <Head title="Treasurer Dashboard" />

  <Deferred data="dashboardData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-4">
        <AppKpiCard title="Pending Cheques" :value="dashboardData?.pending_cheques ?? 0" icon="pi pi-clock" color="warn" subtitle="needs treasurer action" />
      </div>
      <div class="col-span-12 lg:col-span-4">
        <AppKpiCard title="Ready Today" :value="dashboardData?.ready_today ?? 0" icon="pi pi-check-circle" color="success" subtitle="cheque ready for releasing" />
      </div>
      <div class="col-span-12 lg:col-span-4">
        <AppKpiCard title="Total Claimed" :value="claimedCount" icon="pi pi-verified" color="primary" subtitle="released to beneficiary" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Cheque Status This Week</div>
          <Chart v-if="trendData?.labels?.length" type="line" :data="trendData" :options="baseChartOptions()" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Status Breakdown</div>
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
          <Chart v-if="categoryAmountData?.labels?.length" type="bar" :data="categoryAmountData" :options="categoryAmountOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Applications</div>
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
              <AppEmptyState icon="pi pi-inbox" message="No applications yet" />
            </template>
          </DataTable>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8">
        <div v-for="i in 3" :key="i" class="col-span-12 lg:col-span-4">
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
        <div class="col-span-12">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 4" :key="i" width="100%" height="3rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
