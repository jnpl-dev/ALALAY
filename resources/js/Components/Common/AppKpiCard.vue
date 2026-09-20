<script setup>
import { computed } from 'vue'

const props = defineProps({
  title: { type: String, required: true },
  value: { type: [String, Number], default: '—' },
  icon: { type: String, default: 'pi pi-file' },
  color: { type: String, default: 'info' },
  subtitle: { type: String, default: '' },
  change: { type: Number, default: null },
  changeLabel: { type: String, default: '' },
})

const colorMap = {
  info: { bg: 'bg-blue-100 dark:bg-blue-400/10', icon: 'text-blue-500' },
  success: { bg: 'bg-green-100 dark:bg-green-400/10', icon: 'text-green-500' },
  warn: { bg: 'bg-orange-100 dark:bg-orange-400/10', icon: 'text-orange-500' },
  danger: { bg: 'bg-red-100 dark:bg-red-400/10', icon: 'text-red-500' },
}

const trend = computed(() => {
  if (props.change == null) return null
  if (props.change === 0) return 'neutral'
  return props.change > 0 ? 'up' : 'down'
})
</script>

<template>
  <div class="card mb-0 h-full">
    <div class="flex justify-between mb-4">
      <div>
        <span class="block text-muted-color font-medium mb-4">{{ title }}</span>
        <div class="text-surface-900 font-medium text-xl">{{ value }}</div>
      </div>
      <div class="flex items-center justify-center rounded-full" style="width: 2.5rem; height: 2.5rem" :class="colorMap[color]?.bg ?? colorMap.info.bg">
        <i :class="[icon, colorMap[color]?.icon ?? colorMap.info.icon, 'text-xl!']"></i>
      </div>
    </div>
    <div v-if="trend !== null" class="flex items-center gap-1.5 text-sm">
      <i :class="{
        'pi pi-arrow-up text-green-500': trend === 'up',
        'pi pi-arrow-down text-red-500': trend === 'down',
        'pi pi-minus text-muted-color': trend === 'neutral',
      }" />
      <span :class="{
        'text-green-500': trend === 'up',
        'text-red-500': trend === 'down',
        'text-muted-color': trend === 'neutral',
      }">
        {{ change > 0 ? '+' : '' }}{{ change }}
      </span>
      <span v-if="changeLabel" class="text-muted-color">{{ changeLabel }}</span>
    </div>
  </div>
</template>
