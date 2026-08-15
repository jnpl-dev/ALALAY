<script setup>
import { ref, onMounted } from 'vue'

const emit = defineEmits(['close'])

const show = ref(false)
const agreed = ref(false)

onMounted(() => {
  show.value = true
})

function proceed() {
  if (!agreed.value) return
  show.value = false
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="show"
        role="dialog"
        aria-modal="true"
        aria-labelledby="disclaimer-title"
        class="fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-6"
      >
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl flex flex-col max-h-[85vh]">
          <div class="p-8 sm:p-10 pb-0 text-center">
            <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
              <svg class="w-8 h-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
              </svg>
            </div>
            <h2 id="disclaimer-title" class="text-xl font-bold text-emerald-900 mb-2">{{ $t('disclaimer.title') }}</h2>
            <p class="text-emerald-600 text-sm mb-5">{{ $t('disclaimer.lead') }}</p>
          </div>

          <div class="px-8 sm:px-10 overflow-y-auto min-h-0">
            <ul class="space-y-3 text-sm text-emerald-800">
              <li v-for="n in 5" :key="n" class="flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 8.5c-.77-1.333-2.694-1.333-3.464 0L4.17 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>{{ $t(`disclaimer.w${n}`) }}</span>
              </li>
            </ul>
          </div>

          <div class="p-8 sm:p-10 pt-6">
            <label
              class="flex items-start gap-3 cursor-pointer select-none rounded-xl border-2 border-emerald-100 p-4 hover:border-emerald-300 transition-colors duration-150"
              :class="agreed ? 'bg-emerald-50 border-emerald-400' : ''"
              @click="agreed = !agreed"
            >
              <input
                type="checkbox"
                :checked="agreed"
                class="mt-0.5 w-5 h-5 accent-emerald-600 cursor-pointer"
                @click.stop
                @change="agreed = $event.target.checked"
              >
              <span class="text-sm font-medium text-emerald-800">{{ $t('disclaimer.agree') }}</span>
            </label>

            <button
              :disabled="!agreed"
              class="w-full mt-4 px-4 py-3 rounded-xl font-semibold text-sm transition-[background,opacity,transform] duration-150"
              :class="agreed
                ? 'bg-emerald-700 text-white hover:bg-emerald-800 active:scale-[0.98] shadow-lg shadow-emerald-200 cursor-pointer'
                : 'bg-emerald-100 text-emerald-400 cursor-not-allowed'"
              @click="proceed"
            >
              {{ $t('disclaimer.proceed') }}
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