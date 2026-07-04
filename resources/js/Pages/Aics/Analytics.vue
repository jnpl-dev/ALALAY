<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { formatDate } from '@/Utils/formatDate'
import { getStatusLabel } from '@/Utils/statusLabels'

defineOptions({ layout: AppLayout })

defineProps({
  totalApplications: { type: Number, default: 0 },
  pendingApplications: { type: Number, default: 0 },
  approvedThisMonth: { type: Number, default: 0 },
  codesIssued: { type: Number, default: 0 },
  monthlyTrends: { type: Object, default: () => ({}) },
  recentApplications: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="AICS Analytics" />
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Applications</span>
              <div class="text-surface-900 font-medium text-xl">{{ totalApplications }}</div>
            </div>
            <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-file text-blue-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">total submitted</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Pending</span>
              <div class="text-surface-900 font-medium text-xl">{{ pendingApplications }}</div>
            </div>
            <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-clock text-orange-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">awaiting review</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Approved</span>
              <div class="text-surface-900 font-medium text-xl">{{ approvedThisMonth }}</div>
            </div>
            <div class="flex items-center justify-center bg-green-100 dark:bg-green-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-check-circle text-green-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">this month</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Codes Issued</span>
              <div class="text-surface-900 font-medium text-xl">{{ codesIssued }}</div>
            </div>
            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-qrcode text-purple-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">assistance codes</span>
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
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-line text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>No trend data available</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
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
                  <td class="py-2">{{ getStatusLabel(app.status).label }}</td>
                  <td class="text-right py-2 text-muted-color">{{ formatDate(app.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-inbox text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>No recent applications</span>
          </div>
        </div>
      </div>
    </div>
</template>
