<script setup>
import { computed } from 'vue'
import { useAuth } from '@/Composables/useAuth'

const { user } = useAuth()

const greeting = computed(() => {
  const hour = Number(
    new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Manila', hour: 'numeric', hour12: false }).format(new Date())
  )
  if (hour >= 5 && hour < 12) return 'Good Morning!'
  if (hour >= 12 && hour < 18) return 'Good Afternoon!'
  return 'Good Evening!'
})

const firstName = computed(() => user.value?.first_name ?? '')

const today = computed(() => {
  return new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Manila',
    weekday: 'long',
    month: 'long',
    day: 'numeric',
    year: 'numeric',
  }).format(new Date())
})
</script>

<template>
  <div class="w-full mb-8 rounded-2xl bg-gradient-to-r from-[#059669] to-[#0d9488] px-8 py-6 text-white shadow-lg shadow-emerald-950/10">
    <div class="text-2xl font-semibold tracking-tight">{{ greeting }} {{ firstName }}.</div>
    <div class="mt-1 text-sm text-emerald-50/80">{{ today }}</div>
  </div>
</template>