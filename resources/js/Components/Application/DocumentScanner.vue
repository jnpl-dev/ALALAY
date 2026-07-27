<script setup>
import { ref, computed, watch, onMounted, onBeforeUnmount, nextTick, reactive } from 'vue'
import { useDocumentScanner } from '@/Composables/useDocumentScanner.js'

const props = defineProps({
  docName: { type: String, default: 'Document' },
  required: { type: Boolean, default: false },
  captureType: { type: String, default: 'single' },
  modelValue: { default: null },
})

const emit = defineEmits(['update:modelValue', 'captured', 'cleared'])

const {
  isScanning,
  isProcessing,
  isAnimating,
  previewUrl,
  rawPreviewUrl,
  capturedPages,
  cameraError,
  hasCapture,
  isComplete,
  isConfirmed: scannerConfirmed,
  pageLabel,
  detectedCorners,
  showCornerEditor,
  selectedFilter,
  filters,
  cropStage,
  rawCaptureWidth,
  rawCaptureHeight,
  captureWithFile,
  applyCrop,
  retakeLast,
  addPage,
  confirmPages,
  generatePdfBlob,
  reset,
  updateCorner,
  changeFilter,
} = useDocumentScanner(props.captureType)

const scanFileInputRef = ref(null)
const showOverlay = ref(false)
const cropImageRef = ref(null)
const cropContainerRef = ref(null)
const imgRect = reactive({ left: 0, top: 0, width: 0, height: 0 })

let activeHandleIndex = -1
let cachedRect = null

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

function startCapture() {
  showOverlay.value = true
  nextTick(() => scanFileInputRef.value?.click())
}

function handleFileCapture(e) {
  const file = e.target.files?.[0]
  e.target.value = ''
  if (!file) return
  captureWithFile(file)
}

