<script setup>
import Timeline from 'primevue/timeline'
import { formatDateTime } from '@/Utils/formatDate'

defineProps({
  reviews: { type: Array, default: () => [] },
})

function formatReviewerName(name) {
  if (!name) return 'System'
  const parts = name.trim().split(/\s+/)
  const lastName = parts.pop()
  let middleInit = ''
  let givenParts = [...parts]
  const last = parts[parts.length - 1]
  if (last && (last.endsWith('.') || last.length === 1)) {
    middleInit = last.endsWith('.') ? last : last.toUpperCase() + '.'
    givenParts = parts.slice(0, -1)
  }
  const givenInitials = givenParts.map(p => p.charAt(0).toUpperCase()).join('')
  return `${lastName}, ${givenInitials}${middleInit ? ' ' + middleInit : ''}`
}

const stageLabels = {
  aics_screening: 'AICS Screening',
  mswdo_review: 'MSWDO Review',
  assistance_coding: 'Assistance Coding',
  voucher_creation: 'Voucher Creation',
  accountant_review: 'Accountant Review',
  treasurer_review: 'Treasurer Review',
  mayors_approval: "Mayor's Approval",
}

const decisionLabels = {
  approved: 'Approved',
  coded: 'Coded',
  voucher_created: 'Created',
  returned: 'Returned',
  on_hold: 'On Hold',
  pending: 'Pending',
}

const decisionSeverity = (decision) => {
  if (decision === 'approved' || decision === 'coded' || decision === 'voucher_created') return 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300'
  if (decision === 'returned') return 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300'
  if (decision === 'on_hold') return 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300'
  return 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'
}

const dotColor = (decision) => {
  if (decision === 'approved' || decision === 'coded' || decision === 'voucher_created') return 'border-green-500 bg-green-400'
  if (decision === 'returned') return 'border-orange-500 bg-orange-400'
  if (decision === 'on_hold') return 'border-yellow-500 bg-yellow-400'
  return 'border-primary bg-primary'
}
</script>

<template>
  <div v-if="reviews.length">
    <Timeline :value="reviews" align="left" layout="vertical">
      <template #opposite="slotProps">
        <div class="text-xs text-muted-color leading-tight text-right pr-4 pt-0.5 whitespace-nowrap">
          <div>{{ formatReviewerName(slotProps.item.user_name) }}</div>
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
