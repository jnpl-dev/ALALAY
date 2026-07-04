<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps({
  chequesForProcessing: { type: Number, default: 0 },
  acknowledgedThisMonth: { type: Number, default: 0 },
  totalAmount: { type: Number, default: 0 },
  pendingCheques: { type: Number, default: 0 },
  monthlyTrends: { type: Array, default: () => [] },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  recentCheques: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="Treasurer Analytics" />
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Cheques</span>
              <div class="text-surface-900 font-medium text-xl">{{ chequesForProcessing }}</div>
            </div>
            <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-money-bill text-blue-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">{{ acknowledgedThisMonth }} </span>
          <span class="text-muted-color">acknowledged this month</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Acknowledged</span>
              <div class="text-surface-900 font-medium text-xl">{{ acknowledgedThisMonth }}</div>
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
              <span class="block text-muted-color font-medium mb-4">Total Amount</span>
              <div class="text-surface-900 font-medium text-xl">{{ 'PHP ' + Number(totalAmount).toLocaleString() }}</div>
            </div>
            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-calculator text-purple-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">total value</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Pending</span>
              <div class="text-surface-900 font-medium text-xl">{{ pendingCheques }}</div>
            </div>
            <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-clock text-orange-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">not yet acknowledged</span>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Disbursement Overview</div>
          <table v-if="monthlyTrends.length" class="w-full">
            <thead>
              <tr class="text-muted-color font-medium text-sm">
                <th class="text-left pb-2">Month</th>
                <th class="text-left pb-2">Cheques</th>
                <th class="text-left pb-2">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in monthlyTrends" :key="row.month" class="border-t border-surface">
                <td class="py-2 text-surface-900">{{ row.month }}</td>
                <td class="py-2 text-surface-900">{{ row.count }}</td>
                <td class="py-2 text-surface-900">{{ 'PHP ' + Number(row.total).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-chart-bar text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>Analytics data will appear here</span>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Cheques</div>
          <table v-if="recentCheques.length" class="w-full">
            <thead>
              <tr class="text-muted-color font-medium text-sm">
                <th class="text-left pb-2">Reference</th>
                <th class="text-left pb-2">Claimant</th>
                <th class="text-left pb-2">Status</th>
                <th class="text-left pb-2">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cheque in recentCheques" :key="cheque.id" class="border-t border-surface">
                <td class="py-2 text-surface-900">{{ cheque.reference_code }}</td>
                <td class="py-2 text-surface-900">{{ cheque.claimant_name }}</td>
                <td class="py-2"><span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full" :class="cheque.status === 'claimed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">{{ cheque.status.replace(/_/g, ' ') }}</span></td>
                <td class="py-2 text-surface-900">{{ 'PHP ' + Number(cheque.amount).toLocaleString() }}</td>
              </tr>
            </tbody>
          </table>
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-inbox text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>No recent cheques</span>
          </div>
        </div>
      </div>
    </div>
</template>
