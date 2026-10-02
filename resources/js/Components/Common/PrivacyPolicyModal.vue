<script setup>
defineProps({
  visible: { type: Boolean, default: false }
})

const emit = defineEmits(['update:visible'])

function close() {
  emit('update:visible', false)
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="visible"
        role="dialog"
        aria-modal="true"
        aria-labelledby="privacy-policy-title"
        class="fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-4 sm:p-6"
        @click.self="close"
      >
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
          <div class="flex items-center justify-between p-6 sm:p-8 pb-4 border-b border-emerald-100">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
              </div>
              <h2 id="privacy-policy-title" class="text-lg font-bold text-emerald-900">{{ $t('privacy.policy_title') }}</h2>
            </div>
            <button
              type="button"
              class="w-8 h-8 rounded-lg flex items-center justify-center text-emerald-500 hover:bg-emerald-50 transition-colors cursor-pointer"
              @click="close"
            >
              <i class="pi pi-times"></i>
            </button>
          </div>

          <div class="px-6 sm:px-8 py-6 overflow-y-auto min-h-0">
            <div class="space-y-6 text-sm text-emerald-800">
              <div v-for="s in 7" :key="s">
                <h3 class="font-semibold text-emerald-900 mb-1">{{ $t(`privacy.policy_s${s}_title`) }}</h3>
                <p class="leading-relaxed text-emerald-700">{{ $t(`privacy.policy_s${s}_body`) }}</p>
              </div>
            </div>
          </div>

          <div class="p-6 sm:p-8 pt-4 border-t border-emerald-100">
            <button
              type="button"
              class="w-full px-4 py-3 rounded-xl font-semibold text-sm bg-emerald-700 text-white hover:bg-emerald-800 active:scale-[0.98] transition-[background,transform] duration-150 shadow-lg shadow-emerald-200 cursor-pointer"
              @click="close"
            >
              {{ $t('privacy.policy_close') }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
  transition: opacity 0.25s ease-out;
}
.fade-enter-from, .fade-leave-to {
  opacity: 0;
}
</style>
