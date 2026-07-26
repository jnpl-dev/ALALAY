<script setup>
import { computed } from 'vue'
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import DataView from 'primevue/dataview'
import Skeleton from 'primevue/skeleton'
import { CHART_COLORS, baseChartOptions, paletteColors } from '@/Utils/chartColors'
import { roleLabel } from '@/Utils/roleLabel'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Admin' }, { label: 'Dashboard' }])

const props = defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const roleData = computed(() => {
  const data = props.dashboardData?.users_by_role ?? []
  return {
    labels: data.map(d => roleLabel(d.role)),
    datasets: [{
      data: data.map(d => d.count),
      backgroundColor: paletteColors.slice(0, data.length || 1),
      borderWidth: 2,
      borderColor: '#FFFFFF',
    }],
  }
})

const chartOptions = baseChartOptions({
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
  <Head title="Admin Dashboard" />

  <Deferred data="dashboardData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-4">
        <AppKpiCard title="Total Users" :value="dashboardData?.total_users ?? 0" icon="pi pi-users" color="info" subtitle="registered accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-4">
        <AppKpiCard title="Active Users" :value="dashboardData?.active_users ?? 0" icon="pi pi-check-circle" color="success" subtitle="active accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-4">
        <AppKpiCard title="Inactive Users" :value="dashboardData?.inactive_users ?? 0" icon="pi pi-ban" color="warn" subtitle="deactivated accounts" />
      </div>

      <div class="col-span-12 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Users by Role</div>
          <Chart v-if="roleData?.labels?.length" type="doughnut" :data="roleData" :options="chartOptions" class="h-80" />
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
            <span>No data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Recent Activity</div>
          <DataView :value="dashboardData?.recent_activity ?? []">
            <template #list="{ items }">
              <div v-for="item in items" :key="item.id" class="flex items-center py-3 border-b border-surface-200 last:border-b-0">
                <div class="w-10 h-10 flex items-center justify-center rounded-full mr-3 shrink-0" style="background-color: rgba(27, 79, 114, 0.12)">
                  <i class="pi pi-history text-sm" style="color: #1B4F72"></i>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="font-medium text-surface-900 text-sm truncate">{{ item.user_name }}</div>
                  <div class="text-xs text-muted-color truncate">{{ item.action }} &middot; {{ item.module }}</div>
                </div>
              </div>
            </template>
            <template #empty>
              <AppEmptyState icon="pi pi-inbox" message="No recent activity" />
            </template>
          </DataView>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-4">
        <div class="card h-full">
          <div class="font-semibold text-xl mb-4">Unusual Activity</div>
          <DataView :value="dashboardData?.unusual_activity ?? []">
            <template #list="{ items }">
              <div v-for="item in items" :key="item.id" class="flex items-center py-3 border-b border-surface-200 last:border-b-0">
                <div class="w-10 h-10 flex items-center justify-center rounded-full mr-3 shrink-0" style="background-color: rgba(220, 38, 38, 0.12)">
                  <i class="pi pi-exclamation-triangle text-sm" style="color: #DC2626"></i>
                </div>
                <div class="min-w-0 flex-1">
                  <div class="font-medium text-surface-900 text-sm truncate">{{ item.action }}</div>
                  <div class="text-xs text-muted-color truncate">{{ item.description || item.module }}</div>
                  <div class="text-xs text-muted-color">{{ item.user_name }}</div>
                </div>
              </div>
            </template>
            <template #empty>
              <AppEmptyState icon="pi pi-shield" message="No unusual activity" />
            </template>
          </DataView>
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
        <div class="col-span-12 xl:col-span-4">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <Skeleton width="100%" height="280px" />
          </div>
        </div>
        <div class="col-span-12 xl:col-span-4">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 3" :key="i" width="100%" height="3.5rem" />
            </div>
          </div>
        </div>
        <div class="col-span-12 xl:col-span-4">
          <div class="card">
            <Skeleton width="50%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 3" :key="i" width="100%" height="3.5rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
