<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })
</script>

<template>
  <Head title="MSWDO Analytics" />
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
          <span class="text-muted-color">for validation</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Validated</span>
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
              <span class="block text-muted-color font-medium mb-4">Returned</span>
              <div class="text-surface-900 font-medium text-xl">0</div>
            </div>
            <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-undo text-orange-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">needs revision</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Vouchers</span>
              <div class="text-surface-900 font-medium text-xl">0</div>
            </div>
            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-receipt text-purple-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">prepared</span>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Validation Overview</div>
          <div class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>Analytics data will appear here</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Pending Actions</div>
          <div class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-inbox text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>No pending actions</span>
          </div>
        </div>
      </div>
    </div>
</template>
