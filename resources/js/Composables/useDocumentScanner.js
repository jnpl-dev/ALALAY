import { ref } from 'vue'

export function useDocumentScanner() {
  const isScanning = ref(false)
  const isProcessing = ref(false)
  const previewUrl = ref(null)
  const capturedBlob = ref(null)
  const cameraError = ref(null)
  const hasCapture = ref(false)
  const submissionMethod = ref(null)

  let stream = null
  let videoElement = null

  function setVideoElement(el) {
    videoElement = el
  }

  async function startCamera() {
    cameraError.value = null
    isScanning.value = true
    submissionMethod.value = null

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: { facingMode: 'environment', width: { ideal: 1920 }, height: { ideal: 1080 } },
        audio: false,
      })

      if (videoElement) {
        videoElement.srcObject = stream
      }
    } catch (err) {
      isScanning.value = false
      cameraError.value = err.message || 'Camera access denied or unavailable'
      stream = null
    }
  }

  function capture() {
    if (!videoElement || !stream) return

    isProcessing.value = true

    const video = videoElement
    const srcWidth = video.videoWidth
    const srcHeight = video.videoHeight

    const canvas = document.createElement('canvas')

    const MAX_WIDTH = 1200
    let targetWidth = srcWidth
    let targetHeight = srcHeight
    if (srcWidth > MAX_WIDTH) {
      targetWidth = MAX_WIDTH
      targetHeight = Math.round((srcHeight / srcWidth) * MAX_WIDTH)
    }

    canvas.width = targetWidth
    canvas.height = targetHeight

    const ctx = canvas.getContext('2d')
    ctx.drawImage(video, 0, 0, targetWidth, targetHeight)

    const imageData = ctx.getImageData(0, 0, targetWidth, targetHeight)
    const data = imageData.data
    const len = data.length

    const gray = new Uint8Array(targetWidth * targetHeight)

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
    const w = targetWidth
    const h = targetHeight
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
    outCanvas.width = targetWidth
    outCanvas.height = targetHeight
    const outCtx = outCanvas.getContext('2d')
    const outImageData = new ImageData(output, targetWidth, targetHeight)
    outCtx.putImageData(outImageData, 0, 0)

    outCanvas.toBlob((blob) => {
      isProcessing.value = false

      if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value)
      }

      capturedBlob.value = blob
      previewUrl.value = URL.createObjectURL(blob)
      hasCapture.value = true
      submissionMethod.value = 'camera'

      stopCamera()
    }, 'image/jpeg', 0.88)
  }

  function retake() {
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value)
    }
    capturedBlob.value = null
    previewUrl.value = null
    hasCapture.value = false
    submissionMethod.value = null
    cameraError.value = null
    isScanning.value = false
    startCamera()
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

  function useFallback(file) {
    if (!file) return

    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value)
    }

    isProcessing.value = true

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
      const ctx = canvas.getContext('2d')
      ctx.drawImage(img, 0, 0, targetWidth, targetHeight)

      canvas.toBlob((blob) => {
        isProcessing.value = false
        capturedBlob.value = blob
        previewUrl.value = URL.createObjectURL(blob)
        hasCapture.value = true
        submissionMethod.value = 'fallback_upload'
      }, 'image/jpeg', 0.88)
    }
    img.src = URL.createObjectURL(file)
  }

  function clearCapture() {
    if (previewUrl.value) {
      URL.revokeObjectURL(previewUrl.value)
    }
    capturedBlob.value = null
    previewUrl.value = null
    hasCapture.value = false
    submissionMethod.value = null
    isScanning.value = false
    isProcessing.value = false
    cameraError.value = null
  }

  return {
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
  }
}
