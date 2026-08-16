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
import { CHART_COLORS, baseChartOptions } from '@/Utils/chartColors'
import { formatDate } from '@/Utils/formatDate'
import { formatCurrency } from '@/Utils/formatCurrency'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Accountant' }, { label: 'Dashboard' }])

const props = defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const trendData = computed(() => {
  const raw = props.dashboardData?.weekly_voucher_trend ?? []
  return {
    labels: raw.map(d => d.date),
    datasets: [{
      label: 'Vouchers',
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

const voucherStatusData = computed(() => {
  const data = props.dashboardData?.voucher_statuses ?? []
  const labels = { voucher_recording: 'Pending', with_treasurer: 'Approved' }
  const colors = { voucher_recording: CHART_COLORS.warning, with_treasurer: CHART_COLORS.success }
  return {
    labels: data.map(d => labels[d.status] || d.status),
    datasets: [{
      data: data.map(d => d.count),
      backgroundColor: data.map(d => colors[d.status] || CHART_COLORS.muted),
      borderWidth: 2,
      borderColor: '#FFFFFF',
    }],
  }
})

const voucherStatusPercent = computed(() => {
  const counts = (props.dashboardData?.voucher_statuses ?? []).map(d => d.count)
  const total = counts.reduce((sum, n) => sum + n, 0)
  return total === 0 ? counts.map(() => 0) : counts.map(n => Math.round((n / total) * 100))
})

const categoryAmountData = computed(() => {
  const data = props.dashboardData?.category_amount ?? []
  return {
    labels: data.map(d => d.category_name).reverse(),
    datasets: [{
      label: 'Total Amount',
      data: data.map(d => Number(d.total)).reverse(),
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

</script>

<template>
  <Head title="Accountant Dashboard" />

  <AppGreeting />

  <Deferred data="dashboardData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6">
        <AppKpiCard title="Pending Vouchers" :value="dashboardData?.pending_vouchers ?? 0" icon="pi pi-receipt" color="warn" subtitle="needs recording" />
      </div>
      <div class="col-span-12 lg:col-span-6">
        <AppKpiCard title="Approved Today" :value="dashboardData?.approved_today ?? 0" icon="pi pi-check-circle" color="success" subtitle="sent to treasurer" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Voucher Trend This Week</div>
          <Chart v-if="trendData?.labels?.length" type="line" :data="trendData" :options="baseChartOptions()" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 md:col-span-6 xl:col-span-3">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Voucher Status</div>
          <div v-if="voucherStatusData?.labels?.length" class="flex flex-col gap-4 py-2">
            <div v-for="(item, index) in voucherStatusData.labels" :key="item" class="flex flex-col gap-1">
              <div class="flex items-center justify-between text-sm">
                <span class="font-medium text-color">{{ item }}</span>
                <span class="text-muted-color">
                  {{ voucherStatusData.datasets[0].data[index] }} · {{ voucherStatusPercent[index] }}%
                </span>
              </div>
              <div class="h-2 w-full rounded-full bg-surface-200 overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-500"
                  :style="{ width: (voucherStatusPercent[index] || 0) + '%', backgroundColor: voucherStatusData.datasets[0].backgroundColor[index] }"
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
          <div class="font-semibold text-xl mb-4">Recent Vouchers</div>
          <DataTable :value="dashboardData?.recent_vouchers ?? []" striped-rows class="w-full">
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
            <Column field="prepared_at" header="Prepared">
              <template #body="{ data }">
                {{ data.prepared_at ? formatDate(data.prepared_at) : '—' }}
              </template>
            </Column>
            <template #empty>
              <AppEmptyState icon="pi pi-inbox" message="No vouchers yet" />
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
