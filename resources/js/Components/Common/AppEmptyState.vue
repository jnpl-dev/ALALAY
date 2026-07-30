<script setup>
defineProps({
  icon: { type: String, default: 'pi pi-inbox' },
  message: { type: String, default: 'No data available' },
})
</script>

<template>
  <div class="flex flex-col items-center justify-center py-8 text-muted-color">
    <i :class="[icon, 'text-4xl mb-3 text-muted-color']"></i>
    <span>{{ message }}</span>
    <slot />
  </div>
</template>
