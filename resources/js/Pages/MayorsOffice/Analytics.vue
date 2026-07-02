<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
</script>

<template>
  <Head title="Mayor's Office Analytics" />
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Applications</span>
              <div class="text-surface-900 font-medium text-xl">0</div>
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
              <span class="block text-muted-color font-medium mb-4">Approved</span>
              <div class="text-surface-900 font-medium text-xl">0</div>
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
              <span class="block text-muted-color font-medium mb-4">Disbursed</span>
              <div class="text-surface-900 font-medium text-xl">PHP 0</div>
            </div>
            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-money-bill text-purple-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">total disbursed</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Beneficiaries</span>
              <div class="text-surface-900 font-medium text-xl">0</div>
            </div>
            <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-users text-orange-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">served this month</span>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Program Overview</div>
          <div class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>Analytics data will appear here</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Reports</div>
          <div class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-inbox text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>No recent reports</span>
          </div>
        </div>
      </div>
    </div>
</template>
