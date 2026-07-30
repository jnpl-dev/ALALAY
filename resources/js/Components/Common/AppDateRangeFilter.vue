<script setup>
import { ref, watch } from 'vue'
import Button from 'primevue/button'
import DatePicker from 'primevue/datepicker'

const props = defineProps({
  dateFrom: { type: String, default: '' },
  dateTo: { type: String, default: '' },
})

const emit = defineEmits(['apply', 'clear'])

const from = ref(props.dateFrom ? new Date(props.dateFrom) : new Date(new Date().getFullYear(), new Date().getMonth(), 1))
const to = ref(props.dateTo ? new Date(props.dateTo) : new Date())

watch(() => props.dateFrom, (v) => { if (v) from.value = new Date(v) })
watch(() => props.dateTo, (v) => { if (v) to.value = new Date(v) })

function apply() {
  if (!from.value || !to.value) return
  emit('apply', {
    date_from: from.value.toISOString().slice(0, 10),
    date_to: to.value.toISOString().slice(0, 10),
  })
}

function clearFilter() {
  const now = new Date()
  from.value = new Date(now.getFullYear(), now.getMonth(), 1)
  to.value = now
  emit('clear')
}
</script>

<template>
  <div class="flex flex-wrap items-center gap-3">
    <DatePicker v-model="from" dateFormat="yy-mm-dd" placeholder="From" :showIcon="true" showClear />
    <span class="text-muted-color">—</span>
    <DatePicker v-model="to" dateFormat="yy-mm-dd" placeholder="To" :showIcon="true" showClear />
    <Button label="Apply" icon="pi pi-search" @click="apply" />
    <Button label="Clear" icon="pi pi-refresh" severity="secondary" @click="clearFilter" />
  </div>
</template>
