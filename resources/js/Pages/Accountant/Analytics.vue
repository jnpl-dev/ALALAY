<script setup>
import { computed } from 'vue'
import { Head, Deferred, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppDateRangeFilter from '@/Components/Common/AppDateRangeFilter.vue'
import { CHART_COLORS, baseChartOptions, paletteColors } from '@/Utils/chartColors'
import { fillMissingDates } from '@/Utils/chartDates'
import { formatCurrency } from '@/Utils/formatCurrency'
import Skeleton from 'primevue/skeleton'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Accountant' }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({ date_from: '', date_to: '' }) },
})

function applyFilter({ date_from, date_to }) {
  router.get(route('accountant.analytics'), { date_from, date_to }, { preserveState: true })
}

function clearFilter() {
  router.get(route('accountant.analytics'), {}, { preserveState: true })
}

const trendData = computed(() => {
  const raw = props.analyticsData?.voucher_trend ?? []
  const filled = fillMissingDates(raw, props.filters.date_from, props.filters.date_to)
  return {
    labels: filled.map(d => d.date),
    datasets: [{
      label: 'Vouchers',
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

const amountTrendData = computed(() => {
  const raw = props.analyticsData?.amount_trend ?? []
  const filled = fillMissingDates(raw, props.filters.date_from, props.filters.date_to)
  return {
    labels: filled.map(d => d.date),
    datasets: [{
      label: 'Approved Amount',
      data: filled.map(d => Number(d.total) || 0),
      backgroundColor: CHART_COLORS.success,
      borderRadius: 4,
    }],
  }
})

const amountTrendOptions = baseChartOptions({
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
  },
})

const categoryAmountData = computed(() => {
  const data = props.analyticsData?.category_amount ?? []
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
  <Head title="Accountant Analytics" />

  <div class="card">
    <div class="flex items-center justify-between mb-6">
      <div class="font-semibold text-xl">Analytics</div>
    </div>
    <AppDateRangeFilter :date-from="filters.date_from" :date-to="filters.date_to" @apply="applyFilter" @clear="clearFilter" />
  </div>

  <Deferred data="analyticsData">
    <div class="flex flex-wrap gap-8 mt-8">
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Total Vouchers" :value="analyticsData?.total_vouchers ?? 0" icon="pi pi-receipt" color="primary" subtitle="in date range" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Approved" :value="analyticsData?.total_approved ?? 0" icon="pi pi-check-circle" color="success" subtitle="ready for treasurer" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Returned" :value="analyticsData?.total_returned ?? 0" icon="pi pi-undo" color="danger" subtitle="sent back to MSWDO" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Total Amount" :value="formatCurrency(analyticsData?.total_amount_approved ?? 0)" icon="pi pi-money-bill" color="success" subtitle="approved vouchers" />
      </div>
      <div class="flex-1 min-w-[180px]">
        <AppKpiCard title="Average Amount" :value="formatCurrency(analyticsData?.average_amount ?? 0)" icon="pi pi-calculator" color="info" subtitle="per approved voucher" />
      </div>
    </div>

    <div class="grid grid-cols-12 gap-8 mt-8">
      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Voucher Volume</div>
          <Chart v-if="trendData?.labels?.length" type="line" :data="trendData" :options="baseChartOptions()" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Approved Amount Over Time</div>
          <Chart v-if="amountTrendData?.labels?.length" type="bar" :data="amountTrendData" :options="amountTrendOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Approved Amount by Category</div>
          <Chart v-if="categoryAmountData?.labels?.length" type="bar" :data="categoryAmountData" :options="categoryAmountOptions" class="h-72" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>
    </div>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8 mt-8">
        <div v-for="i in 5" :key="i" class="col-span-12 lg:col-span-6 xl:col-span-2">
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
