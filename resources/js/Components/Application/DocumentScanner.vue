<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { useDocumentScanner } from '@/Composables/useDocumentScanner.js'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import ProgressSpinner from 'primevue/progressspinner'

const SCANNER_PRESETS = {
  a4: {
    overlayAspectRatio: 3 / 4,
    overlayWidthPercent: 0.85,
    showRotateHint: false,
    cameraFacingMode: 'environment',
    label: 'Align document within the frame',
  },
  card: {
    overlayAspectRatio: 85.6 / 54,
    overlayWidthPercent: 0.80,
    showRotateHint: true,
    cameraFacingMode: 'environment',
    label: 'Rotate phone sideways \u2014 align ID card within the frame',
  },
  half_sheet: {
    overlayAspectRatio: 210 / 148,
    overlayWidthPercent: 0.88,
    showRotateHint: true,
    cameraFacingMode: 'environment',
    label: 'Rotate phone sideways \u2014 align Cedula within the frame',
  },
}

const props = defineProps({
  docName: { type: String, default: 'Document' },
  required: { type: Boolean, default: false },
  captureType: { type: String, default: 'single' },
  scannerSize: { type: String, default: 'a4' },
  modelValue: { default: null },
})

const emit = defineEmits(['update:modelValue', 'captured', 'cleared'])

const preset = computed(() => SCANNER_PRESETS[props.scannerSize] || SCANNER_PRESETS.a4)

const {
  isScanning,
  isProcessing,
  previewUrl,
  capturedPages,
  cameraError,
  hasCapture,
  isComplete,
  isConfirmed: scannerConfirmed,
  pageLabel,
  setVideoElement,
  startCamera: scannerStartCamera,
  capture: scannerCapture,
  retakeLast,
  addPage,
  confirmPages,
  generatePdfBlob,
  stopCamera,
  reset,
} = useDocumentScanner(props.captureType)

const videoRef = ref(null)
const fileInputRef = ref(null)
const showFallback = ref(false)
const showRotateHint = ref(false)
const showOverlay = ref(false)

setVideoElement(videoRef.value)

watch(videoRef, (el) => {
  if (el) setVideoElement(el)
})

const isConfirmed = computed(() => !!props.modelValue || scannerConfirmed.value)

const stateLabel = computed(() => {
  if (props.captureType === 'double') {
    if (capturedPages.value.length === 0) return 'Front Side'
    if (capturedPages.value.length === 1) return 'Back Side'
    return ''
  }
  if (props.captureType === 'multi') {
    return `Page ${capturedPages.value.length + 1}`
  }
  return ''
})

async function startCapture() {
  showFallback.value = false

  if (preset.value.showRotateHint && capturedPages.value.length === 0 && !isConfirmed.value) {
    showRotateHint.value = true
    await new Promise((resolve) => setTimeout(resolve, 2000))
    showRotateHint.value = false
  }

  showOverlay.value = true
  await scannerStartCamera(preset.value.cameraFacingMode)
}

function handleCapture() {
  scannerCapture()
}

function handleRetakeLast() {
  retakeLast()
}

function handleUseSingle() {
  confirmPages()
  const blob = generatePdfBlob()
  const file = new File([blob], `${props.docName}.pdf`, { type: 'application/pdf' })
  emit('update:modelValue', file)
  emit('captured', {
    file,
    preview: capturedPages.value[0]?.data || null,
    pageCount: capturedPages.value.length,
    pages: capturedPages.value.map(p => ({ ...p })),
  })
  showOverlay.value = false
}

function handleUseDouble() {
  if (capturedPages.value.length >= 2) {
    confirmPages()
    const blob = generatePdfBlob()
    const file = new File([blob], `${props.docName}.pdf`, { type: 'application/pdf' })
    emit('update:modelValue', file)
    emit('captured', {
      file,
      preview: capturedPages.value[0]?.data || null,
      pageCount: capturedPages.value.length,
      pages: capturedPages.value.map(p => ({ ...p })),
    })
    showOverlay.value = false
  } else {
    addPage()
  }
}

function handleUseMulti() {
  previewUrl.value = null
}

function handleFinishMulti() {
  confirmPages()
  const blob = generatePdfBlob()
  const file = new File([blob], `${props.docName}.pdf`, { type: 'application/pdf' })
  emit('update:modelValue', file)
  emit('captured', {
    file,
    preview: capturedPages.value[0]?.data || null,
    pageCount: capturedPages.value.length,
    pages: capturedPages.value.map(p => ({ ...p })),
  })
  showOverlay.value = false
}

function handleRecapture() {
  emit('update:modelValue', null)
  emit('cleared')
  reset()
  showOverlay.value = false
}

