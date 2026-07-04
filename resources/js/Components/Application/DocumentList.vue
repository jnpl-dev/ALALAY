<script setup>
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
      <button
        @click="viewDocument(doc)"
        class="flex items-center gap-1.5 px-3 py-1 text-xs font-medium rounded-lg bg-primary text-white border-none cursor-pointer hover:bg-primary-600 transition-colors"
      >
        <i class="pi pi-eye"></i>
        View
      </button>
    </div>
  </div>
  <div v-else class="text-sm text-muted-color py-4 text-center">
    No documents uploaded
  </div>
</template>
