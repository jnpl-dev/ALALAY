<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import { formatCurrency } from '@/Utils/formatCurrency'

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
        <AppKpiCard title="Cheques" :value="chequesForProcessing" icon="pi pi-money-bill" color="info" :subtitle="acknowledgedThisMonth + ' acknowledged this month'" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Acknowledged" :value="acknowledgedThisMonth" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Total Amount" :value="formatCurrency(totalAmount)" icon="pi pi-calculator" color="purple" subtitle="total value" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Pending" :value="pendingCheques" icon="pi pi-clock" color="warn" subtitle="not yet acknowledged" />
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
                <td class="py-2 text-surface-900">{{ formatCurrency(row.total) }}</td>
              </tr>
            </tbody>
          </table>
          <AppEmptyState v-else icon="pi pi-chart-bar" message="Analytics data will appear here" />
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
                <td class="py-2 text-surface-900">{{ formatCurrency(cheque.amount) }}</td>
              </tr>
            </tbody>
          </table>
          <AppEmptyState v-else icon="pi pi-inbox" message="No recent cheques" />
        </div>
      </div>
    </div>
</template>
