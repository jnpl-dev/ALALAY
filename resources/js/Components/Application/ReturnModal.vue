<script setup>
import { ref, computed } from 'vue'
import Dialog from 'primevue/dialog'
import Button from 'primevue/button'
import InputTextarea from 'primevue/inputtextarea'

const props = defineProps({
  visible: { type: Boolean, default: false },
  requiredDocuments: { type: Array, default: () => [] },
})

const emit = defineEmits(['update:visible', 'confirmed'])

const remarks = ref('')
const selectedDocs = ref([])

const allMandatory = computed(() => props.requiredDocuments.filter(d => d.is_mandatory ?? true))

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
    :style="{ maxWidth: '500px' }"
    class="p-fluid"
  >
    <div class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-surface-700 mb-2">Reason for return</label>
        <InputTextarea v-model="remarks" rows="3" placeholder="Explain what needs to be revised..." class="w-full" />
      </div>

      <div v-if="requiredDocuments.length">
        <label class="block text-sm font-medium text-surface-700 mb-2">Documents to re-capture</label>
        <div class="space-y-2">
          <div v-for="doc in requiredDocuments" :key="doc.id" class="flex items-center gap-2">
            <input
              type="checkbox"
              :checked="selectedDocs.includes(doc.id)"
              @change="toggleDoc(doc.id)"
              class="w-4 h-4 rounded border-surface-300 text-primary focus:ring-primary cursor-pointer"
            />
            <label class="text-sm text-surface-700 cursor-pointer">{{ doc.doc_name ?? doc.name ?? 'Document' }}</label>
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
