<script setup>
defineProps({
  data: { type: Object, default: () => ({}) },
  options: { type: Object, default: () => ({}) },
  title: { type: String, default: '' },
  height: { type: String, default: '300px' },
})
</script>

<template>
  <div class="card">
    <div v-if="title" class="font-semibold text-xl mb-4">{{ title }}</div>
    <table v-if="data?.labels?.length" class="w-full text-sm">
      <thead>
        <tr class="text-muted-color font-medium">
          <th class="text-left pb-2">Label</th>
          <th class="text-right pb-2">Value</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="(label, i) in data.labels" :key="i" class="border-t border-surface">
          <td class="py-2 text-surface-900">{{ label }}</td>
          <td class="py-2 text-right text-surface-900">{{ data.datasets?.[0]?.data?.[i] ?? '—' }}</td>
        </tr>
      </tbody>
    </table>
    <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
      <i class="pi pi-chart-bar text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
      <span>No data available</span>
    </div>
  </div>
</template>
