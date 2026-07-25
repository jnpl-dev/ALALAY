<script setup>
import { ref, computed, watch, onBeforeUnmount, nextTick } from 'vue'
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
  setVideoElement,
  startCamera,
  capture: scannerCapture,
  retakeLast,
  addPage,
  confirmPages,
  generatePdfBlob,
  stopCamera,
  reset,
  updateCorner,
  recropWithCurrentCorners,
  changeFilter,
} = useDocumentScanner(props.captureType)

const videoRef = ref(null)
const fileInputRef = ref(null)
const showFallback = ref(false)
const showOverlay = ref(false)
const cornerDragIndex = ref(-1)
const scanLineRef = ref(null)

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
  showOverlay.value = true
  await nextTick()
  await startCamera('environment')
}

function handleCapture() {
  scannerCapture()
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

function handleAddPage() {
  addPage()
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

function onCornerPointerDown(index, e) {
  e.preventDefault()
  cornerDragIndex.value = index
}

function onPointerMove(e) {
  if (cornerDragIndex.value < 0 || !detectedCorners.value) return
  const overlay = e.currentTarget
  const rect = overlay.getBoundingClientRect()
  const x = ((e.clientX - rect.left) / rect.width) * overlay.scrollWidth
  const y = ((e.clientY - rect.top) / rect.height) * overlay.scrollHeight
  updateCorner(cornerDragIndex.value, Math.round(x), Math.round(y))
}

function onPointerUp() {
  cornerDragIndex.value = -1
}

onBeforeUnmount(() => {
  stopCamera()
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
        <p v-if="cameraError" class="text-xs text-red-500 text-center max-w-xs">{{ cameraError }}</p>
        <div class="flex items-center gap-3 w-full">
          <hr class="flex-1 border-gray-200">
          <span class="text-xs text-gray-400">or</span>
          <hr class="flex-1 border-gray-200">
        </div>
        <button @click="fileInputRef?.click()" class="text-sm font-medium text-emerald-600 hover:text-emerald-800 transition-colors cursor-pointer">
          Upload image instead
        </button>
        <input ref="fileInputRef" type="file" accept="image/*" class="hidden" @change="handleFallbackFile" />
        <span class="text-xs text-gray-400">Accepts images (converted to PDF)</span>
      </div>
    </div>

    <Teleport to="body">
      <Transition name="fade">
        <div
          v-if="showOverlay && (isScanning || isProcessing || isAnimating || previewUrl || (captureType === 'multi' && capturedPages.length > 0 && !isConfirmed))"
          class="fixed inset-0 z-[9999] bg-black flex flex-col"
        >

          <div
            v-if="isAnimating && !previewUrl"
            class="relative flex-1 bg-black flex items-center justify-center overflow-hidden"
          >
            <div class="text-white text-center">
              <div class="relative w-64 h-80 mx-auto border-2 border-white/20 rounded-lg overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-b from-emerald-500/10 via-transparent to-emerald-500/10"></div>
                <div
                  ref="scanLineRef"
                  class="absolute left-0 right-0 h-1 animate-scan-line"
                  style="background: linear-gradient(90deg, transparent, #10b981, #34d399, #10b981, transparent); box-shadow: 0 0 20px #10b981, 0 0 60px #10b981;"
                ></div>
              </div>
              <p class="mt-4 text-sm text-white/70">Scanning document...</p>
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
            v-if="isScanning && !isProcessing && !isAnimating"
            class="relative flex-1 flex items-center justify-center bg-black overflow-hidden"
          >
            <video
              ref="videoRef"
              autoplay
              playsinline
              class="absolute inset-0 w-full h-full object-cover"
            />

            <div class="absolute inset-0 pointer-events-none border-[3px] border-white/20 m-8 rounded-xl"></div>

            <div class="absolute top-14 left-0 right-0 flex justify-center pointer-events-none z-10">
              <span class="px-4 py-1.5 rounded-full bg-black/50 text-white text-xs font-medium backdrop-blur-sm">
                {{ stateLabel || docName }}
              </span>
            </div>

            <div class="absolute bottom-10 left-0 right-0 flex justify-center z-10">
              <button
                @click="handleCapture"
                class="w-16 h-16 rounded-full bg-white/90 border-4 border-white/60 shadow-xl flex items-center justify-center cursor-pointer active:scale-95 transition-transform"
              >
                <div class="w-11 h-11 rounded-full border-2 border-gray-800"></div>
              </button>
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
            v-if="previewUrl && !isScanning && !isProcessing && !isAnimating"
            class="relative flex-1 bg-gray-900 flex items-center justify-center"
            @pointermove="onPointerMove"
            @pointerup="onPointerUp"
            @pointerleave="onPointerUp"
          >
            <div class="relative max-w-full max-h-full">
              <img :src="previewUrl" class="max-w-full max-h-full object-contain" />

              <div
                v-if="detectedCorners && showCornerEditor"
                class="absolute inset-0"
                :style="{ width: '100%', height: '100%' }"
              >
                <svg class="w-full h-full" viewBox="0 0 100 100" preserveAspectRatio="none">
                  <polygon
                    :points="detectedCorners.map(p => `${(p.x / (capturedPages[capturedPages.length-1]?.width || 1)) * 100},${(p.y / (capturedPages[capturedPages.length-1]?.height || 1)) * 100}`).join(' ')"
                    fill="rgba(16, 185, 129, 0.1)"
                    stroke="#10b981"
                    stroke-width="0.5"
                    stroke-dasharray="2,2"
                  />
                </svg>
                <div
                  v-for="(corner, i) in detectedCorners"
                  :key="i"
                  class="absolute w-7 h-7 -translate-x-1/2 -translate-y-1/2 cursor-grab active:cursor-grabbing"
                  :style="{
                    left: ((corner.x / (capturedPages[capturedPages.length-1]?.width || 1)) * 100) + '%',
                    top: ((corner.y / (capturedPages[capturedPages.length-1]?.height || 1)) * 100) + '%',
                  }"
                  @pointerdown="(e) => onCornerPointerDown(i, e)"
                >
                  <div class="w-full h-full rounded-full border-2 border-emerald-400 bg-emerald-500/30 flex items-center justify-center">
                    <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
                  </div>
                </div>
              </div>
            </div>

            <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black/80 to-transparent pt-16 pb-6 px-6">
              <div v-if="showCornerEditor && detectedCorners" class="flex justify-center gap-2 mb-3">
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
            v-if="captureType === 'multi' && capturedPages.length > 0 && !isConfirmed && !previewUrl && !isScanning && !isProcessing && !isAnimating"
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
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease-out;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<style>
@keyframes scan-line {
  0% { top: 0; opacity: 0; }
  5% { opacity: 1; }
  90% { opacity: 1; }
  95% { top: 100%; opacity: 0; }
  100% { top: 100%; opacity: 0; }
}
.animate-scan-line {
  animation: scan-line 1.5s ease-in-out forwards;
}
</style>
