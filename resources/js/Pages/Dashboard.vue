<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useAuth } from '@/Composables/useAuth'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import Skeleton from 'primevue/skeleton'

defineOptions({ layout: AppLayout })

defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const { user } = useAuth()
const accountUrl = route('account.edit')
</script>

<template>
  <Head title="Dashboard" />

  <Deferred data="dashboardData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Users" :value="dashboardData?.totalUsers ?? 0" icon="pi pi-users" color="info" subtitle="registered accounts" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Applications" :value="dashboardData?.totalApplications ?? 0" icon="pi pi-file" color="purple" subtitle="total submitted" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Pending" :value="dashboardData?.pendingApplications ?? 0" icon="pi pi-clock" color="warn" subtitle="awaiting review" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Approved" :value="dashboardData?.approvedThisMonth ?? 0" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Activity</div>
          <ul v-if="dashboardData?.recentActivity?.length" class="p-0 mx-0 mt-0 mb-6 list-none">
            <li v-for="entry in dashboardData.recentActivity" :key="entry.id" class="flex items-center py-2 border-b border-surface">
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

        <div class="card">
          <div class="font-semibold text-xl mb-4">Quick Links</div>
          <div class="flex flex-wrap gap-3">
            <button class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium cursor-pointer border-none" style="background-color: var(--p-primary-color); color: var(--p-primary-contrast-color);" @click="router.get(accountUrl)">
              <i class="pi pi-cog"></i>
              <span>Account Settings</span>
            </button>
          </div>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">{{ user?.first_name || 'User' }}</span>
              <div class="text-surface-900 font-medium text-xl">{{ user?.role?.replace('_', ' ') || '—' }}</div>
            </div>
            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-user text-purple-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">{{ user?.email || '—' }}</span>
          <span v-if="user?.aup_accepted" class="text-muted-color"> &middot; AUP accepted</span>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-6">Account Info</div>

          <span class="block text-muted-color font-medium mb-4">AUTHENTICATION</span>
          <ul class="p-0 mx-0 mt-0 mb-6 list-none">
            <li class="flex items-center py-2 border-b border-surface">
              <div class="w-12 h-12 flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-full mr-4 shrink-0">
                <i class="pi pi-envelope text-xl! text-blue-500"></i>
              </div>
              <span class="text-surface-900 leading-normal">
                {{ user?.email || '—' }}
                <span class="text-surface-700"> &middot; logged in</span>
              </span>
            </li>
            <li class="flex items-center py-2 border-b border-surface">
              <div class="w-12 h-12 flex items-center justify-center bg-green-100 dark:bg-green-400/10 rounded-full mr-4 shrink-0">
                <i class="pi pi-shield text-xl! text-green-500"></i>
              </div>
              <span class="text-surface-900 leading-normal">
                {{ user?.aup_accepted ? 'AUP Accepted' : 'AUP Pending' }}
                <span class="text-surface-700"> &middot; acceptable use policy</span>
              </span>
            </li>
          </ul>

          <span class="block text-muted-color font-medium mb-4">SESSION</span>
          <ul class="p-0 m-0 list-none">
            <li class="flex items-center py-2 border-b border-surface">
              <div class="w-12 h-12 flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full mr-4 shrink-0">
                <i class="pi pi-id-card text-xl! text-purple-500"></i>
              </div>
              <span class="text-surface-900 leading-normal">
                User #{{ user?.id || '—' }}
                <span class="text-surface-700"> &middot; role: {{ user?.role?.replace('_', ' ') || '—' }}</span>
              </span>
            </li>
          </ul>
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
            <Skeleton width="40%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 4" :key="i" width="100%" height="3rem" />
            </div>
          </div>
        </div>

        <div class="col-span-12 lg:col-span-6 xl:col-span-3">
          <div class="card">
            <Skeleton width="40%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton width="60%" height="1rem" />
              <Skeleton width="80%" height="1rem" />
              <Skeleton width="50%" height="1rem" />
            </div>
          </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <Skeleton width="40%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 3" :key="i" width="100%" height="3rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
