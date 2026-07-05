<script setup>
import { ref } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputTextarea from 'primevue/textarea'

const props = defineProps({
  visible: { type: Boolean, default: false },
  submittedDocuments: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:visible', 'confirmed'])

const remarks = ref('')
const selectedDocs = ref([])

function toggleDoc(docId) {
  const idx = selectedDocs.value.indexOf(docId)
  if (idx >= 0) selectedDocs.value.splice(idx, 1)
  else selectedDocs.value.push(docId)
}

function confirm() {
  emit('confirmed', {
    remarks: remarks.value,
    document_ids: selectedDocs.value,
  })
  remarks.value = ''
  selectedDocs.value = []
}

function close() {
  emit('update:visible', false)
}
</script>

<template>
  <Dialog
    :visible="visible"
    @update:visible="emit('update:visible', $event)"
    header="Return Application"
    :modal="true"
    :style="{ maxWidth: '600px', width: '90vw' }"
    class="p-fluid"
  >
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-surface-700 mb-2">Reason for return <span class="text-red-500">*</span></label>
        <InputTextarea v-model="remarks" rows="3" placeholder="Explain what needs to be revised..." class="w-full" />
      </div>

      <div v-if="submittedDocuments.length">
        <label class="block text-sm font-medium text-surface-700 mb-2">Select documents that need resubmission</label>
        <div class="space-y-2 max-h-60 overflow-y-auto border border-surface rounded-lg p-3">
          <div v-for="doc in submittedDocuments" :key="doc.id" class="flex items-center gap-3 py-1">
            <input
              type="checkbox"
              :checked="selectedDocs.includes(doc.id)"
              @change="toggleDoc(doc.id)"
              class="w-4 h-4 rounded border-surface-300 text-primary focus:ring-primary cursor-pointer flex-shrink-0"
            />
            <label class="text-sm text-surface-700 cursor-pointer flex items-center gap-2">
              <i class="pi pi-file text-muted-color text-xs"></i>
              {{ doc.doc_name ?? doc.name ?? 'Document' }}
            </label>
          </div>
        </div>
      </div>
    </div>

    <template #footer>
      <Button label="Cancel" severity="secondary" outlined @click="close" />
      <Button label="Return Application" severity="warn" @click="confirm" :disabled="!remarks.trim()" />
    </template>
  </Dialog>
</template>
