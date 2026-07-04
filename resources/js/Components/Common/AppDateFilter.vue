<script setup>
import { ref } from 'vue'
import { formatDate } from '@/Utils/formatDate'

const emit = defineEmits(['filter-changed'])

const presets = [
  { label: 'Today', days: 0 },
  { label: 'This Week', days: 7 },
  { label: 'This Month', days: 30 },
  { label: 'This Quarter', days: 90 },
  { label: 'This Year', days: 365 },
]

const selected = ref('This Month')
const fromDate = ref('')
const toDate = ref('')
const showCustom = ref(false)

function selectPreset(preset) {
  selected.value = preset.label
  showCustom.value = false
  const to = new Date()
  const from = new Date()
  from.setDate(from.getDate() - preset.days)
  emit('filter-changed', {
    from: formatDate(from, 'YYYY-MM-DD'),
    to: formatDate(to, 'YYYY-MM-DD'),
  })
}

function applyCustom() {
  selected.value = 'Custom'
  emit('filter-changed', {
    from: fromDate.value,
    to: toDate.value,
  })
}

selectPreset(presets[2])
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <button
      v-for="preset in presets"
      :key="preset.label"
      @click="selectPreset(preset)"
      class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors cursor-pointer"
      :class="selected === preset.label
        ? 'bg-primary text-primary-contrast border-primary'
        : 'bg-surface-0 text-surface-700 border-surface-300 hover:bg-surface-100'"
    >
      {{ preset.label }}
    </button>
    <button
      @click="showCustom = !showCustom"
      class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors cursor-pointer"
      :class="showCustom || selected === 'Custom'
        ? 'bg-primary text-primary-contrast border-primary'
        : 'bg-surface-0 text-surface-700 border-surface-300 hover:bg-surface-100'"
    >
      Custom
    </button>
    <div v-if="showCustom" class="flex items-center gap-2 ml-2">
      <input v-model="fromDate" type="date" class="px-2 py-1.5 text-sm rounded-lg border border-surface-300 outline-none focus:ring-2 focus:ring-primary" />
      <span class="text-muted-color text-sm">—</span>
      <input v-model="toDate" type="date" class="px-2 py-1.5 text-sm rounded-lg border border-surface-300 outline-none focus:ring-2 focus:ring-primary" />
      <button @click="applyCustom" class="px-3 py-1.5 text-sm font-medium rounded-lg bg-primary text-white border-none cursor-pointer hover:bg-primary-600 transition-colors">
        Apply
      </button>
    </div>
  </div>
</template>
