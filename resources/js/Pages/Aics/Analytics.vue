<script setup>
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import LineChart from '@/Components/Charts/LineChart.vue'
import DonutChart from '@/Components/Charts/DonutChart.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import { formatDate } from '@/Utils/formatDate'
import { getStatusLabel } from '@/Utils/statusLabels'
import { computed } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
})

const severityColors = {
  info: { bg: '#3B82F6', hover: '#2563EB' },
  success: { bg: '#22C55E', hover: '#16A34A' },
  warn: { bg: '#F59E0B', hover: '#D97706' },
  danger: { bg: '#EF4444', hover: '#DC2626' },
  contrast: { bg: '#6B7280', hover: '#4B5563' },
}

const statusChartData = computed(() => {
  const raw = props.analyticsData?.applicationsByStatus
  const hasData = raw && Object.keys(raw).length > 0
  const labels = hasData ? Object.keys(raw).map(k => getStatusLabel(k).label) : ['Pending', 'Approved', 'Returned', 'On Hold']
  const values = hasData ? Object.values(raw) : [12, 8, 3, 2]
  const keys = hasData ? Object.keys(raw) : []

  const colors = keys.length
    ? keys.map(k => {
        const sev = getStatusLabel(k).severity
        return severityColors[sev] ?? severityColors.contrast
      })
    : [{ bg: '#3B82F6' }, { bg: '#22C55E' }, { bg: '#EF4444' }, { bg: '#F59E0B' }]

  return {
    labels,
    datasets: [
      {
        data: values,
        backgroundColor: colors.map(c => c.bg),
        hoverBackgroundColor: colors.map(c => c.hover ?? c.bg),
      },
    ],
  }
})

const commonChartOptions = {
  animation: { duration: 1200, easing: 'easeInOutQuart' },
  transitions: { resize: { animation: { duration: 1200 } } },
  responsive: true,
  maintainAspectRatio: false,
}

const statusChartOptions = computed(() => ({ ...commonChartOptions }))

const monthlyChartData = computed(() => {
  const raw = props.analyticsData?.monthlyTrends
  const hasData = raw && Object.keys(raw).length > 0
  const entries = hasData ? Object.entries(raw) : []
  const labels = entries.length ? entries.map(([m]) => m) : ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
  const values = entries.length ? entries.map(([, v]) => v) : [5, 8, 12, 7, 14, 10]

  return {
    labels,
    datasets: [
      {
        label: 'Applications',
        data: values,
        fill: true,
        borderColor: '#3B82F6',
        backgroundColor: 'rgba(59, 130, 246, 0.1)',
        tension: 0.4,
      },
    ],
  }
})

const monthlyChartOptions = computed(() => ({ ...commonChartOptions }))
</script>

<template>
  <Head title="AICS Analytics" />
  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Total" :value="analyticsData?.totalApplications ?? 0" icon="pi pi-file" color="info" subtitle="all applications" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Pending" :value="analyticsData?.pendingApplications ?? 0" icon="pi pi-clock" color="warn" subtitle="needs review" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Forwarded" :value="analyticsData?.forwardedApplications ?? 0" icon="pi pi-send" color="success" subtitle="to MSWDO" />
      </div>
      <div class="col-span-12 lg:col-span-3">
        <AppKpiCard title="Returned" :value="analyticsData?.returnedApplications ?? 0" icon="pi pi-undo" color="danger" subtitle="to applicant" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <LineChart :data="monthlyChartData" :options="monthlyChartOptions" title="Monthly Trends" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <DonutChart :data="statusChartData" :options="statusChartOptions" title="Applications by Status" />
      </div>

      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Applications</div>
          <DataTable :value="analyticsData?.recentApplications ?? []" striped-rows class="w-full">
            <Column field="reference_code" header="Code" />
            <Column field="claimant_name" header="Claimant" />
            <Column field="category_name" header="Category" />
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
              <AppEmptyState icon="pi pi-inbox" message="No recent applications" />
            </template>
          </DataTable>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8">
        <div v-for="i in 4" :key="i" class="col-span-12 lg:col-span-3">
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
            <Skeleton width="100%" height="200px" />
          </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="200px" />
          </div>
        </div>
        <div class="col-span-12">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 4" :key="i" width="100%" height="2rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
