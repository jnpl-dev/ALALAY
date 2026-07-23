import { computed, isRef, ref, watch, onMounted, onUnmounted } from 'vue'
import { useDocumentVisibility } from '@vueuse/core'
import axios from 'axios'

export function usePolling(url, params, onNewData, intervalSeconds = 20, options = {}) {
  const { enabled = null } = options
  const lastChecked = ref(null)
  const isPolling = ref(false)
  const isBusy = ref(false)
  const pendingUpdate = ref(null)
  const visibility = useDocumentVisibility()
  const isVisible = computed(() => visibility.value === 'visible')

  let timer = null

  function isEnabled() {
    if (enabled !== null) {
      return typeof enabled === 'function' ? enabled() : enabled
    }
    return true
  }

  function resolveParams() {
    if (params && typeof params === 'object' && isRef(params)) {
      return params.value
    }
    if (typeof params === 'function') {
      return params()
    }
    return params || {}
  }

  async function poll() {
    if (isPolling.value || !isEnabled()) return
    isPolling.value = true

    try {
      const response = await axios.get(url, {
        params: {
          ...resolveParams(),
          since: lastChecked.value,
        }
      })

      if (response.data.changed) {
        if (isBusy.value) {
          pendingUpdate.value = response.data
        } else {
          applyUpdate(response.data)
        }
      }
    } catch {
      // Silent fail
    } finally {
      isPolling.value = false
    }
  }

  function applyUpdate(data) {
    onNewData(data)
    lastChecked.value = data.last_checked
    pendingUpdate.value = null
  }

  function start() {
    stop()
    poll()
    timer = setInterval(poll, intervalSeconds * 1000)
  }

  function stop() {
    if (timer) clearInterval(timer)
    timer = null
  }

  onMounted(() => {
    start()
  })

  onUnmounted(() => {
    stop()
  })

  const unwatch = watch(isVisible, (visible) => {
    if (visible) {
      poll()
      start()
    } else {
      stop()
    }
  })

  function markBusy() {
    isBusy.value = true
  }

  function markFree() {
    isBusy.value = false
    if (pendingUpdate.value) {
      applyUpdate(pendingUpdate.value)
    }
  }

  return {
    isPolling,
    lastChecked,
    markBusy,
    markFree,
    start,
    stop,
  }
}
