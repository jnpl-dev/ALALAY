<script setup>
import { watch, toRef } from 'vue'
import { usePdfThumbnail } from '@/Composables/usePdfThumbnail'

const props = defineProps({
  doc: { type: Object, required: true },
})

const { thumbnail, loading, generate, reset } = usePdfThumbnail()

const signedUrl = toRef(() => props.doc?.signed_url)

watch(signedUrl, (url) => {
  if (url && props.doc?.mime_type === 'application/pdf') generate(url)
  else reset()
}, { immediate: true })
</script>

<template>
  <div v-if="doc.mime_type === 'application/pdf'" class="w-full h-full flex items-center justify-center bg-surface-50 dark:bg-surface-800 overflow-hidden">
    <img v-if="thumbnail" :src="thumbnail" :alt="doc.doc_name" class="w-full h-full object-cover" />
    <div v-else-if="loading" class="flex items-center justify-center">
      <i class="pi pi-spin pi-spinner text-2xl text-muted-color"></i>
    </div>
    <div v-else class="flex flex-col items-center gap-2 text-muted-color">
      <i class="pi pi-file-pdf text-4xl"></i>
      <span class="text-[10px] font-medium">PDF</span>
    </div>
  </div>
  <img v-else-if="doc.signed_url && doc.mime_type?.startsWith('image/')" :src="doc.signed_url" :alt="doc.doc_name"
    class="w-full h-full object-cover" loading="lazy" />
  <div v-else class="w-full h-full flex items-center justify-center bg-surface-50 dark:bg-surface-800">
    <i class="pi pi-file text-3xl text-muted-color"></i>
  </div>
</template>
