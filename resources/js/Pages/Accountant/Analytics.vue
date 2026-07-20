<script setup>
import { Head, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import Skeleton from 'primevue/skeleton'
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

defineProps({
  analyticsData: { type: Object, default: () => ({}) },
})
</script>

<template>
  <Head title="Accountant Analytics" />
  <Deferred data="analyticsData">
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Vouchers" :value="analyticsData?.vouchersForReview ?? 0" icon="pi pi-receipt" color="info" :subtitle="(analyticsData?.approvedThisMonth ?? 0) + ' approved this month'" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Approved" :value="analyticsData?.approvedThisMonth ?? 0" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Budget" :value="formatCurrency(analyticsData?.totalAmount ?? 0)" icon="pi pi-wallet" color="purple" subtitle="allocated funds" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Disbursed" :value="formatCurrency(analyticsData?.disbursedThisMonth ?? 0)" icon="pi pi-money-bill" color="warn" subtitle="total disbursed" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Budget Overview</div>
          <table v-if="analyticsData?.monthlyTrends?.length" class="w-full">
            <thead>
              <tr class="text-muted-color font-medium text-sm">
                <th class="text-left pb-2">Month</th>
                <th class="text-left pb-2">Applications</th>
                <th class="text-left pb-2">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in analyticsData.monthlyTrends" :key="row.month" class="border-t border-surface">
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
          <div class="font-semibold text-xl mb-4">Recent Transactions</div>
          <table v-if="analyticsData?.recentTransactions?.length" class="w-full">
            <thead>
              <tr class="text-muted-color font-medium text-sm">
                <th class="text-left pb-2">Reference</th>
                <th class="text-left pb-2">Claimant</th>
                <th class="text-left pb-2">Status</th>
                <th class="text-left pb-2">Amount</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="txn in analyticsData.recentTransactions" :key="txn.id" class="border-t border-surface">
                <td class="py-2 text-surface-900">{{ txn.reference_code }}</td>
                <td class="py-2 text-surface-900">{{ txn.claimant_name }}</td>
                <td class="py-2"><span class="inline-block px-2 py-0.5 text-xs font-medium rounded-full" :class="txn.status === 'claimed' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'">{{ txn.status.replace(/_/g, ' ') }}</span></td>
                <td class="py-2 text-surface-900">{{ formatCurrency(txn.amount) }}</td>
              </tr>
            </tbody>
          </table>
          <AppEmptyState v-else icon="pi pi-inbox" message="No recent transactions" />
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
