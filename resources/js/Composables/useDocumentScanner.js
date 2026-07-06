import { ref, computed } from 'vue'
import { jsPDF } from 'jspdf'

export function useDocumentScanner(captureType = 'single') {
  const isScanning = ref(false)
  const isProcessing = ref(false)
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

  let stream = null
  let videoElement = null

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

  function enhance(canvas) {
    const down = downscale(canvas)
    const w = down.width
    const h = down.height
    const ctx = down.getContext('2d')
    const imageData = ctx.getImageData(0, 0, w, h)
    const data = imageData.data
    const len = data.length

    const gray = new Uint8Array(w * h)
    let minVal = 255
    let maxVal = 0
    for (let i = 0; i < len; i += 4) {
      const g = Math.round(0.299 * data[i] + 0.587 * data[i + 1] + 0.114 * data[i + 2])
      gray[i / 4] = g
      if (g < minVal) minVal = g
      if (g > maxVal) maxVal = g
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
    const integral = new Uint32Array((w + 1) * (h + 1))
    for (let y = 0; y < h; y++) {
      for (let x = 0; x < w; x++) {
        const idx = (y + 1) * (w + 1) + (x + 1)
        const val = stretched[y * w + x]
        integral[idx] = val
          + integral[(y) * (w + 1) + (x + 1)]
          + integral[(y + 1) * (w + 1) + (x)]
          - integral[(y) * (w + 1) + (x)]
      }
    }

    const output = new Uint8ClampedArray(len)
    for (let y = 0; y < h; y++) {
      for (let x = 0; x < w; x++) {
        const x1 = Math.max(0, x - Math.floor(blockSize / 2))
        const y1 = Math.max(0, y - Math.floor(blockSize / 2))
        const x2 = Math.min(w - 1, x + Math.floor(blockSize / 2))
        const y2 = Math.min(h - 1, y + Math.floor(blockSize / 2))
        const count = (x2 - x1 + 1) * (y2 - y1 + 1)
        const sum = integral[(y2 + 1) * (w + 1) + (x2 + 1)]
          - integral[(y1) * (w + 1) + (x2 + 1)]
          - integral[(y2 + 1) * (w + 1) + (x1)]
          + integral[(y1) * (w + 1) + (x1)]
        const mean = sum / count
        const idx = (y * w + x) * 4
        const val = stretched[y * w + x] < (mean - C) ? 0 : 255
        output[idx] = val
        output[idx + 1] = val
        output[idx + 2] = val
        output[idx + 3] = 255
      }
    }

    const outCanvas = document.createElement('canvas')
    outCanvas.width = w
    outCanvas.height = h
    const outCtx = outCanvas.getContext('2d')
    outCtx.putImageData(new ImageData(output, w, h), 0, 0)
    return outCanvas
  }

  function canvasToDataUrl(enhancedCanvas) {
    return enhancedCanvas.toDataURL('image/jpeg', 0.88)
  }

  function capture() {
    if (!videoElement || !stream) return
    isProcessing.value = true
    previewUrl.value = null

    const srcCanvas = document.createElement('canvas')
    srcCanvas.width = videoElement.videoWidth
    srcCanvas.height = videoElement.videoHeight
    srcCanvas.getContext('2d').drawImage(videoElement, 0, 0)

    const enhanced = enhance(srcCanvas)
    const dataUrl = canvasToDataUrl(enhanced)

    capturedPages.value.push({
      data: dataUrl,
      width: enhanced.width,
      height: enhanced.height,
    })

    previewUrl.value = dataUrl
    isProcessing.value = false
    stopCamera()
  }

  function retakeLast() {
    if (capturedPages.value.length > 0) {
      capturedPages.value.pop()
    }
    previewUrl.value = null
    cameraError.value = null
    startCamera()
  }

  function addPage() {
    previewUrl.value = null
    startCamera()
  }

  function confirmPages() {
    isConfirmed.value = true
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
    pageLabel.value = ''
  }

  return {
    isScanning,
    isProcessing,
    previewUrl,
    capturedPages,
    cameraError,
    hasCapture,
    isComplete,
    isConfirmed,
    pageLabel,
    setVideoElement,
    startCamera,
    capture,
    retakeLast,
    addPage,
    confirmPages,
    generatePdfBlob,
    stopCamera,
    reset,
  }
}
