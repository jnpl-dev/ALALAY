<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  url: { type: String, default: null },
  title: { type: String, default: 'Document Viewer' },
})

const emit = defineEmits(['close'])

const visible = ref(false)

watch(() => props.url, (val) => {
  visible.value = !!val
})

function close() {
  visible.value = false
  emit('close')
}

function isImage(url) {
  return /\.(jpe?g|png|gif|webp|bmp)$/i.test(url)
}
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60" @click.self="close">
      <div class="bg-surface-0 rounded-xl shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-surface">
          <h3 class="font-semibold text-surface-900 text-sm truncate mr-4">{{ title }}</h3>
          <button @click="close" class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-surface-100 transition-colors cursor-pointer border-none bg-transparent text-surface-500">
            <i class="pi pi-times"></i>
          </button>
        </div>
        <div v-if="url" class="flex-1 overflow-auto p-6 flex items-center justify-center">
          <img v-if="isImage(url)" :src="url" :alt="title" class="max-w-full max-h-[70vh] object-contain rounded-lg" />
          <iframe v-else :src="url" class="w-full h-[70vh] rounded-lg border-0" />
        </div>
        <div v-else class="flex-1 flex items-center justify-center text-muted-color py-12">
          <div class="text-center">
            <i class="pi pi-file text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <p>No document to display</p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