function handleFallbackFile(e) {
  const file = e.target.files?.[0]
  if (!file) return

  const img = new Image()
  img.onload = () => {
    const canvas = document.createElement('canvas')
    const MAX_WIDTH = 1200
    let targetWidth = img.width
    let targetHeight = img.height
    if (img.width > MAX_WIDTH) {
      targetWidth = MAX_WIDTH
      targetHeight = Math.round((img.height / img.width) * MAX_WIDTH)
    }
    canvas.width = targetWidth
    canvas.height = targetHeight
    canvas.getContext('2d').drawImage(img, 0, 0, targetWidth, targetHeight)

    const dataUrl = canvas.toDataURL('image/jpeg', 0.88)
    capturedPages.value.push({
      data: dataUrl,
      width: targetWidth,
      height: targetHeight,
    })

  confirmPages()
  const blob = generatePdfBlob()
  const pdfFile = new File([blob], `${props.docName}.pdf`, { type: 'application/pdf' })
  emit('update:modelValue', pdfFile)
  emit('captured', {
    file: pdfFile,
    preview: capturedPages.value[0]?.data || null,
    pageCount: capturedPages.value.length,
    pages: capturedPages.value.map(p => ({ ...p })),
  })
  showOverlay.value = false
}
  img.src = URL.createObjectURL(file)
  e.target.value = ''
}

function handleClear() {
  emit('update:modelValue', null)
  emit('cleared')
  reset()
  showOverlay.value = false
}

function closeOverlay() {
  stopCamera()
  reset()
  showOverlay.value = false
}

onBeforeUnmount(() => {
  stopCamera()
})
</script>

