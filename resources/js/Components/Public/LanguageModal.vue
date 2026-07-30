<script setup>
import { ref, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'

const emit = defineEmits(['close'])

const { locale } = useI18n()
const show = ref(false)

onMounted(() => {
  const chosen = localStorage.getItem('locale')
  if (!chosen) {
    show.value = true
  }
})

function select(lang) {
  locale.value = lang
  try { localStorage.setItem('locale', lang) } catch {}
  show.value = false
  emit('close')
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="show"
        class="fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-6"
      >
        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 sm:p-10 text-center">
          <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
            <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.78.147 2.653.255m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" />
            </svg>
          </div>
          <h2 class="text-xl font-bold text-emerald-900 mb-2">{{ $t('language.modal_title') }}</h2>
          <p class="text-emerald-600 text-sm mb-8">{{ $t('language.modal_subtitle') }}</p>

          <div class="grid grid-cols-2 gap-4">
            <button
              @click="select('en')"
              class="p-5 rounded-xl border-2 border-emerald-200 hover:border-emerald-500 hover:bg-emerald-50 transition-all duration-200 cursor-pointer text-center group"
            >
              <span class="block text-3xl mb-2">🇺🇸</span>
              <span class="block text-sm font-semibold text-emerald-800 group-hover:text-emerald-600">{{ $t('language.english') }}</span>
            </button>
            <button
              @click="select('fil')"
              class="p-5 rounded-xl border-2 border-emerald-200 hover:border-emerald-500 hover:bg-emerald-50 transition-all duration-200 cursor-pointer text-center group"
            >
              <span class="block text-3xl mb-2">🇵🇭</span>
              <span class="block text-sm font-semibold text-emerald-800 group-hover:text-emerald-600">{{ $t('language.filipino') }}</span>
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
