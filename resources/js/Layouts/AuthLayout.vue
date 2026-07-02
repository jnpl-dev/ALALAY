<script setup>
import { Head } from '@inertiajs/vue3'
</script>

<template>
  <Head title="Login" />
  <div class="min-h-screen flex items-center justify-center bg-surface-50 px-4">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <div class="text-3xl font-bold text-primary">ALALAY</div>
        <div class="text-sm text-surface-500 mt-1">Municipality of General Mamerto Natividad, Nueva Ecija</div>
      </div>
      <div class="bg-white p-8 rounded-xl shadow-lg border border-surface-200">
        <slot />
      </div>
    </div>
  </div>
</template>
