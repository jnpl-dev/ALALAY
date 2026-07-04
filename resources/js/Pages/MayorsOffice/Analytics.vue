<script setup>
import { Head } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

defineProps({
  totalApplications: { type: Number, default: 0 },
  approvedThisMonth: { type: Number, default: 0 },
  totalDisbursed: { type: Number, default: 0 },
  beneficiariesServed: { type: Number, default: 0 },
  monthlyTrends: { type: Array, default: () => [] },
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
  applicationsByCategory: { type: Array, default: () => [] },
})
</script>

<template>
  <Head title="Mayor's Office Analytics" />
    <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Applications" :value="totalApplications" icon="pi pi-file" color="info" :subtitle="approvedThisMonth + ' approved this month'" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Approved" :value="approvedThisMonth" icon="pi pi-check-circle" color="success" subtitle="this month" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Disbursed" :value="formatCurrency(totalDisbursed)" icon="pi pi-money-bill" color="purple" subtitle="total disbursed" />
      </div>
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <AppKpiCard title="Beneficiaries" :value="beneficiariesServed" icon="pi pi-users" color="warn" subtitle="served this month" />
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Program Overview</div>
          <table v-if="monthlyTrends.length" class="w-full">
            <thead>
              <tr class="text-muted-color font-medium text-sm">
                <th class="text-left pb-2">Month</th>
                <th class="text-left pb-2">Applications</th>
                <th class="text-left pb-2">Disbursed</th>
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
          <div class="font-semibold text-xl mb-4">Reports by Category</div>
          <table v-if="applicationsByCategory.length" class="w-full">
            <thead>
              <tr class="text-muted-color font-medium text-sm">
                <th class="text-left pb-2">Category</th>
                <th class="text-left pb-2">Applications</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="cat in applicationsByCategory" :key="cat.category_name" class="border-t border-surface">
                <td class="py-2 text-surface-900">{{ cat.category_name }}</td>
                <td class="py-2 text-surface-900">{{ cat.count }}</td>
              </tr>
            </tbody>
          </table>
          <AppEmptyState v-else icon="pi pi-inbox" message="No recent reports" />
        </div>
      </div>
    </div>
</template>
