<script setup>
import { formatDateTime } from '@/Utils/formatDate'

defineProps({
  reviews: { type: Array, default: () => [] },
})
</script>

<template>
  <div v-if="reviews.length" class="space-y-4">
    <div v-for="review in reviews" :key="review.id" class="border-b border-surface pb-4 last:border-b-0 last:pb-0">
      <div class="flex items-center gap-2 mb-1">
        <i class="pi pi-history text-muted-color text-sm"></i>
        <span class="font-medium text-sm text-surface-900">{{ review.stage ?? review.module ?? 'Review' }}</span>
        <span class="text-xs text-muted-color">— {{ review.decision ?? review.action }}</span>
      </div>
      <p v-if="review.remarks" class="text-sm text-surface-700 ml-6">{{ review.remarks }}</p>
      <div class="flex items-center gap-2 text-xs text-muted-color ml-6 mt-1">
        <span>{{ review.user_name ?? review.reviewed_by ?? 'System' }}</span>
        <span>&middot;</span>
        <span>{{ formatDateTime(review.created_at) }}</span>
      </div>
    </div>
  </div>
  <div v-else class="text-sm text-muted-color py-4 text-center">
    No review entries yet
  </div>
</template>
