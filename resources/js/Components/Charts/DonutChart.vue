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
    <Chart type="doughnut" :data="data" :options="options" class="h-80" />
  </div>
</template>