function handleUseCapture() {
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

function handleRetakeLast() {
  retakeLast()
}

function handleRetake() {
  retakeLast()
}

function handleApplyCrop() {
  applyCrop()
}

function handleAddPage() {
  addPage()
}

function handleRecapture() {
  emit('update:modelValue', null)
  emit('cleared')
  reset()
  showOverlay.value = false
}

function handleClear() {
  emit('update:modelValue', null)
  emit('cleared')
  reset()
  showOverlay.value = false
}

function closeOverlay() {
  reset()
  showOverlay.value = false
}

function onCornerPointerDown(index, e) {
  e.preventDefault()
  e.target.setPointerCapture(e.pointerId)
  activeHandleIndex = index
  const img = cropImageRef.value
  if (img) cachedRect = img.getBoundingClientRect()
  document.documentElement.style.overflow = 'hidden'
  document.body.style.overflow = 'hidden'
  document.body.style.position = 'fixed'
  document.body.style.width = '100%'
}

function onPointerMove(e) {
  if (activeHandleIndex < 0 || !detectedCorners.value || !cachedRect) return
  const x = ((e.clientX - cachedRect.left) / cachedRect.width) * rawCaptureWidth.value
  const y = ((e.clientY - cachedRect.top) / cachedRect.height) * rawCaptureHeight.value
  updateCorner(activeHandleIndex, Math.round(x), Math.round(y))
}

function onPointerUp(e) {
  if (activeHandleIndex >= 0) {
    e.target.releasePointerCapture(e.pointerId)
    document.documentElement.style.overflow = ''
    document.body.style.overflow = ''
    document.body.style.position = ''
    document.body.style.width = ''
  }
  activeHandleIndex = -1
  cachedRect = null
}

const cropOverlayStyle = computed(() => ({
  left: imgRect.left + 'px',
  top: imgRect.top + 'px',
  width: imgRect.width + 'px',
  height: imgRect.height + 'px',
}))

function getCornerStyle(corner) {
  return {
    left: ((corner.x / Math.max(1, rawCaptureWidth.value)) * imgRect.width) + 'px',
    top: ((corner.y / Math.max(1, rawCaptureHeight.value)) * imgRect.height) + 'px',
  }
}

function updateImgRect() {
  const img = cropImageRef.value
  const container = cropContainerRef.value
  if (!img || !container) return
  const ib = img.getBoundingClientRect()
  const cb = container.getBoundingClientRect()
  imgRect.left = ib.left - cb.left
  imgRect.top = ib.top - cb.top
  imgRect.width = ib.width
  imgRect.height = ib.height
}

let resizeObserver = null

function setupResizeObserver() {
  const img = cropImageRef.value
  const container = cropContainerRef.value
  if (!img || !container) return
  if (resizeObserver) resizeObserver.disconnect()
  resizeObserver = new ResizeObserver(updateImgRect)
  resizeObserver.observe(img)
  resizeObserver.observe(container)
  updateImgRect()
}

watch(() => cropStage.value === 'cropping' && previewUrl.value, (isCropping) => {
  if (isCropping) {
    nextTick(() => {
      setupResizeObserver()
    })
  }
})

function forceBlockNativeScroll(e) {
  if (activeHandleIndex >= 0) {
    e.preventDefault()
    e.stopPropagation()
  }
}

onMounted(() => {
  if (cropStage.value === 'cropping' && previewUrl.value) {
    nextTick(setupResizeObserver)
  }
  window.addEventListener('touchmove', forceBlockNativeScroll, { passive: false })
  window.addEventListener('touchstart', forceBlockNativeScroll, { passive: false })
})

onBeforeUnmount(() => {
  if (resizeObserver) resizeObserver.disconnect()
  window.removeEventListener('touchmove', forceBlockNativeScroll)
  window.removeEventListener('touchstart', forceBlockNativeScroll)
})
</script>

<template>
  <div class="document-scanner">
    <div class="flex items-center justify-between mb-2">
      <span class="text-sm font-semibold text-surface-900">{{ docName }}</span>
      <span v-if="required" class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-700 font-medium">Required</span>
    </div>

    <div
      v-if="isConfirmed"
      class="border border-emerald-200 rounded-lg p-4 bg-emerald-50"
    >
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-emerald-100 flex items-center justify-center shrink-0">
          <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-emerald-700">Captured</p>
          <p v-if="captureType === 'multi'" class="text-xs text-emerald-500">{{ capturedPages.length }} pages</p>
        </div>
        <button @click="handleRecapture" class="text-sm font-medium text-emerald-600 hover:text-emerald-800 transition-colors cursor-pointer">Recapture</button>
      </div>
    </div>

    <div
      v-else
      class="border-2 border-dashed border-gray-300 rounded-lg p-6 bg-gray-50"
    >
      <div class="flex flex-col items-center gap-3">
        <svg class="w-10 h-10 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.16a15.53 15.53 0 01-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
          <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
        </svg>
        <button @click="startCapture" class="px-5 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-colors cursor-pointer">
          Scan Document
        </button>
        <span class="text-xs text-gray-400">Accepts images (converted to PDF)</span>
      </div>
    </div>

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showOverlay"
          class="fixed inset-0 z-[9999] bg-black flex flex-col"
        >

          <div
            v-if="isAnimating && !previewUrl"
            class="relative flex-1 bg-black flex items-center justify-center overflow-hidden"
          >
            <div class="text-white text-center">
              <svg class="w-12 h-12 text-emerald-400 animate-spin mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
              </svg>
              <p class="text-sm text-white/70">Processing document...</p>
            </div>
          </div>

          <div
            v-if="isProcessing"
            class="absolute inset-0 z-10 flex flex-col items-center justify-center bg-black/60"
          >
            <svg class="w-10 h-10 text-emerald-400 animate-spin mb-3" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <span class="text-white text-sm">Processing...</span>
          </div>

          <div
            v-if="!isProcessing && !isAnimating && !previewUrl"
            class="relative flex-1 flex items-center justify-center bg-black"
          >
            <input
              ref="scanFileInputRef"
              type="file"
              accept="image/*"
              capture="environment"
              class="hidden"
              @change="handleFileCapture"
            />
            <div class="flex flex-col items-center gap-6">
              <button
                @click="scanFileInputRef?.click()"
                class="w-24 h-24 rounded-full bg-emerald-500 hover:bg-emerald-400 transition-colors flex items-center justify-center cursor-pointer shadow-lg shadow-emerald-500/30"
              >
                <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.16a15.53 15.53 0 01-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 12.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0z" />
                </svg>
              </button>
              <div class="text-center">
                <p class="text-white text-lg font-semibold">Scan Document</p>
                <p class="text-white/50 text-sm mt-1">Opens your camera for a high-quality capture</p>
              </div>
              <button
                @click="closeOverlay"
                class="mt-4 text-sm text-white/40 hover:text-white/60 transition-colors cursor-pointer"
              >
                Cancel
              </button>
            </div>
          </div>

          <div
            v-if="cropStage === 'cropping' && previewUrl && !isScanning && !isProcessing && !isAnimating"
            class="relative flex-1 bg-gray-900 flex flex-col overflow-hidden"
          >
            <div
              ref="cropContainerRef"
              class="flex-1 min-h-0 flex items-center justify-center relative overflow-hidden crop-container"
              @pointermove="onPointerMove"
              @pointerup="onPointerUp"
              @pointerleave="onPointerUp"
            >
              <img ref="cropImageRef" :src="rawPreviewUrl || previewUrl" class="max-w-full max-h-full object-contain" />

              <div
                v-if="detectedCorners && showCornerEditor"
                class="absolute"
                :style="cropOverlayStyle"
              >
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                  <polygon
                    :points="detectedCorners.map(p => `${(p.x / Math.max(1, rawCaptureWidth)) * 100},${(p.y / Math.max(1, rawCaptureHeight)) * 100}`).join(' ')"
                    fill="rgba(16, 185, 129, 0.1)"
                    stroke="#10b981"
                    stroke-width="0.5"
                    stroke-dasharray="2,2"
                  />
                </svg>
              <div
                v-for="(corner, i) in detectedCorners"
                :key="i"
                class="absolute p-3 md:p-0 -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing flex items-center justify-center crop-handle"
                :style="getCornerStyle(corner)"
                @pointerdown="(e) => onCornerPointerDown(i, e)"
                @pointermove="onPointerMove"
                @pointerup="onPointerUp"
              >
                  <div class="w-full h-full rounded-full border-2 border-emerald-400 bg-emerald-500/30 flex items-center justify-center">
                    <div class="w-3 h-3 md:w-2 md:h-2 rounded-full bg-emerald-500"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="shrink-0 bg-gradient-to-t from-black/80 to-transparent pt-16 pb-6 px-6">
              <div class="flex gap-3">
                <button
                  @click="handleRetake"
                  class="flex-1 px-4 py-3 rounded-xl text-sm font-medium bg-white/20 text-white border border-white/30 backdrop-blur-sm cursor-pointer hover:bg-white/30 transition-colors"
                >
                  Retake
                </button>
                <button
                  @click="handleApplyCrop"
                  class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold text-white cursor-pointer bg-emerald-600 hover:bg-emerald-700 transition-colors"
                >
                  Crop & Enhance
                </button>
              </div>
            </div>

            <div class="absolute top-4 right-4 z-10">
              <button
                @click="closeOverlay"
                class="w-9 h-9 rounded-full bg-black/50 text-white flex items-center justify-center border-none cursor-pointer backdrop-blur-sm hover:bg-black/70 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div
            v-if="cropStage === 'preview' && previewUrl && !isScanning && !isProcessing && !isAnimating"
            class="relative flex-1 bg-gray-900 flex flex-col overflow-hidden"
          >
            <div class="flex-1 min-h-0 flex items-center justify-center relative overflow-hidden">
              <img :src="previewUrl" class="max-w-full max-h-full object-contain" />
            </div>

            <div class="shrink-0 bg-gradient-to-t from-black/80 to-transparent pt-16 pb-6 px-6">
              <div class="flex justify-center gap-2 mb-3">
                <button
                  v-for="f in filters"
                  :key="f"
                  @click="changeFilter(f)"
                  class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all cursor-pointer"
                  :class="selectedFilter === f
                    ? 'bg-emerald-600 text-white shadow-lg shadow-emerald-600/30'
                    : 'bg-white/20 text-white/80 hover:bg-white/30'"
                >
                  {{ f === 'original' ? 'Original' : f === 'enhanced' ? 'Enhanced' : 'B&W' }}
                </button>
              </div>
              <div class="flex gap-3">
                <button
                  @click="closeOverlay"
                  class="flex-1 px-4 py-3 rounded-xl text-sm font-medium bg-white/20 text-white border border-white/30 backdrop-blur-sm cursor-pointer hover:bg-white/30 transition-colors"
                >
                  Cancel
                </button>
                <button
                  @click="handleRetakeLast"
                  class="px-5 py-3 rounded-xl text-sm font-medium bg-white/20 text-white border border-white/30 backdrop-blur-sm cursor-pointer hover:bg-white/30 transition-colors"
                >
                  Retake
                </button>
                <button
                  v-if="captureType === 'single'"
                  @click="handleUseCapture"
                  class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold text-white cursor-pointer bg-emerald-600 hover:bg-emerald-700 transition-colors"
                >
                  Use This
                </button>
                <button
                  v-else-if="captureType === 'double'"
                  @click="capturedPages.length < 2 ? handleAddPage() : handleUseCapture()"
                  class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold text-white cursor-pointer bg-emerald-600 hover:bg-emerald-700 transition-colors"
                >
                  {{ capturedPages.length < 2 ? 'Capture Back Side' : 'Use Both' }}
                </button>
                <button
                  v-else-if="captureType === 'multi'"
                  @click="handleAddPage"
                  class="flex-1 px-4 py-3 rounded-xl text-sm font-semibold text-white cursor-pointer bg-emerald-600 hover:bg-emerald-700 transition-colors"
                >
                  Use This
                </button>
              </div>
            </div>

            <div class="absolute top-4 right-4 z-10">
              <button
                @click="closeOverlay"
                class="w-9 h-9 rounded-full bg-black/50 text-white flex items-center justify-center border-none cursor-pointer backdrop-blur-sm hover:bg-black/70 transition-colors"
              >
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
              </button>
            </div>
          </div>

          <div
            v-if="captureType === 'multi' && capturedPages.length > 0 && !isConfirmed && !previewUrl && !isProcessing && !isAnimating"
            class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-black/60"
          >
            <div class="bg-black/70 backdrop-blur-xl rounded-2xl px-8 py-6 flex flex-col items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-emerald-500/20 flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
              <span class="text-white text-sm font-medium">{{ capturedPages.length }} page(s) captured</span>
              <div class="flex gap-3">
                <button
                  @click="addPage"
                  class="px-5 py-2.5 rounded-xl text-sm font-semibold text-white cursor-pointer bg-emerald-600 hover:bg-emerald-700 transition-colors"
                >
                  + Add Page
                </button>
                <button
                  @click="handleUseCapture"
                  class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-white/20 text-white border border-white/30 cursor-pointer hover:bg-white/30 transition-colors"
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
.crop-container {
  touch-action: none;
  overscroll-behavior: contain;
}

.crop-handle {
  touch-action: none !important;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease-out;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

