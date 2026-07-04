<script setup>
import { router } from '@inertiajs/vue3'
import Button from 'primevue/button'

defineProps({
  url: { type: String, required: true },
  label: { type: String, default: 'Export CSV' },
  icon: { type: String, default: 'pi pi-download' },
})

function exportData() {
  router.visit(url, { method: 'get', data: { export: 'csv' } })
}
</script>

<template>
  <Button :label="label" :icon="icon" severity="secondary" outlined @click="exportData" />
</template>
