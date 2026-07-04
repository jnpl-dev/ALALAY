<script setup>
import { computed } from 'vue'
import { getStatusLabel } from '@/Utils/statusLabels'

const props = defineProps({
  status: { type: String, default: null },
})

const severityMap = {
  info: 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300',
  success: 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300',
  warn: 'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300',
  danger: 'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300',
  contrast: 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
}

const info = computed(() => getStatusLabel(props.status))
</script>

<template>
  <span v-if="status" class="inline-block px-2 py-0.5 text-xs font-medium rounded-full" :class="severityMap[info.severity] ?? severityMap.contrast">
    {{ info.label }}
  </span>
  <span v-else class="text-muted-color text-xs">—</span>
</template>
