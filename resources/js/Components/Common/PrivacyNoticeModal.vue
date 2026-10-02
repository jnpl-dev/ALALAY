<script setup>
import { ref, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'

const emit = defineEmits(['close', 'openPolicy'])

const show = ref(false)
const agreed = ref(false)

onMounted(() => {
  if (sessionStorage.getItem('privacy_notice_agreed')) {
    emit('close')
    return
  }
  show.value = true
})

function agree() {
  if (!agreed.value) return
  try { sessionStorage.setItem('privacy_notice_agreed', 'true') } catch {}
  show.value = false
  emit('close')
}

function decline() {
  router.visit(route('home'))
}
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="show"
        role="dialog"
        aria-modal="true"
        aria-labelledby="privacy-notice-title"
        class="fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-4 sm:p-6"
      >
        <div class="w-full max-w-2xl bg-white rounded-2xl shadow-2xl flex flex-col max-h-[90vh]">
          <div class="p-6 sm:p-8 pb-0 text-center">
            <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
              <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
              </svg>
            </div>
            <h2 id="privacy-notice-title" class="text-xl font-bold text-emerald-900 mb-2">{{ $t('privacy.notice_title') }}</h2>
            <p class="text-emerald-600 text-sm mb-5">{{ $t('privacy.notice_lead') }}</p>
          </div>

          <div class="px-6 sm:px-8 overflow-y-auto min-h-0">
            <div class="space-y-4 text-sm text-emerald-800">
              <div v-for="item in 6" :key="item" class="flex items-start gap-3">
                <svg class="w-5 h-5 mt-0.5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ $t(`privacy.notice_w${item}`) }}</span>
              </div>
            </div>

            <p class="mt-4 text-sm text-emerald-600">
              {{ $t('privacy.notice_policy_link_prefix') }}
              <button
                type="button"
                class="font-semibold text-emerald-700 underline hover:text-emerald-900 cursor-pointer"
                @click="emit('openPolicy')"
              >
                {{ $t('privacy.notice_policy_link') }}
              </button>
            </p>
          </div>

          <div class="p-6 sm:p-8 pt-5">
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
              <span class="text-sm font-medium text-emerald-800">{{ $t('privacy.notice_agree') }}</span>
            </label>

            <div class="flex gap-3 mt-4">
              <button
                type="button"
                class="flex-1 px-4 py-3 rounded-xl font-semibold text-sm border-2 border-emerald-200 text-emerald-700 hover:bg-emerald-50 transition-[background,transform] duration-150 active:scale-[0.98] cursor-pointer"
                @click="decline"
              >
                {{ $t('privacy.notice_decline') }}
              </button>
              <button
                type="button"
                :disabled="!agreed"
                class="flex-1 px-4 py-3 rounded-xl font-semibold text-sm transition-[background,opacity,transform] duration-150"
                :class="agreed
                  ? 'bg-emerald-700 text-white hover:bg-emerald-800 active:scale-[0.98] shadow-lg shadow-emerald-200 cursor-pointer'
                  : 'bg-emerald-100 text-emerald-400 cursor-not-allowed'"
                @click="agree"
              >
                {{ $t('privacy.notice_proceed') }}
              </button>
            </div>
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
