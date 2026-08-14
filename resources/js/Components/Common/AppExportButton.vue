<script setup>
import Button from 'primevue/button'

const props = defineProps({
  url: { type: String, required: true },
  params: { type: Object, default: () => ({}) },
  label: { type: String, default: 'Export CSV' },
  icon: { type: String, default: 'pi pi-download' },
})

function exportData() {
  const query = new URLSearchParams()
  Object.entries(props.params).forEach(([k, v]) => {
    if (v != null && v !== '') query.append(k, v)
  })
  const qs = query.toString()
  const fullUrl = qs ? `${props.url}?${qs}` : props.url

  const link = document.createElement('a')
  link.href = fullUrl
  link.setAttribute('download', '')
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
}
</script>

<template>
  <Button :label="label" :icon="icon" severity="secondary" outlined @click="exportData" />
</template>
