import { ref, computed } from 'vue'
import { jsPDF } from 'jspdf'
import { cannyEdgeDetection, findDocumentCorners, correctPerspective, magicFilter, downscaleCanvas } from '@/Utils/imageProcessing.js'

export function useDocumentScanner(captureType = 'single') {
  const isScanning = ref(false)
  const isProcessing = ref(false)
  const isAnimating = ref(false)
  const previewUrl = ref(null)
  const capturedPages = ref([])
  const cameraError = ref(null)
  const isConfirmed = ref(false)
  const hasCapture = computed(() => capturedPages.value.length > 0)
  const isComplete = computed(() => {
    if (captureType === 'single') return capturedPages.value.length === 1
    if (captureType === 'double') return capturedPages.value.length === 2
    return capturedPages.value.length > 0
  })
  const pageLabel = ref('')

  const detectedCorners = ref(null)
  const showCornerEditor = ref(false)
  const selectedFilter = ref('enhanced')
  const filters = ['original', 'enhanced', 'bw']

  let stream = null
  let videoElement = null
  let rawCaptureCanvas = null

  function setVideoElement(el) {
    videoElement = el
  }

  async function startCamera(facingMode = 'environment') {
    cameraError.value = null
    isScanning.value = true
    isConfirmed.value = false

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode, width: { ideal: 1920 }, height: { ideal: 1080 } },
        audio: false,
      })
      if (videoElement) {
        videoElement.srcObject = stream
        await videoElement.play()
      }
    } catch (err) {
      isScanning.value = false
      cameraError.value = err.message || 'Camera access denied or unavailable'
      stream = null
    }
  }

  function downscale(sourceCanvas, maxWidth = 1200) {
    const scale = Math.min(1, maxWidth / sourceCanvas.width)
    const dest = document.createElement('canvas')
    dest.width = sourceCanvas.width * scale
    dest.height = sourceCanvas.height * scale
    dest.getContext('2d').drawImage(sourceCanvas, 0, 0, dest.width, dest.height)
    return dest
  }

  function processCapture(srcCanvas) {
    const down = downscale(srcCanvas)
    rawCaptureCanvas = down

    const { edges, width, height } = cannyEdgeDetection(
      down.getContext('2d').getImageData(0, 0, down.width, down.height)
    )

    const corners = findDocumentCorners(edges, width, height)

    let resultCanvas
    if (corners) {
      detectedCorners.value = corners
      showCornerEditor.value = true
      const perspCanvas = correctPerspective(down, corners, 1240, 1754)
      resultCanvas = perspCanvas
    } else {
      detectedCorners.value = null
      showCornerEditor.value = false
      resultCanvas = down
    }

    const enhancedCanvas = selectedFilter.value === 'original'
      ? resultCanvas
      : selectedFilter.value === 'bw'
        ? applyBW(resultCanvas)
        : magicFilter(resultCanvas)

    const dataUrl = canvasToDataUrl(enhancedCanvas)

    capturedPages.value.push({
      data: dataUrl,
      width: enhancedCanvas.width,
      height: enhancedCanvas.height,
    })

    previewUrl.value = dataUrl
    rawCaptureCanvas = null
  }

  function applyBW(canvas) {
    const ctx = canvas.getContext('2d')
    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)
    const { data, width, height } = imageData
    const gray = new Uint8Array(width * height)
    for (let i = 0; i < data.length; i += 4) {
      gray[i / 4] = Math.round(0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2])
    }

    let minVal = 255, maxVal = 0
    for (let i = 0; i < gray.length; i++) {
      if (gray[i] < minVal) minVal = gray[i]
      if (gray[i] > maxVal) maxVal = gray[i]
    }
    const range = maxVal - minVal
    const stretched = new Uint8Array(gray.length)
    if (range > 0) {
      for (let i = 0; i < gray.length; i++) {
        stretched[i] = Math.round(((gray[i] - minVal) / range) * 255)
      }
    } else {
      stretched.set(gray)
    }

    const blockSize = 40
    const C = 10
    const integral = new Uint32Array((width + 1) * (height + 1))
    for (let y = 0; y < height; y++) {
      for (let x = 0; x < width; x++) {
        const idx = (y + 1) * (width + 1) + (x + 1)
        const val = stretched[y * width + x]
        integral[idx] = val
          + integral[(y) * (width + 1) + (x + 1)]
          + integral[(y + 1) * (width + 1) + (x)]
          - integral[(y) * (width + 1) + (x)]
      }
    }

    const output = new Uint8ClampedArray(data.length)
    for (let y = 0; y < height; y++) {
      for (let x = 0; x < width; x++) {
        const x1 = Math.max(0, x - Math.floor(blockSize / 2))
        const y1 = Math.max(0, y - Math.floor(blockSize / 2))
        const x2 = Math.min(width - 1, x + Math.floor(blockSize / 2))
        const y2 = Math.min(height - 1, y + Math.floor(blockSize / 2))
        const count = (x2 - x1 + 1) * (y2 - y1 + 1)
        const sum = integral[(y2 + 1) * (width + 1) + (x2 + 1)]
          - integral[(y1) * (width + 1) + (x2 + 1)]
          - integral[(y2 + 1) * (width + 1) + (x1)]
          + integral[(y1) * (width + 1) + (x1)]
        const mean = sum / count
        const idx = (y * width + x) * 4
        const val = stretched[y * width + x] < (mean - C) ? 0 : 255
        output[idx] = val
        output[idx + 1] = val
        output[idx + 2] = val
        output[idx + 3] = 255
      }
    }

    const outCanvas = document.createElement('canvas')
    outCanvas.width = width
    outCanvas.height = height
    outCanvas.getContext('2d').putImageData(new ImageData(output, width, height), 0, 0)
    return outCanvas
  }

  function canvasToDataUrl(canvas) {
    return canvas.toDataURL('image/jpeg', 0.88)
  }

  async function capture() {
    if (!videoElement || !stream) return
    isProcessing.value = true
    previewUrl.value = null

    const srcCanvas = document.createElement('canvas')
    srcCanvas.width = videoElement.videoWidth
    srcCanvas.height = videoElement.videoHeight
    srcCanvas.getContext('2d').drawImage(videoElement, 0, 0)

    stopCamera()

    isAnimating.value = true

    await new Promise((resolve) => setTimeout(resolve, 1200))

    processCapture(srcCanvas)

    isProcessing.value = false
    isAnimating.value = false
  }

  function updateCorner(index, x, y) {
    if (!detectedCorners.value) return
    detectedCorners.value[index] = { x, y }
  }

  function recropWithCurrentCorners() {
    if (!detectedCorners.value || capturedPages.value.length === 0) return

    const lastCapture = capturedPages.value[capturedPages.value.length - 1]
    const img = new Image()
    img.onload = () => {
      const srcCanvas = document.createElement('canvas')
      srcCanvas.width = img.width
      srcCanvas.height = img.height
      srcCanvas.getContext('2d').drawImage(img, 0, 0)

      const perspCanvas = correctPerspective(
        srcCanvas,
        detectedCorners.value,
        1240, 1754,
      )

      const resultCanvas = selectedFilter.value === 'original'
        ? perspCanvas
        : selectedFilter.value === 'bw'
          ? applyBW(perspCanvas)
          : magicFilter(perspCanvas)

      const dataUrl = canvasToDataUrl(resultCanvas)
      capturedPages.value[capturedPages.value.length - 1] = {
        data: dataUrl,
        width: resultCanvas.width,
        height: resultCanvas.height,
      }
      previewUrl.value = dataUrl
    }
    img.src = lastCapture.data
  }

  function changeFilter(filter) {
    selectedFilter.value = filter
    if (capturedPages.value.length > 0) {
      recropWithCurrentCorners()
    }
  }

  function retakeLast() {
    if (capturedPages.value.length > 0) {
      capturedPages.value.pop()
    }
    previewUrl.value = null
    cameraError.value = null
    detectedCorners.value = null
    showCornerEditor.value = false
    startCamera()
  }

  function addPage() {
    previewUrl.value = null
    detectedCorners.value = null
    showCornerEditor.value = false
    startCamera()
  }

  function confirmPages() {
    isConfirmed.value = true
    showCornerEditor.value = false
  }

  function generatePdfBlob() {
    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
    })

    const pageW = pdf.internal.pageSize.getWidth()
    const pageH = pdf.internal.pageSize.getHeight()
    const margin = 10
    const maxW = pageW - margin * 2
    const maxH = pageH - margin * 2

    capturedPages.value.forEach((img, index) => {
      if (index > 0) pdf.addPage('a4', 'portrait')

      const imgAspect = img.width / img.height
      const pageAspect = maxW / maxH

      let drawW, drawH
      if (imgAspect > pageAspect) {
        drawW = maxW
        drawH = maxW / imgAspect
      } else {
        drawH = maxH
        drawW = maxH * imgAspect
      }

      const x = (pageW - drawW) / 2
      const y = (pageH - drawH) / 2

      pdf.addImage(img.data, 'JPEG', x, y, drawW, drawH)
    })

    return pdf.output('blob')
  }

  function stopCamera() {
    if (stream) {
      stream.getTracks().forEach((track) => track.stop())
      stream = null
    }
    if (videoElement) {
      videoElement.srcObject = null
    }
    isScanning.value = false
  }

  function reset() {
    stopCamera()
    previewUrl.value = null
    capturedPages.value = []
    cameraError.value = null
    isProcessing.value = false
    isConfirmed.value = false
    isAnimating.value = false
    pageLabel.value = ''
    detectedCorners.value = null
    showCornerEditor.value = false
    selectedFilter.value = 'enhanced'
    rawCaptureCanvas = null
  }

  return {
    isScanning,
    isProcessing,
    isAnimating,
    previewUrl,
    capturedPages,
    cameraError,
    hasCapture,
    isComplete,
    isConfirmed,
    pageLabel,
    detectedCorners,
    showCornerEditor,
    selectedFilter,
    filters,
    setVideoElement,
    startCamera,
    capture,
    retakeLast,
    addPage,
    confirmPages,
    generatePdfBlob,
    stopCamera,
    reset,
    updateCorner,
    recropWithCurrentCorners,
    changeFilter,
  }
}
