<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useAuth } from '@/Composables/useAuth'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import Skeleton from 'primevue/skeleton'
import { formatDate } from '@/Utils/formatDate'

defineOptions({ layout: AppLayout })

defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const { user } = useAuth()
</script>

<template>
  <Head title="Dashboard" />

  <Deferred data="dashboardData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Total" :value="dashboardData?.totalApplications ?? 0" icon="pi pi-file" color="info" subtitle="all applications" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Pending Review" :value="dashboardData?.pendingApplications ?? 0" icon="pi pi-clock" color="warn" subtitle="needs AICS action" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Forwarded" :value="dashboardData?.forwardedApplications ?? 0" icon="pi pi-send" color="success" subtitle="to MSWDO" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Returned" :value="dashboardData?.returnedApplications ?? 0" icon="pi pi-undo" color="danger" subtitle="to applicant" />
      </div>

      <div class="col-span-12 xl:col-span-8">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Applications</div>
          <div v-if="dashboardData?.recentApplications?.length" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-muted-color border-b border-surface">
                  <th class="text-left py-2">Code</th>
                  <th class="text-left py-2">Claimant</th>
                  <th class="text-left py-2">Status</th>
                  <th class="text-right py-2">Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="app in dashboardData.recentApplications" :key="app.id" class="border-b border-surface">
                  <td class="py-2 font-mono text-sm">{{ app.reference_code }}</td>
                  <td class="py-2">{{ app.claimant_name }}</td>
                  <td class="py-2"><AppStatusBadge :status="app.status" /></td>
                  <td class="text-right py-2 text-muted-color">{{ formatDate(app.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppEmptyState v-else icon="pi pi-inbox" message="No applications yet" />
        </div>
      </div>

      <div class="col-span-12 xl:col-span-4">
        <div class="card mb-4">
          <div class="font-semibold text-xl mb-4">{{ user?.first_name || 'User' }}</div>
          <div class="flex items-center gap-3 mb-3">
            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full" style="width: 3rem; height: 3rem">
              <i class="pi pi-user text-purple-500 text-xl!"></i>
            </div>
            <div>
              <div class="font-medium text-surface-900">{{ user?.role?.replace('_', ' ') || '—' }}</div>
              <div class="text-muted-color text-sm">{{ user?.email }}</div>
            </div>
          </div>
          <hr class="border-surface my-3">
          <button class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg text-sm font-medium cursor-pointer border-none transition-colors hover:opacity-80" style="background-color: var(--p-primary-color); color: var(--p-primary-contrast-color);" @click="router.get(route('aics.applications.index'))">
            <i class="pi pi-list"></i>
            <span>View All Applications</span>
          </button>
        </div>

        <div class="card">
          <div class="font-semibold text-xl mb-4">Quick Actions</div>
          <div class="flex flex-col gap-2">
            <button class="w-full inline-flex items-center gap-2 px-4 py-3 rounded-lg text-sm font-medium cursor-pointer border-none transition-colors hover:opacity-80" style="background-color: color-mix(in srgb, var(--p-primary-color) 12%, transparent); color: var(--p-primary-color);" @click="router.get(route('aics.applications.index'))">
              <i class="pi pi-search"></i>
              <span>Review Applications</span>
            </button>
            <button class="w-full inline-flex items-center gap-2 px-4 py-3 rounded-lg text-sm font-medium cursor-pointer border-none transition-colors hover:opacity-80" style="background-color: color-mix(in srgb, var(--p-primary-color) 12%, transparent); color: var(--p-primary-color);" @click="router.get(route('aics.assistance-codes.index'))">
              <i class="pi pi-qrcode"></i>
              <span>Assistance Codes</span>
            </button>
            <button class="w-full inline-flex items-center gap-2 px-4 py-3 rounded-lg text-sm font-medium cursor-pointer border-none transition-colors hover:opacity-80" style="background-color: color-mix(in srgb, var(--p-primary-color) 12%, transparent); color: var(--p-primary-color);" @click="router.get(route('aics.analytics'))">
              <i class="pi pi-chart-bar"></i>
              <span>View Analytics</span>
            </button>
          </div>
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

        <div class="col-span-12 xl:col-span-8">
          <div class="card">
            <Skeleton width="40%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 5" :key="i" width="100%" height="3rem" />
            </div>
          </div>
        </div>

        <div class="col-span-12 xl:col-span-4">
          <div class="card">
            <Skeleton width="40%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton width="60%" height="1rem" />
              <Skeleton width="80%" height="1rem" />
              <Skeleton width="50%" height="1rem" />
              <Skeleton width="100%" height="2.5rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
