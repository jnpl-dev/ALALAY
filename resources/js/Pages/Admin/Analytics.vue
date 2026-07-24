<script setup>
import { computed } from 'vue'
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import DataView from 'primevue/dataview'
import Skeleton from 'primevue/skeleton'
import { getStatusLabel } from '@/Utils/statusLabels'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Admin' }, { label: 'Analytics' }])

const props = defineProps({
  analyticsData: { type: Object, default: () => ({}) },
})

const statusEntries = computed(() => {
  const data = props.analyticsData?.applicationsByStatus ?? {}
  return Object.entries(data).map(([key, count]) => ({
    status: key,
    label: getStatusLabel(key).label,
    count,
  }))
})
</script>

<template>
  <Head title="Admin Analytics" />
  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Total Users" :value="analyticsData?.totalUsers ?? 0" icon="pi pi-users" color="info" subtitle="registered accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Active" :value="analyticsData?.activeUsers ?? 0" icon="pi pi-check-circle" color="success" subtitle="active accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Inactive" :value="analyticsData?.inactiveUsers ?? 0" icon="pi pi-ban" color="warn" subtitle="deactivated accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Applications" :value="analyticsData?.totalApplications ?? 0" icon="pi pi-file" color="info" subtitle="total submitted" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Applications by Status</div>
          <DataTable :value="statusEntries" striped-rows class="w-full">
            <Column field="label" header="Status">
              <template #body="{ data }">
                <AppStatusBadge :status="data.status" />
              </template>
            </Column>
            <Column field="count" header="Count" sortable />
            <template #empty>
              <AppEmptyState icon="pi pi-chart-bar" message="No data available" />
            </template>
          </DataTable>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Activity</div>
          <DataView :value="analyticsData?.recentActivity ?? []">
            <template #list="{ items }">
              <div v-for="item in items" :key="item.id" class="flex items-center py-2">
                <div class="w-12 h-12 flex items-center justify-center rounded-full mr-4 shrink-0" :style="{ backgroundColor: 'var(--color-primary-surface)' }">
                  <i class="pi pi-history text-xl!" :style="{ color: 'var(--color-primary)' }"></i>
                </div>
                <span class="text-surface-900 leading-normal">
                  {{ item.user_name }}
                  <span class="text-surface-700"> &middot; {{ item.action }} / {{ item.module }}</span>
                </span>
              </div>
            </template>
            <template #empty>
              <AppEmptyState icon="pi pi-inbox" message="No recent activity" />
            </template>
          </DataView>
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
              <Skeleton v-for="i in 3" :key="i" width="100%" height="3rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
