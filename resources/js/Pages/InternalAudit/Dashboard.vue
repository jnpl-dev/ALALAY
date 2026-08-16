<script setup>
import { computed } from 'vue'
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppGreeting from '@/Components/Common/AppGreeting.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import { CHART_COLORS, baseChartOptions } from '@/Utils/chartColors'
import { formatDate } from '@/Utils/formatDate'
import { formatCurrency } from '@/Utils/formatCurrency'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Internal Audit' }, { label: 'Dashboard' }])

const props = defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const decisionTrendData = computed(() => {
  const raw = props.dashboardData?.decision_trend ?? []
  const labels = [...new Set(raw.map(d => d.date))].sort()
  const byDecision = (decision) => labels.map(date => raw.find(r => r.date === date && r.decision === decision)?.count ?? 0)
  return {
    labels,
    datasets: [
      {
        label: 'Approved',
        data: byDecision('approved'),
        backgroundColor: CHART_COLORS.success,
        borderRadius: 3,
      },
      {
        label: 'Returned',
        data: byDecision('returned'),
        backgroundColor: CHART_COLORS.danger,
        borderRadius: 3,
      },
    ],
  }
})

const stackedBarOptions = baseChartOptions({
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
  scales: {
    x: { stacked: true },
    y: { beginAtZero: true, stacked: true },
  },
})

const decisionCounts = computed(() => {
  const c = props.dashboardData?.decision_counts ?? {}
  return {
    approved: c.approved ?? 0,
    returned: c.returned ?? 0,
  }
})

const approvalRate = computed(() => {
  const total = decisionCounts.value.approved + decisionCounts.value.returned
  return total === 0 ? 0 : Math.round((decisionCounts.value.approved / total) * 100)
})

const decisionColors = { approved: CHART_COLORS.success, returned: CHART_COLORS.danger }

const approvedRateBar = computed(() => [
  { label: 'Approved', count: decisionCounts.value.approved, pct: approvalRate.value, color: decisionColors.approved },
  { label: 'Returned', count: decisionCounts.value.returned, pct: 100 - approvalRate.value, color: decisionColors.returned },
])

const amountByCategoryData = computed(() => {
  const data = props.dashboardData?.approved_amount_by_category ?? []
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
  <Head title="Internal Audit Dashboard" />

  <AppGreeting />

  <Deferred data="dashboardData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-4">
        <AppKpiCard title="Pending Reviews" :value="dashboardData?.pending_reviews ?? 0" icon="pi pi-clock" color="primary" subtitle="needs coding review" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-4">
        <AppKpiCard title="Approved Today" :value="dashboardData?.approved_today ?? 0" icon="pi pi-check-circle" color="success" subtitle="ready for voucher creation" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-4">
        <AppKpiCard title="Returned Today" :value="dashboardData?.returned_today ?? 0" icon="pi pi-undo" color="danger" subtitle="sent back to AICS" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Coding Decisions This Week</div>
          <Chart v-if="decisionTrendData?.labels?.length" type="bar" :data="decisionTrendData" :options="stackedBarOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Approval Rate This Week</div>
          <div v-if="approvedRateBar.length" class="flex flex-col gap-5 py-2">
            <div v-for="item in approvedRateBar" :key="item.label" class="flex flex-col gap-1">
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

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Approved Amount by Category</div>
          <Chart v-if="amountByCategoryData?.labels?.length" type="bar" :data="amountByCategoryData" :options="horizontalAmountOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Reviews</div>
          <DataTable :value="dashboardData?.recent_reviews ?? []" striped-rows class="w-full">
            <Column field="reference_code" header="Code">
              <template #body="{ data }">
                <span class="font-mono text-sm font-medium" style="color: #1B4F72">{{ data.reference_code }}</span>
              </template>
            </Column>
            <Column field="claimant_name" header="Claimant" />
            <Column field="category_name" header="Category" />
            <Column field="amount" header="Amount">
              <template #body="{ data }">
                {{ data.amount != null ? formatCurrency(data.amount) : '—' }}
              </template>
            </Column>
            <Column field="decision" header="Decision">
              <template #body="{ data }">
                <span
                  class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize"
                  :style="data.decision === 'approved'
                    ? 'background-color: rgba(5, 150, 105, 0.15); color: ' + CHART_COLORS.success
                    : 'background-color: rgba(220, 38, 38, 0.15); color: ' + CHART_COLORS.danger"
                >{{ data.decision }}</span>
              </template>
            </Column>
            <Column field="reviewed_at" header="Date">
              <template #body="{ data }">
                {{ formatDate(data.reviewed_at) }}
              </template>
            </Column>
            <template #empty>
              <AppEmptyState icon="pi pi-inbox" message="No reviews yet" />
            </template>
          </DataTable>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8">
        <div v-for="i in 3" :key="i" class="col-span-12 lg:col-span-6 xl:col-span-4">
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