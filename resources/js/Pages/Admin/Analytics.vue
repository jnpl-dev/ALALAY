<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'

defineOptions({ layout: AppLayout })

defineProps({
  totalUsers: { type: Number, default: 0 },
  activeUsers: { type: Number, default: 0 },
  inactiveUsers: { type: Number, default: 0 },
  totalApplications: { type: Number, default: 0 },
  recentActivity: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="Admin Analytics" />
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Total Users" :value="totalUsers" icon="pi pi-users" color="info" subtitle="registered accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Active" :value="activeUsers" icon="pi pi-check-circle" color="success" subtitle="active accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Inactive" :value="inactiveUsers" icon="pi pi-ban" color="warn" subtitle="deactivated accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Applications" :value="totalApplications" icon="pi pi-file" color="purple" subtitle="total submitted" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">System Overview</div>
          <AppEmptyState icon="pi pi-chart-bar" message="Analytics data will appear here" />
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Activity</div>
          <ul v-if="recentActivity.length" class="p-0 mx-0 mt-0 mb-6 list-none">
            <li v-for="entry in recentActivity" :key="entry.id" class="flex items-center py-2 border-b border-surface">
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
</template>
