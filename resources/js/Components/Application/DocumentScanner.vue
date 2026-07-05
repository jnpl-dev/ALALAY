<script setup>
import { ref, watch, onBeforeUnmount } from 'vue'
import { useDocumentScanner } from '@/Composables/useDocumentScanner.js'

const props = defineProps({
  docName: { type: String, default: 'Document' },
  required: { type: Boolean, default: false },
  modelValue: { type: null, default: null },
})

const emit = defineEmits(['update:modelValue', 'captured', 'cleared'])

const {
  isScanning,
  isProcessing,
  previewUrl,
  capturedBlob,
  cameraError,
  hasCapture,
  submissionMethod,
  setVideoElement,
  startCamera,
  capture,
  retake,
  stopCamera,
  useFallback,
  clearCapture,
} = useDocumentScanner()

const videoRef = ref(null)
const fileInputRef = ref(null)
const showFallback = ref(false)
const confirmed = ref(false)

setVideoElement(videoRef.value)

const handleCaptureClick = () => {
  capture()
}

const handleUseThis = () => {
  if (capturedBlob.value) {
    const file = new File([capturedBlob.value], `${props.docName.replace(/\s+/g, '_')}.jpg`, {
      type: 'image/jpeg',
    })
    if (submissionMethod.value) {
      file._submissionMethod = submissionMethod.value
    }
    emit('update:modelValue', file)
    emit('captured', file)
    confirmed.value = true
  }
}

const handleRecapture = () => {
  confirmed.value = false
  clearCapture()
  retake()
}

const handleFallbackFile = (e) => {
  const file = e.target.files?.[0]
  if (file) {
    useFallback(file)
    e.target.value = ''
  }
}

const startScanner = () => {
  showFallback.value = false
  startCamera()
}

watch(videoRef, (el) => {
  if (el) setVideoElement(el)
})

onBeforeUnmount(() => {
  stopCamera()
  if (previewUrl.value) URL.revokeObjectURL(previewUrl.value)
})

const handleClear = () => {
  confirmed.value = false
  clearCapture()
  stopCamera()
  isScanning.value = false
  showFallback.value = false
  emit('cleared')
  emit('update:modelValue', null)
}
</script>

<template>
  <div class="document-scanner">
    <div v-if="required" class="flex mb-2">
      <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-600 font-medium">Required</span>
    </div>

    <div v-if="isProcessing" class="flex flex-col items-center justify-center py-12 border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-gray-800">
      <svg class="animate-spin h-8 w-8 text-gray-400 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
      </svg>
      <span class="text-sm text-gray-500">Processing document...</span>
    </div>

    <div v-else-if="hasCapture && previewUrl" class="border-2 border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
      <div class="relative bg-black">
        <img :src="previewUrl" alt="Captured document" class="w-full h-auto max-h-96 object-contain" />
      </div>
      <div v-if="submissionMethod === 'fallback_upload'" class="px-3 py-1.5 bg-yellow-50 dark:bg-yellow-900/20 border-t border-gray-200 dark:border-gray-700">
        <span class="text-xs text-yellow-700 dark:text-yellow-400">Uploaded from device storage</span>
      </div>
      <div class="flex gap-2 p-3 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800">
        <button
          v-if="!confirmed"
          @click="handleUseThis"
          class="flex-1 px-4 py-2 rounded-lg text-sm font-medium text-white border-none cursor-pointer"
          style="background-color: var(--p-primary-color, #3b82f6)"
        >
          Use This
        </button>
        <div
          v-else
          class="flex-1 flex items-center justify-center gap-1.5 px-4 py-2 rounded-lg bg-emerald-100 text-emerald-700 text-sm font-medium"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
          </svg>
          Document Captured
        </div>
        <button
          @click="handleRecapture"
          class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 border border-gray-300 dark:border-gray-600 cursor-pointer"
        >
          Recapture
        </button>
        <button
          @click="handleClear"
          class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-gray-700 text-red-600 dark:text-red-400 border border-gray-300 dark:border-gray-600 cursor-pointer"
        >
          Remove
        </button>
      </div>
    </div>

    <div v-else-if="isScanning && !cameraError" class="relative border-2 border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden bg-black">
      <video ref="videoRef" autoplay playsinline class="w-full h-auto max-h-96 object-contain" />
      <div class="absolute inset-0 pointer-events-none">
        <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
          <defs>
            <mask id="guide-mask">
              <rect width="100" height="100" fill="white" />
              <rect x="10" y="20" width="80" height="60" rx="3" fill="black" />
            </mask>
          </defs>
          <rect width="100" height="100" fill="rgba(0,0,0,0.45)" mask="url(#guide-mask)" />
        </svg>
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
          <div class="border-2 border-white/70 rounded-lg" style="width: 80%; height: 60%;"></div>
        </div>
      </div>
      <div class="absolute bottom-3 left-0 right-0 flex justify-center">
        <button
          @click="handleCaptureClick"
          class="w-14 h-14 rounded-full bg-white border-4 border-white/80 shadow-lg flex items-center justify-center cursor-pointer"
        >
          <div class="w-10 h-10 rounded-full border-2 border-gray-800"></div>
        </button>
      </div>
      <div class="absolute top-2 right-2">
        <button
          @click="stopCamera(); showFallback = true"
          class="px-2 py-1 text-xs rounded bg-black/50 text-white border-none cursor-pointer"
        >
          Cancel
        </button>
      </div>
    </div>

    <div v-else class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 bg-gray-50 dark:bg-gray-800">
      <div class="flex flex-col items-center gap-3">
        <svg class="w-8 h-8 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
        </svg>

        <button
          @click="startScanner"
          class="px-5 py-2 rounded-lg text-sm font-medium text-white border-none cursor-pointer"
          style="background-color: var(--p-primary-color, #3b82f6)"
        >
          Scan Document
        </button>

        <p v-if="cameraError" class="text-xs text-red-500 text-center max-w-xs">{{ cameraError }}</p>

        <div class="w-full flex items-center gap-2 text-xs text-gray-400">
          <span class="flex-1 h-px bg-gray-300 dark:bg-gray-600"></span>
          <span>or</span>
          <span class="flex-1 h-px bg-gray-300 dark:bg-gray-600"></span>
        </div>

        <button
          @click="fileInputRef?.click()"
          class="text-xs text-gray-500 dark:text-gray-400 underline hover:text-gray-700 dark:hover:text-gray-200 cursor-pointer bg-transparent border-none"
        >
          Can't use camera? Upload image instead
        </button>
        <input
          ref="fileInputRef"
          type="file"
          accept="image/jpeg,image/png"
          class="hidden"
          @change="handleFallbackFile"
        />
        <span class="text-xs text-gray-400">Accepted: JPG, PNG</span>
      </div>
    </div>
  </div>
</template>