<template>
  <div class="document-scanner">
    <!-- Inline: doc name + requirement badge -->
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-semibold text-surface-900">{{ docName }}</span>
      <span v-if="required" class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-medium">Required</span>
    </div>

    <!-- Confirmed -->
    <div
      v-if="isConfirmed"
      class="border border-emerald-200 rounded-lg p-4 bg-emerald-50"
    >
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
          <i class="pi pi-check text-emerald-600 text-lg"></i>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-emerald-700">Captured</p>
          <p v-if="captureType === 'multi'" class="text-xs text-emerald-500">{{ capturedPages.length }} pages</p>
        </div>
        <Button label="Recapture" link @click="handleRecapture" />
      </div>
    </div>

    <!-- Idle -->
    <div
      v-else
      class="border-2 border-dashed border-surface rounded-lg p-6 bg-surface-50"
    >
      <div class="flex flex-col items-center gap-3">
        <i class="pi pi-camera text-3xl text-muted-color"></i>

        <Button label="Scan Document" icon="pi pi-camera" @click="startCapture" />

        <p v-if="cameraError" class="text-xs text-red-500 text-center max-w-xs">{{ cameraError }}</p>

        <Divider align="center" class="w-full">
          <span class="text-xs text-muted-color">or</span>
        </Divider>

        <Button label="Upload image instead" link @click="fileInputRef?.click()" />
        <input
          ref="fileInputRef"
          type="file"
          accept="image/*"
          class="hidden"
          @change="handleFallbackFile"
        />
        <span class="text-xs text-muted-color">Accepts images (converted to PDF)</span>
      </div>
    </div>

    <!-- Full-screen camera overlay -->
    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showOverlay && (isScanning || isProcessing || previewUrl || (captureType === 'multi' && capturedPages.length > 0 && !isConfirmed))"
          class="fixed inset-0 z-[9999] bg-black flex flex-col"
        >
        <!-- Rotate hint -->
        <div
          v-if="showRotateHint"
          class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/80"
        >
          <i class="pi pi-refresh text-5xl text-white/70 mb-4 animate-spin"></i>
          <p class="text-white/80 text-sm max-w-xs text-center">Rotate your phone sideways to scan this document.</p>
        </div>

        <!-- Processing state -->
        <div
          v-if="isProcessing"
          class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/60"
        >
          <ProgressSpinner strokeWidth="4" class="w-10 h-10 mb-3" />
          <span class="text-white text-sm">Processing...</span>
        </div>

        <!-- Camera active -->
        <div
          v-if="isScanning && !isProcessing"
          class="relative flex-1 flex items-center justify-center bg-black overflow-hidden"
        >
          <video
            ref="videoRef"
            autoplay
            playsinline
            class="absolute inset-0 w-full h-full object-cover"
          />

          <!-- Overlay mask -->
          <div class="absolute inset-0 pointer-events-none">
            <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
              <defs>
                <mask id="guide-mask">
                  <rect width="100" height="100" fill="white" />
                  <rect
                    :x="(100 - preset.overlayWidthPercent * 100) / 2"
                    :y="(100 - (preset.overlayWidthPercent * 100) / preset.overlayAspectRatio) / 2"
                    :width="preset.overlayWidthPercent * 100"
                    :height="(preset.overlayWidthPercent * 100) / preset.overlayAspectRatio"
                    rx="3"
                    fill="black"
                  />
                </mask>
              </defs>
              <rect width="100" height="100" fill="rgba(0,0,0,0.45)" mask="url(#guide-mask)" />
            </svg>
          </div>

          <!-- State label (e.g. "Front Side", "Page 1") -->
          <div class="absolute top-14 left-0 right-0 flex justify-center pointer-events-none z-10">
            <span class="px-4 py-1.5 rounded-full bg-black/50 text-white text-xs font-medium backdrop-blur-sm">
              {{ stateLabel || docName }}
            </span>
          </div>

          <!-- Capture button -->
          <div class="absolute bottom-10 left-0 right-0 flex justify-center z-10">
            <button
              @click="handleCapture"
              v-tooltip.top="'Capture'"
              class="w-16 h-16 rounded-full bg-white/90 border-4 border-white/60 shadow-xl flex items-center justify-center cursor-pointer active:scale-95 transition-transform"
            >
              <div class="w-11 h-11 rounded-full border-2 border-gray-800"></div>
            </button>
          </div>

          <!-- Cancel button -->
          <div class="absolute top-4 right-4 z-10">
            <button
              @click="closeOverlay"
              v-tooltip.top="'Close'"
              class="w-9 h-9 rounded-full bg-black/50 text-white flex items-center justify-center border-none cursor-pointer backdrop-blur-sm"
            >
              <i class="pi pi-times"></i>
            </button>
          </div>
        </div>

        <!-- Preview (captured image with actions) -->
        <div
          v-if="previewUrl && !isScanning && !isProcessing"
          class="relative flex-1 bg-black flex items-center justify-center"
        >
          <img :src="previewUrl" class="max-w-full max-h-full object-contain" />

          <!-- Bottom action bar -->
          <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/70 to-transparent pt-16 pb-6 px-6">
            <div class="flex gap-3">
              <button
                @click="closeOverlay"
                v-tooltip.top="'Cancel'"
                class="flex-1 px-4 py-3 rounded-xl text-sm font-medium bg-white/20 text-white border border-white/30 backdrop-blur-sm cursor-pointer"
              >
                Cancel
              </button>
              <button
                @click="handleRetakeLast"
                v-tooltip.top="'Retake'"
                class="px-5 py-3 rounded-xl text-sm font-medium bg-white/20 text-white border border-white/30 backdrop-blur-sm cursor-pointer"
              >
                Retake
              </button>
              <button
                v-if="captureType === 'single'"
                @click="handleUseSingle"
                v-tooltip.top="'Use this capture'"
                class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold text-white cursor-pointer"
                style="background-color: var(--p-primary-color, #059669)"
              >
                Use This
              </button>
              <button
                v-if="captureType === 'double'"
                @click="handleUseDouble"
                v-tooltip.top="capturedPages.length < 2 ? 'Capture back side' : 'Use this capture'"
                class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold text-white cursor-pointer"
                style="background-color: var(--p-primary-color, #059669)"
              >
                {{ capturedPages.length < 2 ? 'Capture Back Side' : 'Use This' }}
              </button>
              <button
                v-if="captureType === 'multi'"
                @click="handleUseMulti"
                v-tooltip.top="'Use this page'"
                class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold text-white cursor-pointer"
                style="background-color: var(--p-primary-color, #059669)"
              >
                Use This
              </button>
            </div>
          </div>

          <!-- Close top-right -->
          <div class="absolute top-4 right-4 z-10">
            <button
              @click="closeOverlay"
              v-tooltip.top="'Close'"
              class="w-9 h-9 rounded-full bg-black/50 text-white flex items-center justify-center border-none cursor-pointer backdrop-blur-sm"
            >
              <i class="pi pi-times"></i>
            </button>
          </div>
        </div>

        <!-- Multi capture: add another page prompt (shown after Use This in multi mode) -->
        <div
          v-if="captureType === 'multi' && capturedPages.length > 0 && !isConfirmed && !previewUrl && !isScanning && !isProcessing"
          class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-black/60"
        >
          <div class="bg-black/70 backdrop-blur-xl rounded-2xl px-8 py-6 flex flex-col items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
              <i class="pi pi-check text-emerald-400 text-xl"></i>
            </div>
            <span class="text-white text-sm font-medium">{{ capturedPages.length }} page(s) captured</span>
            <div class="flex gap-3">
              <button
                @click="addPage"
                class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white cursor-pointer"
                style="background-color: var(--p-primary-color, #059669)"
              >
                + Add Page
              </button>
              <button
                @click="handleFinishMulti"
                class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-white/20 text-white border border-white/30 cursor-pointer"
              >
                Done &mdash; {{ capturedPages.length }} pages
              </button>
            </div>
          </div>
        </div>
      </div>
      </Transition>
    </Teleport>
  </div>
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
</style>
