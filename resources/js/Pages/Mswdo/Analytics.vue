<script setup>
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import BarChart from '@/Components/Charts/BarChart.vue'
import Timeline from 'primevue/timeline'
import Skeleton from 'primevue/skeleton'
import { computed } from 'vue'
import { formatDate } from '@/Utils/formatDate'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'MSWDO' }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
})

const trendChartData = computed(() => {
  const trends = props.analyticsData?.monthlyTrends ?? {}
  const labels = Object.keys(trends)
  const values = Object.values(trends)
  if (!labels.length) return { labels: [], datasets: [] }
  return {
    labels,
    datasets: [{
      label: 'Applications',
      data: values,
      backgroundColor: 'rgba(59, 130, 246, 0.5)',
      borderColor: 'rgb(59, 130, 246)',
      borderWidth: 1,
    }],
  }
})
</script>

<template>
  <Head title="MSWDO Analytics" />
  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="For Validation" :value="analyticsData?.forValidation ?? 0" icon="pi pi-file" color="info" subtitle="for validation" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Validated" :value="analyticsData?.validatedThisMonth ?? 0" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Returned" :value="analyticsData?.returned ?? 0" icon="pi pi-undo" color="warn" subtitle="needs revision" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Vouchers" :value="analyticsData?.vouchersPrepared ?? 0" icon="pi pi-receipt" color="info" subtitle="prepared" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <BarChart :data="trendChartData" title="Monthly Trends" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Pending Actions</div>
          <Timeline v-if="analyticsData?.pendingActions?.length" :value="analyticsData.pendingActions" align="left" class="text-sm">
            <template #opposite="{ item }">
              <div class="text-xs text-muted-color text-right pr-4 whitespace-nowrap">
                <div>{{ formatDate(item.created_at) }}</div>
              </div>
            </template>
            <template #marker>
              <i class="pi pi-circle-fill text-xs" style="color: var(--p-primary-color)" />
            </template>
            <template #content="{ item }">
              <div class="font-medium">{{ item.action }}</div>
              <div class="text-xs text-muted-color">{{ item.module }} · {{ item.user_name }}</div>
            </template>
          </Timeline>
          <AppEmptyState v-else icon="pi pi-inbox" message="No pending actions" />
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
            <div class="space-y-3">
              <Skeleton v-for="i in 3" :key="i" width="100%" height="1rem" />
            </div>
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
