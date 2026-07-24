<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useAuth } from '@/Composables/useAuth'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Skeleton from 'primevue/skeleton'
import { formatDate } from '@/Utils/formatDate'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Home' }, { label: 'Dashboard' }])

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
          <DataTable :value="dashboardData?.recentApplications ?? []" striped-rows class="w-full">
            <Column field="reference_code" header="Code" />
            <Column field="claimant_name" header="Claimant" />
            <Column field="status" header="Status">
              <template #body="{ data }">
                <AppStatusBadge :status="data.status" />
              </template>
            </Column>
            <Column field="created_at" header="Date">
              <template #body="{ data }">
                {{ formatDate(data.created_at) }}
              </template>
            </Column>
            <template #empty>
              <AppEmptyState icon="pi pi-inbox" message="No applications yet" />
            </template>
          </DataTable>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-4">
        <div class="card mb-4">
          <div class="font-semibold text-xl mb-4">{{ user?.first_name || 'User' }}</div>
          <div class="flex items-center gap-3 mb-3">
            <div class="flex items-center justify-center bg-primary-emphasis rounded-full" style="width: 3rem; height: 3rem">
              <i class="pi pi-user text-primary-contrast text-xl!"></i>
            </div>
            <div>
              <div class="font-medium text-surface-900">{{ user?.role?.replace('_', ' ') || '—' }}</div>
              <div class="text-muted-color text-sm">{{ user?.email }}</div>
            </div>
          </div>
          <Divider />
          <Button label="View All Applications" icon="pi pi-list" severity="primary" fluid
            @click="router.get(route('aics.applications.index'))" />
        </div>

        <div class="card">
          <div class="font-semibold text-xl mb-4">Quick Actions</div>
          <div class="flex flex-col gap-2">
            <Button label="Review Applications" icon="pi pi-search" severity="secondary" outlined fluid class="justify-start active:scale-[0.98] transition-transform"
              @click="router.get(route('aics.applications.index'))" />
            <Button label="Assistance Codes" icon="pi pi-qrcode" severity="secondary" outlined fluid class="justify-start active:scale-[0.98] transition-transform"
              @click="router.get(route('aics.assistance-codes.index'))" />
            <Button label="View Analytics" icon="pi pi-chart-bar" severity="secondary" outlined fluid class="justify-start active:scale-[0.98] transition-transform"
              @click="router.get(route('aics.analytics'))" />
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
