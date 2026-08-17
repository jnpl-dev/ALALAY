<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  action: { type: String, default: null },
})

const emit = defineEmits(['token', 'expired', 'error'])

const page = usePage()
const siteKey = page.props.utils?.turnstile_site_key || ''

const container = ref(null)
const widgetId = ref(null)
const loaded = ref(false)

let scriptPromise = null

function loadScript() {
  if (window.turnstile) {
    loaded.value = true
    return Promise.resolve()
  }
  if (!scriptPromise) {
    scriptPromise = new Promise((resolve) => {
      const script = document.createElement('script')
      script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit'
      script.async = true
      script.defer = true
      script.onload = () => {
        loaded.value = true
        resolve()
      }
      script.onerror = () => {
        loaded.value = false
        resolve()
      }
      document.head.appendChild(script)
    })
  }
  return scriptPromise
}

function render() {
  if (!siteKey || !container.value || !window.turnstile) return
  widgetId.value = window.turnstile.render(container.value, {
    sitekey: siteKey,
    appearance: 'always',
    theme: 'light',
    action: props.action || undefined,
    callback: (token) => emit('token', token),
    'expired-callback': () => emit('expired'),
    'error-callback': () => emit('error'),
  })
}

function reset() {
  if (window.turnstile && widgetId.value) {
    window.turnstile.reset(widgetId.value)
  }
}

onMounted(async () => {
  if (!siteKey) return
  await loadScript()
  render()
})

onBeforeUnmount(() => {
  if (window.turnstile && widgetId.value) {
    window.turnstile.remove(widgetId.value)
  }
})
</script>

<template>
  <div v-if="siteKey" ref="container" class="cf-turnstile flex justify-center"></div>
</template>