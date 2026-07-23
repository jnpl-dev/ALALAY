<script setup>
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Chart from 'primevue/chart'
import Skeleton from 'primevue/skeleton'
import { computed, toRaw } from 'vue'
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
})

const chartData = computed(() => {
  const trends = props.analyticsData?.monthlyTrends ?? []
  if (!trends.length) return null
  return {
    labels: trends.map(t => t.month),
    datasets: [
      {
        label: 'Cheques',
        backgroundColor: '#42A5F5',
        borderRadius: 4,
        data: trends.map(t => t.count),
      },
    ],
  }
})

const chartOptions = computed(() => ({
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      ticks: { stepSize: 1 },
    },
  },
}))
</script>

<template>
  <Head title="Treasurer Analytics" />
  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Cheques" :value="analyticsData?.chequesForProcessing ?? 0" icon="pi pi-money-bill" color="info" :subtitle="(analyticsData?.acknowledgedThisMonth ?? 0) + ' acknowledged this month'" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Acknowledged" :value="analyticsData?.acknowledgedThisMonth ?? 0" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Total Amount" :value="formatCurrency(analyticsData?.totalAmount ?? 0)" icon="pi pi-calculator" color="info" subtitle="total value" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Pending" :value="analyticsData?.pendingCheques ?? 0" icon="pi pi-clock" color="warn" subtitle="not yet acknowledged" />
      </div>

      <div class="col-span-12 xl:col-span-6 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Disbursement Overview</div>
          <Chart v-if="chartData" type="bar" :data="chartData" :options="chartOptions" class="h-64" />
          <AppEmptyState v-else icon="pi pi-chart-bar" message="Analytics data will appear here" />
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Cheques</div>
          <DataTable v-if="(analyticsData?.recentCheques ?? []).length" :value="toRaw(analyticsData.recentCheques)" striped-rows class="w-full">
            <Column field="reference_code" header="Reference" sortable />
            <Column field="claimant_name" header="Claimant" sortable />
            <Column field="status" header="Status" sortable>
              <template #body="{ data }">
                <AppStatusBadge :status="data.status" />
              </template>
            </Column>
            <Column field="amount" header="Amount" sortable>
              <template #body="{ data }">
                {{ data.amount ? formatCurrency(data.amount) : '—' }}
              </template>
            </Column>
          </DataTable>
          <AppEmptyState v-else icon="pi pi-inbox" message="No recent cheques" />
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
            <Skeleton width="100%" height="16rem" />
          </div>
        </div>
        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 3" :key="i" width="100%" height="2rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
