import { ref, watch } from 'vue'
import axios from 'axios'

export function useFieldValidation(url, getFieldValue, params, options = {}) {
  const { debounceMs = 500 } = options
  const isValid = ref(null)
  const message = ref('')
  const isChecking = ref(false)
  let timer = null

  watch(getFieldValue, (val) => {
    clearTimeout(timer)
    if (!val || val.length === 0) {
      isValid.value = null
      message.value = ''
      if (timer) isChecking.value = false
      return
    }

    isChecking.value = true
    timer = setTimeout(async () => {
      try {
        const response = await axios.get(url, {
          params: {
            ...(typeof params === 'function' ? params() : params),
            value: val,
          },
        })
        isValid.value = response.data.valid
        message.value = response.data.message || ''
      } catch {
        isValid.value = null
        message.value = ''
      } finally {
        isChecking.value = false
      }
    }, debounceMs)
  })

  return { isValid, message, isChecking }
}
