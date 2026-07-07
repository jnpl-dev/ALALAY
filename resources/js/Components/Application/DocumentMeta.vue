<script setup>
import { formatDateTime } from '@/Utils/formatDate'

defineProps({
  uploadedBy: { type: String, default: null },
  uploadedAt: { type: String, default: null },
  pageCount: { type: Number, default: null },
  fileSize: { type: String, default: null },
  version: { type: Number, default: null },
  returnedBy: { type: String, default: null },
  returnedAt: { type: String, default: null },
  returnRemarks: { type: String, default: null },
})
</script>

<template>
  <div class="bg-surface-50 rounded-lg border border-surface p-4 space-y-2 text-sm">
    <div v-if="uploadedBy" class="flex items-center justify-between">
      <span class="text-muted-color">Uploaded by</span>
      <span class="font-medium text-surface-900">{{ uploadedBy }}</span>
    </div>
    <div v-if="uploadedAt" class="flex items-center justify-between">
      <span class="text-muted-color">Date</span>
      <span class="font-medium text-surface-900">{{ formatDateTime(uploadedAt) }}</span>
    </div>
    <div v-if="pageCount" class="flex items-center justify-between">
      <span class="text-muted-color">Pages</span>
      <span class="font-medium text-surface-900">{{ pageCount }}</span>
    </div>
    <div v-if="fileSize" class="flex items-center justify-between">
      <span class="text-muted-color">File size</span>
      <span class="font-medium text-surface-900">{{ fileSize }}</span>
    </div>
    <div v-if="version" class="flex items-center justify-between">
      <span class="text-muted-color">Version</span>
      <span class="font-medium text-surface-900">v{{ version }}</span>
    </div>

    <template v-if="returnedBy">
      <hr class="border-surface" />
      <div class="text-orange-600 text-xs font-medium flex items-center gap-1">
        <i class="pi pi-exclamation-triangle"></i>
        <span>This document was returned</span>
      </div>
      <div class="flex items-center justify-between">
        <span class="text-muted-color">Returned by</span>
        <span class="font-medium text-surface-900">{{ returnedBy }}</span>
      </div>
      <div v-if="returnedAt" class="flex items-center justify-between">
        <span class="text-muted-color">Returned on</span>
        <span class="font-medium text-surface-900">{{ formatDateTime(returnedAt) }}</span>
      </div>
      <div v-if="returnRemarks" class="flex items-start justify-between gap-2">
        <span class="text-muted-color shrink-0">Reason</span>
        <span class="font-medium text-surface-900 text-right">{{ returnRemarks }}</span>
      </div>
    </template>
  </div>
</template>
