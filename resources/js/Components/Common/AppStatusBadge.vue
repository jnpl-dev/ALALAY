<script setup>
import { computed } from 'vue'
import Tag from 'primevue/tag'
import { getStatusLabel } from '@/Utils/statusLabels'

const props = defineProps({
  status: { type: String, default: null },
})

const info = computed(() => getStatusLabel(props.status))
</script>

<template>
  <Tag v-if="status" :severity="info.severity" :value="info.label" rounded />
  <span v-else class="text-muted-color text-xs">—</span>
</template>
