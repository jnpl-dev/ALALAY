import { ref, computed } from 'vue'
import { jsPDF } from 'jspdf'
import { cannyEdgeDetection, findDocumentCorners, correctPerspective, magicFilter, downscaleCanvas } from '@/Utils/imageProcessing.js'

export function useDocumentScanner(captureType = 'single') {
  const isScanning = ref(false)
  const isProcessing = ref(false)
  const isAnimating = ref(false)
  const previewUrl = ref(null)
  const rawPreviewUrl = ref(null)
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
  const cropStage = ref('capturing')

  const detectedCorners = ref(null)
  const showCornerEditor = ref(false)
  const selectedFilter = ref('enhanced')
  const filters = ['original', 'enhanced', 'bw']
  const rawCaptureWidth = ref(0)
  const rawCaptureHeight = ref(0)
  const rawCroppedDataUrl = ref(null)

  let rawCaptureCanvas = null

  function downscale(sourceCanvas, maxWidth = 1200) {
    const scale = Math.min(1, maxWidth / sourceCanvas.width)
    const dest = document.createElement('canvas')
    dest.width = sourceCanvas.width * scale
    dest.height = sourceCanvas.height * scale
    dest.getContext('2d').drawImage(sourceCanvas, 0, 0, dest.width, dest.height)
    return dest
  }

  function canvasToDataUrl(canvas) {
    return canvas.toDataURL('image/jpeg', 0.88)
  }

  async function captureWithFile(file) {
    if (!file) return
    isAnimating.value = true
    previewUrl.value = null
    rawPreviewUrl.value = null
    cameraError.value = null

    const img = await createImageBitmap(file)

    const fullResCanvas = document.createElement('canvas')
    fullResCanvas.width = img.width
    fullResCanvas.height = img.height
    fullResCanvas.getContext('2d').drawImage(img, 0, 0)
    img.close()

    await new Promise((resolve) => setTimeout(resolve, 800))

    const down = downscale(fullResCanvas)
    rawCaptureCanvas = fullResCanvas

    const { edges, width, height } = cannyEdgeDetection(
      down.getContext('2d').getImageData(0, 0, down.width, down.height)
    )

    const corners = findDocumentCorners(edges, width, height)

    const scaleX = fullResCanvas.width / down.width
    const scaleY = fullResCanvas.height / down.height

    if (corners) {
      detectedCorners.value = corners.map(c => ({
        x: Math.round(c.x * scaleX),
        y: Math.round(c.y * scaleY),
      }))
      showCornerEditor.value = true
    } else {
      const marginX = Math.round(fullResCanvas.width * 0.1)
      const marginY = Math.round(fullResCanvas.height * 0.1)
      detectedCorners.value = [
        { x: marginX, y: marginY },
        { x: fullResCanvas.width - marginX, y: marginY },
        { x: fullResCanvas.width - marginX, y: fullResCanvas.height - marginY },
        { x: marginX, y: fullResCanvas.height - marginY },
      ]
      showCornerEditor.value = true
    }

    rawPreviewUrl.value = canvasToDataUrl(down)
    previewUrl.value = canvasToDataUrl(down)
    rawCaptureWidth.value = fullResCanvas.width
    rawCaptureHeight.value = fullResCanvas.height
    cropStage.value = 'cropping'

    isAnimating.value = false
  }

  function applyCrop() {
    if (!rawCaptureCanvas) return
    isProcessing.value = true

    let resultCanvas, rawCroppedCanvas
    if (detectedCorners.value) {
      const perspCanvas = correctPerspective(rawCaptureCanvas, detectedCorners.value, 1240, 1754)
      rawCroppedCanvas = perspCanvas
      resultCanvas = magicFilter(perspCanvas)
    } else {
      rawCroppedCanvas = rawCaptureCanvas
      resultCanvas = magicFilter(rawCaptureCanvas)
    }

    rawCroppedDataUrl.value = canvasToDataUrl(rawCroppedCanvas)

    const dataUrl = canvasToDataUrl(resultCanvas)

    capturedPages.value.push({
      data: dataUrl,
      width: resultCanvas.width,
      height: resultCanvas.height,
    })

    previewUrl.value = dataUrl
    rawPreviewUrl.value = null
    cropStage.value = 'preview'
    rawCaptureCanvas = null
    isProcessing.value = false
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

  function applyFilterToLatest() {
    if (!rawCroppedDataUrl.value || capturedPages.value.length === 0) return

    const img = new Image()
    img.onload = () => {
      const srcCanvas = document.createElement('canvas')
      srcCanvas.width = img.width
      srcCanvas.height = img.height
      srcCanvas.getContext('2d').drawImage(img, 0, 0)

      const resultCanvas = selectedFilter.value === 'original'
        ? srcCanvas
        : selectedFilter.value === 'bw'
          ? applyBW(srcCanvas)
          : magicFilter(srcCanvas)

      const dataUrl = canvasToDataUrl(resultCanvas)
      capturedPages.value[capturedPages.value.length - 1] = {
        data: dataUrl,
        width: resultCanvas.width,
        height: resultCanvas.height,
      }
      previewUrl.value = dataUrl
    }
    img.src = rawCroppedDataUrl.value
  }

  function changeFilter(filter) {
    selectedFilter.value = filter
    if (capturedPages.value.length > 0) {
      applyFilterToLatest()
    }
  }

  function retakeLast() {
    if (capturedPages.value.length > 0) {
      capturedPages.value.pop()
    }
    previewUrl.value = null
    rawPreviewUrl.value = null
    rawCroppedDataUrl.value = null
    cameraError.value = null
    detectedCorners.value = null
    showCornerEditor.value = false
    cropStage.value = 'capturing'
    rawCaptureCanvas = null
  }

  function addPage() {
    previewUrl.value = null
    rawPreviewUrl.value = null
    rawCroppedDataUrl.value = null
    detectedCorners.value = null
    showCornerEditor.value = false
    cropStage.value = 'capturing'
    rawCaptureCanvas = null
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

  function stopCamera() {
    isScanning.value = false
  }

  function reset() {
    previewUrl.value = null
    rawPreviewUrl.value = null
    capturedPages.value = []
    cameraError.value = null
    isProcessing.value = false
    isConfirmed.value = false
    isAnimating.value = false
    pageLabel.value = ''
    detectedCorners.value = null
    showCornerEditor.value = false
    selectedFilter.value = 'enhanced'
    cropStage.value = 'capturing'
    rawCaptureCanvas = null
    rawCroppedDataUrl.value = null
  }

  return {
    isScanning,
    isProcessing,
    isAnimating,
    previewUrl,
    rawPreviewUrl,
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
    cropStage,
    rawCaptureWidth,
    rawCaptureHeight,
    rawCroppedDataUrl,
    captureWithFile,
    applyCrop,
    retakeLast,
    addPage,
    confirmPages,
    generatePdfBlob,
    reset,
    updateCorner,
    recropWithCurrentCorners,
    changeFilter,
  }
}