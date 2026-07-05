<script setup>
import Timeline from 'primevue/timeline'
import { formatDateTime } from '@/Utils/formatDate'

defineProps({
  reviews: { type: Array, default: () => [] },
})

const stageLabels = {
  aics_screening: 'AICS Screening',
  mswdo_review: 'MSWDO Review',
  voucher_creation: 'Voucher Creation',
  accountant_review: 'Accountant Review',
  treasurer_review: 'Treasurer Review',
  mayors_approval: "Mayor's Approval",
}

const decisionLabels = {
  approved: 'Approved',
  coded: 'Coded',
  returned: 'Returned',
  pending: 'Pending',
}

const decisionSeverity = (decision) => {
  if (decision === 'approved' || decision === 'coded') return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
  if (decision === 'returned') return 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300'
  return 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
}

const dotColor = (decision) => {
  if (decision === 'approved' || decision === 'coded') return 'border-green-500 bg-green-400'
  if (decision === 'returned') return 'border-orange-500 bg-orange-400'
  return 'border-primary bg-primary'
}
</script>

<template>
  <div v-if="reviews.length">
    <Timeline :value="reviews" align="left" layout="vertical">
      <template #opposite="slotProps">
        <div class="text-xs text-muted-color leading-tight text-right pr-4 pt-0.5">
          <div>{{ slotProps.item.user_name ?? 'System' }}</div>
          <div>{{ formatDateTime(slotProps.item.created_at) }}</div>
        </div>
      </template>
      <template #marker="slotProps">
        <span class="w-3.5 h-3.5 rounded-full border-2 block" :class="dotColor(slotProps.item.decision)" />
      </template>
      <template #content="slotProps">
        <div class="flex items-center gap-2 mb-1">
          <span class="text-xs font-medium text-muted-color">{{ stageLabels[slotProps.item.stage] ?? slotProps.item.stage }}</span>
          <span class="text-xs font-semibold px-2 py-0.5 rounded-full" :class="decisionSeverity(slotProps.item.decision)">
            {{ decisionLabels[slotProps.item.decision] ?? slotProps.item.decision }}
          </span>
        </div>
        <p v-if="slotProps.item.remarks" class="text-xs text-muted-color leading-relaxed">{{ slotProps.item.remarks }}</p>
      </template>
    </Timeline>
  </div>
  <div v-else class="text-sm text-muted-color py-4 text-center">
    No review entries yet
  </div>
</template>
