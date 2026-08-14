<script setup>
import { ref, watch } from 'vue'
import Button from 'primevue/button'

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
    <Transition name="fade">
      <div v-if="visible" class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80" @click.self="close">
        <Transition name="scale">
          <div class="fixed inset-0 bg-surface-0 flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 shrink-0" style="background: var(--p-primary-color);">
              <div class="flex items-center gap-3">
                <i class="pi pi-file text-white/80"></i>
                <h3 class="font-semibold text-white text-sm truncate max-w-80">{{ title }}</h3>
                <span v-if="documents.length" class="text-xs font-medium bg-white/20 text-white px-2 py-0.5 rounded-full">
                  {{ currentIndex + 1 }} / {{ documents.length }}
                </span>
              </div>
              <div class="flex items-center gap-1">
                <Button
                  v-if="documents.length && currentIndex > 0"
                  icon="pi pi-chevron-left"
                  severity="contrast"
                  rounded
                  text
                  v-tooltip="'Previous'"
                  @click="prev"
                />
                <Button
                  v-if="documents.length && currentIndex < documents.length - 1"
                  icon="pi pi-chevron-right"
                  severity="contrast"
                  rounded
                  text
                  v-tooltip="'Next'"
                  @click="next"
                />
                <Button
                  icon="pi pi-times"
                  severity="contrast"
                  rounded
                  text
                  v-tooltip="'Close'"
                  @click="close"
                  class="ml-2"
                />
              </div>
            </div>
            <div v-if="url" class="flex-1 flex items-center justify-center bg-surface-50 overflow-hidden">
              <img v-if="isImage(url)" :src="url" :alt="title" class="block max-w-full max-h-full object-contain" />
              <iframe v-else :src="url" class="w-full h-full border-0" />
            </div>
            <div v-else class="flex-1 flex items-center justify-center text-muted-color">
              <div class="text-center">
                <i class="pi pi-file text-4xl mb-3 text-muted-color"></i>
                <p>No document to display</p>
              </div>
            </div>
          </div>
        </Transition>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease-out;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
.scale-enter-active {
  transition: transform 0.2s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.2s ease-out;
}
.scale-leave-active {
  transition: transform 0.15s ease-in, opacity 0.15s ease-in;
}
.scale-enter-from {
  transform: scale(0.95);
  opacity: 0;
}
.scale-leave-to {
  transform: scale(0.95);
  opacity: 0;
}
</style>
