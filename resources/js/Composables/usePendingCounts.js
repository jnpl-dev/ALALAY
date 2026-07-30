import { ref, watch, onMounted, onUnmounted } from 'vue'
import { useDocumentVisibility } from '@vueuse/core'
import axios from 'axios'

export function usePendingCounts(intervalSeconds = 15) {
  const counts = ref({})
  const visibility = useDocumentVisibility()

  let timer = null

  async function fetch() {
    try {
      const { data } = await axios.get(route('pending-counts'))
      counts.value = data
    } catch {
      // Silent fail
    }
  }

  function start() {
    stop()
    fetch()
    timer = setInterval(fetch, intervalSeconds * 1000)
  }

  function stop() {
    if (timer) clearInterval(timer)
    timer = null
  }

  onMounted(() => {
    if (visibility.value === 'visible') start()
  })

  onUnmounted(stop)

  watch(visibility, (visible) => {
    if (visible) start()
    else stop()
  })

  return { counts }
}
