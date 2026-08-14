import { ref } from 'vue'

const cache = new Map()

let pdfjs = null

async function getPdfjs() {
  if (!pdfjs) {
    pdfjs = await import('pdfjs-dist')
    pdfjs.GlobalWorkerOptions.workerSrc = new URL(
      'pdfjs-dist/build/pdf.worker.min.mjs',
      import.meta.url,
    ).toString()
  }
  return pdfjs
}

export function usePdfThumbnail() {
  const loading = ref(false)
  const thumbnail = ref(null)

  async function generate(url, pageNum = 1) {
    if (!url) return

    const cached = cache.get(url)
    if (cached) {
      thumbnail.value = cached
      return
    }

    loading.value = true
    thumbnail.value = null

    try {
      const pdfjsLib = await getPdfjs()
      const doc = await pdfjsLib.getDocument({ url }).promise
      const page = await doc.getPage(pageNum)
      const vp = page.getViewport({ scale: 0.5 })

      const canvas = document.createElement('canvas')
      canvas.width = vp.width
      canvas.height = vp.height
      const ctx = canvas.getContext('2d')

      await page.render({ canvasContext: ctx, viewport: vp }).promise

      const dataUrl = canvas.toDataURL('image/jpeg', 0.8)
      cache.set(url, dataUrl)
      thumbnail.value = dataUrl
    } catch (e) {
      console.error('[PDF Thumbnail]', e)
      thumbnail.value = null
    } finally {
      loading.value = false
    }
  }

  function reset() {
    thumbnail.value = null
    loading.value = false
  }

  return { thumbnail, loading, generate, reset }
}
