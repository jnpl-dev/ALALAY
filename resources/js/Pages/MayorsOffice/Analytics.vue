<script setup>
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
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
        label: 'Applications',
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
  <Head title="Mayor's Office Analytics" />
  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Applications" :value="analyticsData?.totalApplications ?? 0" icon="pi pi-file" color="info" :subtitle="(analyticsData?.approvedThisMonth ?? 0) + ' approved this month'" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Approved" :value="analyticsData?.approvedThisMonth ?? 0" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Disbursed" :value="formatCurrency(analyticsData?.totalDisbursed ?? 0)" icon="pi pi-money-bill" color="success" subtitle="total disbursed" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Beneficiaries" :value="analyticsData?.beneficiariesServed ?? 0" icon="pi pi-users" color="warn" subtitle="served this month" />
      </div>

      <div class="col-span-12 xl:col-span-6 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Program Overview</div>
          <Chart v-if="chartData" type="bar" :data="chartData" :options="chartOptions" class="h-64" />
          <AppEmptyState v-else icon="pi pi-chart-bar" message="Analytics data will appear here" />
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Reports by Category</div>
          <DataTable v-if="(analyticsData?.applicationsByCategory ?? []).length" :value="toRaw(analyticsData.applicationsByCategory)" striped-rows class="w-full">
            <Column field="category_name" header="Category" sortable />
            <Column field="count" header="Applications" sortable />
          </DataTable>
          <AppEmptyState v-else icon="pi pi-inbox" message="No recent reports" />
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
