<script setup>
import { ref, watch } from 'vue'

const props = defineProps({
  url: { type: String, default: null },
  title: { type: String, default: 'Document Viewer' },
  documents: { type: Array, default: () => [] },
  currentIndex: { type: Number, default: 0 },
})

const emit = defineEmits(['close', 'prev', 'next'])

const visible = ref(false)

watch(() => props.url, (val) => {
  visible.value = !!val
})

function close() {
  visible.value = false
  emit('close')
}

function prev() {
  emit('prev')
}

function next() {
  emit('next')
}

function isImage(url) {
  return /\.(jpe?g|png|gif|webp|bmp)$/i.test(url)
}
</script>

<template>
  <Teleport to="body">
    <div v-if="visible" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80" @click.self="close">
      <div class="fixed inset-4 bg-surface-0 rounded-xl shadow-xl flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 shrink-0 bg-black/10">
          <div class="flex items-center gap-3">
            <i class="pi pi-file text-white/80"></i>
            <h3 class="font-semibold text-white text-sm truncate max-w-80">{{ title }}</h3>
            <span v-if="documents.length" class="text-xs font-medium bg-white/20 text-white px-2 py-0.5 rounded-full">
              {{ currentIndex + 1 }} / {{ documents.length }}
            </span>
          </div>
          <div class="flex items-center gap-1">
            <button v-if="documents.length && currentIndex > 0" @click="prev"
              class="w-9 h-9 flex items-center justify-center rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors cursor-pointer border-none">
              <i class="pi pi-chevron-left text-sm"></i>
            </button>
            <button v-if="documents.length && currentIndex < documents.length - 1" @click="next"
              class="w-9 h-9 flex items-center justify-center rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors cursor-pointer border-none">
              <i class="pi pi-chevron-right text-sm"></i>
            </button>
            <button @click="close"
              class="w-9 h-9 flex items-center justify-center rounded-lg bg-white/20 text-white hover:bg-white/30 transition-colors cursor-pointer border-none ml-2">
              <i class="pi pi-times text-sm"></i>
            </button>
          </div>
        </div>
        <div v-if="url" class="flex-1 flex items-center justify-center p-4 bg-surface-50 dark:bg-surface-900 overflow-hidden">
          <img v-if="isImage(url)" :src="url" :alt="title" class="block max-w-full max-h-full object-contain" />
          <iframe v-else :src="url" class="w-full h-full rounded-lg border-0" style="min-height: 60vh;" />
        </div>
        <div v-else class="flex-1 flex items-center justify-center text-muted-color">
          <div class="text-center">
            <i class="pi pi-file text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <p>No document to display</p>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
