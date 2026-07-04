<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import { formatDate } from '@/Utils/formatDate'

defineOptions({ layout: AppLayout })

defineProps({
  forValidation: { type: Number, default: 0 },
  validatedThisMonth: { type: Number, default: 0 },
  returned: { type: Number, default: 0 },
  vouchersPrepared: { type: Number, default: 0 },
  monthlyTrends: { type: Object, default: () => ({}) },
  pendingActions: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="MSWDO Analytics" />
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="For Validation" :value="forValidation" icon="pi pi-file" color="info" subtitle="for validation" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Validated" :value="validatedThisMonth" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Returned" :value="returned" icon="pi pi-undo" color="warn" subtitle="needs revision" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Vouchers" :value="vouchersPrepared" icon="pi pi-receipt" color="purple" subtitle="prepared" />
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
          <AppEmptyState v-else icon="pi pi-chart-line" message="No trend data available" />
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Pending Actions</div>
          <div v-if="pendingActions.length" class="overflow-x-auto">
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
                <tr v-for="action in pendingActions" :key="action.id" class="border-b border-surface">
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
</template>
