<script setup>
import { ref } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputTextarea from 'primevue/textarea'
import Checkbox from 'primevue/checkbox'

const props = defineProps({
  visible: { type: Boolean, default: false },
  submittedDocuments: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:visible', 'confirmed'])

const remarks = ref('')
const selectedDocs = ref([])

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
    <div class="flex flex-col gap-4">
      <div>
        <label class="block text-sm font-medium text-surface-700 mb-2">Reason for return <span class="text-red-500">*</span></label>
        <InputTextarea v-model="remarks" rows="3" placeholder="Explain what needs to be revised..." class="w-full" />
      </div>

      <div v-if="submittedDocuments.length">
        <label class="block text-sm font-medium text-surface-700 mb-2">Select documents that need resubmission</label>
        <div class="flex flex-col gap-2 max-h-60 overflow-y-auto border border-surface rounded-lg p-3">
          <div v-for="doc in submittedDocuments" :key="doc.id" class="flex items-center gap-3 py-1">
            <Checkbox
              v-model="selectedDocs"
              :inputId="'doc_' + doc.id"
              :value="doc.id"
            />
            <label :for="'doc_' + doc.id" class="text-sm text-surface-700 cursor-pointer flex items-center gap-2">
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
