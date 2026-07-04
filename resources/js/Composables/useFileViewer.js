import { ref } from 'vue'

const viewerState = ref(null)

export function useFileViewer() {
  function open(url, title) {
    viewerState.value = { url, title: title ?? 'Document' }
  }

  function close() {
    viewerState.value = null
  }

  return {
    viewerState,
    open,
    close,
  }
}
