<script setup>
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import Skeleton from 'primevue/skeleton'
import { formatDate } from '@/Utils/formatDate'

defineOptions({ layout: AppLayout })

defineProps({
  analyticsData: { type: Object, default: () => ({}) },
})
</script>

<template>
  <Head title="MSWDO Analytics" />
  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="For Validation" :value="analyticsData?.forValidation ?? 0" icon="pi pi-file" color="info" subtitle="for validation" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Validated" :value="analyticsData?.validatedThisMonth ?? 0" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Returned" :value="analyticsData?.returned ?? 0" icon="pi pi-undo" color="warn" subtitle="needs revision" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Vouchers" :value="analyticsData?.vouchersPrepared ?? 0" icon="pi pi-receipt" color="purple" subtitle="prepared" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Monthly Trends</div>
          <div v-if="Object.keys(analyticsData?.monthlyTrends ?? {}).length" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-muted-color border-b border-surface">
                  <th class="text-left py-2">Month</th>
                  <th class="text-right py-2">Applications</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(count, month) in analyticsData.monthlyTrends" :key="month" class="border-b border-surface">
                  <td class="py-2">{{ month }}</td>
                  <td class="text-right py-2 font-medium">{{ count }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppEmptyState v-else icon="pi pi-chart-line" message="No trend data available" />
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Pending Actions</div>
          <div v-if="analyticsData?.pendingActions?.length" class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead>
                <tr class="text-muted-color border-b border-surface">
                  <th class="text-left py-2">Action</th>
                  <th class="text-left py-2">Module</th>
                  <th class="text-left py-2">User</th>
                  <th class="text-right py-2">Date</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="action in analyticsData.pendingActions" :key="action.id" class="border-b border-surface">
                  <td class="py-2">{{ action.action }}</td>
                  <td class="py-2">{{ action.module }}</td>
                  <td class="py-2">{{ action.user_name }}</td>
                  <td class="text-right py-2 text-muted-color">{{ formatDate(action.created_at) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
          <AppEmptyState v-else icon="pi pi-inbox" message="No pending actions" />
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
              <Skeleton v-for="i in 3" :key="i" width="100%" height="2rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>
