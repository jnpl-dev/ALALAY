<script setup>
import { useI18n } from 'vue-i18n'
import { ref } from 'vue'

const { locale } = useI18n()
const open = ref(false)

function switchLanguage(lang) {
  locale.value = lang
  try { localStorage.setItem('locale', lang) } catch {}
  open.value = false
}
</script>

<template>
  <div class="relative">
    <button
      @click="open = !open"
      class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-sm font-medium transition-colors"
      :class="open ? 'bg-emerald-100 text-emerald-700' : 'text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50'"
    >
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 016-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.78.147 2.653.255m-4.589 8.495a18.023 18.023 0 01-3.827-5.802" />
      </svg>
      <span>{{ locale === 'fil' ? 'Filipino' : 'English' }}</span>
    </button>

    <Transition
      @enter="(el) => { el.style.animation = 'dropdown-in 150ms ease-out forwards' }"
      @leave="(el) => { el.style.animation = 'dropdown-out 100ms ease-in forwards' }"
    >
      <div
        v-if="open"
        v-click-outside="() => open = false"
        class="absolute right-0 mt-1 w-36 bg-white rounded-xl shadow-lg border border-emerald-100 py-1 overflow-hidden z-50"
      >
        <button
          @click="switchLanguage('en')"
          class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-left transition-colors hover:bg-emerald-50"
          :class="locale === 'en' ? 'font-semibold text-emerald-700 bg-emerald-50' : 'text-gray-700'"
        >
          <span>🇺🇸</span>
          <span>{{ $t('language.english') }}</span>
          <svg v-if="locale === 'en'" class="w-4 h-4 ml-auto text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </button>
        <button
          @click="switchLanguage('fil')"
          class="flex items-center gap-2 w-full px-4 py-2.5 text-sm text-left transition-colors hover:bg-emerald-50"
          :class="locale === 'fil' ? 'font-semibold text-emerald-700 bg-emerald-50' : 'text-gray-700'"
        >
          <span>🇵🇭</span>
          <span>{{ $t('language.filipino') }}</span>
          <svg v-if="locale === 'fil'" class="w-4 h-4 ml-auto text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </button>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
@keyframes dropdown-in {
  from { opacity: 0; transform: translateY(-4px); }
  to { opacity: 1; transform: translateY(0); }
}
@keyframes dropdown-out {
  from { opacity: 1; transform: translateY(0); }
  to { opacity: 0; transform: translateY(-4px); }
}
</style>
