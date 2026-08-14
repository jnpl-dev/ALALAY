<script setup>
defineProps({
  data: { type: Object, default: () => ({}) },
  options: { type: Object, default: () => ({}) },
  title: { type: String, default: '' },
})
</script>

<template>
  <div class="card">
    <div v-if="title" class="font-semibold text-xl mb-4">{{ title }}</div>
    <Chart v-if="data?.labels?.length" type="doughnut" :data="data" :options="options" class="h-80" />
    <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
      <i class="pi pi-chart-pie text-4xl mb-3 text-muted-color"></i>
      <span>No data available</span>
    </div>
  </div>
</template>