<script setup>
import Button from 'primevue/button'

const props = defineProps({
  url: { type: String, required: true },
  label: { type: String, default: 'Export CSV' },
  icon: { type: String, default: 'pi pi-download' },
})

function exportData() {
  window.open(props.url, '_blank')
}
</script>

<template>
  <Button :label="label" :icon="icon" severity="secondary" outlined @click="exportData" />
</template>
