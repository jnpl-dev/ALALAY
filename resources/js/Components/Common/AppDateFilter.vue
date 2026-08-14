<script setup>
import { ref, watch } from 'vue'
import SelectButton from 'primevue/selectbutton'
import DatePicker from 'primevue/datepicker'
import Button from 'primevue/button'
import { formatDate } from '@/Utils/formatDate'

const emit = defineEmits(['filter-changed'])

const presetOptions = [
  { label: 'Today', value: 0 },
  { label: 'This Week', value: 7 },
  { label: 'This Month', value: 30 },
  { label: 'This Quarter', value: 90 },
  { label: 'This Year', value: 365 },
]

const selected = ref(presetOptions[2])
const fromDate = ref(null)
const toDate = ref(null)
const showCustom = ref(false)

watch(selected, (preset) => {
  if (!preset) return
  showCustom.value = false
  const to = new Date()
  const from = new Date()
  from.setDate(from.getDate() - preset.value)
  emit('filter-changed', {
    from: formatDate(from, 'YYYY-MM-DD'),
    to: formatDate(to, 'YYYY-MM-DD'),
  })
}, { immediate: true })

function applyCustom() {
  if (fromDate.value && toDate.value) {
    emit('filter-changed', {
      from: formatDate(fromDate.value, 'YYYY-MM-DD'),
      to: formatDate(toDate.value, 'YYYY-MM-DD'),
    })
  }
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-2">
    <SelectButton
      v-model="selected"
      :options="presetOptions"
      optionLabel="label"
      :allowEmpty="false"
    />
    <button
      @click="showCustom = !showCustom"
      class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors cursor-pointer"
      :class="showCustom || !selected
        ? 'bg-primary text-primary-contrast border-primary'
        : 'bg-surface-0 text-surface-700 border-surface-300 hover:bg-surface-100'"
    >
      Custom
    </button>
    <div v-if="showCustom" class="flex items-center gap-2 ml-2">
      <DatePicker v-model="fromDate" dateFormat="yy-mm-dd" placeholder="From" :showIcon="true" />
      <span class="text-muted-color text-sm">—</span>
      <DatePicker v-model="toDate" dateFormat="yy-mm-dd" placeholder="To" :showIcon="true" />
      <Button label="Apply" size="small" @click="applyCustom" />
    </div>
  </div>
</template>