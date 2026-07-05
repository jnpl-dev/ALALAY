<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import { formatDate } from '@/Utils/formatDate'

defineOptions({ layout: AppLayout })

defineProps({
  totalApplications: { type: Number, default: 0 },
  pendingApplications: { type: Number, default: 0 },
  forwardedApplications: { type: Number, default: 0 },
  returnedApplications: { type: Number, default: 0 },
  applicationsByStatus: { type: Object, default: () => ({}) },
  monthlyTrends: { type: Object, default: () => ({}) },
  recentApplications: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="AICS Analytics" />
  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12 lg:col-span-3">
      <AppKpiCard title="Total" :value="totalApplications" icon="pi pi-file" color="info" subtitle="all applications" />
    </div>
    <div class="col-span-12 lg:col-span-3">
      <AppKpiCard title="Pending" :value="pendingApplications" icon="pi pi-clock" color="warn" subtitle="needs review" />
    </div>
    <div class="col-span-12 lg:col-span-3">
      <AppKpiCard title="Forwarded" :value="forwardedApplications" icon="pi pi-send" color="success" subtitle="to MSWDO" />
    </div>
    <div class="col-span-12 lg:col-span-3">
      <AppKpiCard title="Returned" :value="returnedApplications" icon="pi pi-undo" color="danger" subtitle="to applicant" />
    </div>

    <div class="col-span-12 xl:col-span-6">
      <div class="card">
        <div class="font-semibold text-xl mb-4">Applications by Status</div>
        <div v-if="Object.keys(applicationsByStatus).length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-muted-color border-b border-surface">
                <th class="text-left py-2">Status</th>
                <th class="text-right py-2">Count</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(count, status) in applicationsByStatus" :key="status" class="border-b border-surface">
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
        <div class="font-semibold text-xl mb-4">Monthly Trends</div>
        <div v-if="Object.keys(monthlyTrends).length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-muted-color border-b border-surface">
                <th class="text-left py-2">Month</th>
                <th class="text-right py-2">Applications</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(count, month) in monthlyTrends" :key="month" class="border-b border-surface">
                <td class="py-2">{{ month }}</td>
                <td class="text-right py-2 font-medium">{{ count }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <AppEmptyState v-else icon="pi pi-chart-line" message="No trend data" />
      </div>
    </div>

    <div class="col-span-12">
      <div class="card">
        <div class="font-semibold text-xl mb-4">Recent Applications</div>
        <div v-if="recentApplications.length" class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="text-muted-color border-b border-surface">
                <th class="text-left py-2">Code</th>
                <th class="text-left py-2">Claimant</th>
                <th class="text-left py-2">Category</th>
                <th class="text-left py-2">Status</th>
                <th class="text-right py-2">Date</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="app in recentApplications" :key="app.id" class="border-b border-surface">
                <td class="py-2 font-mono text-sm">{{ app.reference_code }}</td>
                <td class="py-2">{{ app.claimant_name }}</td>
                <td class="py-2">{{ app.category_name }}</td>
                <td class="py-2"><AppStatusBadge :status="app.status" /></td>
                <td class="text-right py-2 text-muted-color">{{ formatDate(app.created_at) }}</td>
              </tr>
            </tbody>
          </table>
        </div>
        <AppEmptyState v-else icon="pi pi-inbox" message="No recent applications" />
      </div>
    </div>
  </div>
</template>
