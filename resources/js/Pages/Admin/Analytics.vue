<script setup>
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import Skeleton from 'primevue/skeleton'

defineOptions({ layout: AppLayout })

defineProps({
  analyticsData: { type: Object, default: () => ({}) },
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
        <AppKpiCard title="Applications" :value="analyticsData?.totalApplications ?? 0" icon="pi pi-file" color="purple" subtitle="total submitted" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Applications by Status</div>
          <div v-if="Object.keys(analyticsData?.applicationsByStatus ?? {}).length" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-muted-color border-b border-surface">
                  <th class="text-left py-2">Status</th>
                  <th class="text-right py-2">Count</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(count, status) in analyticsData.applicationsByStatus" :key="status" class="border-b border-surface">
                  <td class="py-2"><AppStatusBadge :status="status" /></td>
                  <td class="text-right py-2 font-medium">{{ count }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppEmptyState v-else icon="pi pi-chart-bar" message="No data available" />
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Activity</div>
          <ul v-if="analyticsData?.recentActivity?.length" class="p-0 mx-0 mt-0 mb-6 list-none">
            <li v-for="entry in analyticsData.recentActivity" :key="entry.id" class="flex items-center py-2 border-b border-surface">
              <div class="w-12 h-12 flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-full mr-4 shrink-0">
                <i class="pi pi-history text-xl! text-blue-500"></i>
              </div>
              <span class="text-surface-900 leading-normal">
                {{ entry.user_name }}
                <span class="text-surface-700"> &middot; {{ entry.action }} / {{ entry.module }}</span>
              </span>
            </li>
          </ul>
          <AppEmptyState v-else icon="pi pi-inbox" message="No recent activity" />
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
