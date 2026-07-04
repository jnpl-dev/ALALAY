<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

defineProps({
  vouchersForReview: { type: Number, default: 0 },
  approvedThisMonth: { type: Number, default: 0 },
  totalAmount: { type: Number, default: 0 },
  disbursedThisMonth: { type: Number, default: 0 },
  monthlyTrends: { type: Array, default: () => [] },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  recentTransactions: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="Accountant Analytics" />
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Vouchers</span>
              <div class="text-surface-900 font-medium text-xl">{{ vouchersForReview }}</div>
            </div>
            <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-receipt text-blue-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">{{ approvedThisMonth }} </span>
          <span class="text-muted-color">approved this month</span>
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
              <span class="block text-muted-color font-medium mb-4">Budget</span>
              <div class="text-surface-900 font-medium text-xl">{{ formatCurrency(totalAmount) }}</div>
            </div>
            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-wallet text-purple-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">allocated funds</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Disbursed</span>
              <div class="text-surface-900 font-medium text-xl">{{ formatCurrency(disbursedThisMonth) }}</div>
            </div>
            <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-money-bill text-orange-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">total disbursed</span>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Budget Overview</div>
          <table v-if="monthlyTrends.length" class="w-full">
            <thead>
              <tr class="text-muted-color font-medium text-sm">
                <th class="text-left pb-2">Month</th>
                <th class="text-left pb-2">Applications</th>
                <th class="text-left pb-2">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in monthlyTrends" :key="row.month" class="border-t border-surface">
                <td class="py-2 text-surface-900">{{ row.month }}</td>
                <td class="py-2 text-surface-900">{{ row.count }}</td>
                <td class="py-2 text-surface-900">{{ formatCurrency(row.total) }}</td>
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
          <div class="font-semibold text-xl mb-4">Recent Transactions</div>
          <table v-if="recentTransactions.length" class="w-full">
            <thead>
              <tr class="text-muted-color font-medium text-sm">
                <th class="text-left pb-2">Reference</th>
                <th class="text-left pb-2">Claimant</th>
                <th class="text-left pb-2">Status</th>
                <th class="text-left pb-2">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="txn in recentTransactions" :key="txn.id" class="border-t border-surface">
                <td class="py-2 text-surface-900">{{ txn.reference_code }}</td>
                <td class="py-2 text-surface-900">{{ txn.claimant_name }}</td>
                <td class="py-2"><span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full" :class="txn.status === 'claimed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">{{ txn.status.replace(/_/g, ' ') }}</span></td>
                <td class="py-2 text-surface-900">{{ formatCurrency(txn.amount) }}</td>
              </tr>
            </tbody>
          </table>
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-inbox text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>No recent transactions</span>
          </div>
        </div>
      </div>
    </div>
</template>
