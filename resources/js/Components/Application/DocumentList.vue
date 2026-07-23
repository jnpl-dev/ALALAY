<script setup>
import Button from 'primevue/button'

defineProps({
  documents: { type: Array, default: () => [] },
  signedUrlRoute: { type: String, default: '' },
})

const emit = defineEmits(['view'])

function viewDocument(doc) {
  emit('view', doc)
}
</script>

<template>
  <div v-if="documents.length" class="space-y-2">
    <div v-for="doc in documents" :key="doc.id" class="flex items-center justify-between py-2 border-b border-surface last:border-b-0">
      <div class="flex items-center gap-3">
        <i class="pi pi-file text-muted-color"></i>
        <span class="text-sm text-surface-900">{{ doc.doc_name ?? doc.name ?? 'Document' }}</span>
      </div>
      <Button
        icon="pi pi-eye"
        label="View"
        size="small"
        @click="viewDocument(doc)"
        v-tooltip.left="'View document'"
      />
    </div>
  </div>
  <div v-else class="text-sm text-muted-color py-4 text-center">
    No documents uploaded
  </div>
</template>
