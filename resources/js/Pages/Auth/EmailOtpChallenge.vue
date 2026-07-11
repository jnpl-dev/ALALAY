<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()

const homeUrl = route('home')

const form = useForm({
  otp_code: '',
})

const digits = ref(['', '', '', '', '', ''])
const inputRefs = ref([])

const otpString = computed(() => digits.value.join(''))

const submit = () => {
  form.otp_code = otpString.value
  form.post(route('otp.challenge'), {
    preserveState: false,
    preserveScroll: true,
  })
}

const resend = () => {
  form.post(route('otp.resend'), {
    preserveState: true,
    onSuccess: () => {
      digits.value = ['', '', '', '', '', '']
      form.errors.otp_code = null
    },
  })
}

const handleInput = (index, e) => {
  const val = e.target.value
  if (!/^\d*$/.test(val)) {
    e.target.value = digits.value[index]
    return
  }
  digits.value[index] = val.slice(-1)
  if (val && index < 5) {
    inputRefs.value[index + 1]?.focus()
  }
}

const handleKeydown = (index, e) => {
  if (e.key === 'Backspace' && !digits.value[index] && index > 0) {
    inputRefs.value[index - 1]?.focus()
  }
  if (e.key === 'Enter') {
    submit()
  }
}

const handlePaste = (e) => {
  e.preventDefault()
  const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6)
  paste.split('').forEach((char, i) => {
    if (i < 6) digits.value[i] = char
  })
  const nextEmpty = digits.value.findIndex(d => !d)
  const focusIndex = nextEmpty === -1 ? 5 : nextEmpty
  inputRefs.value[focusIndex]?.focus()
}

const setRef = (el, index) => {
  if (el) inputRefs.value[index] = el
}
</script>

<template>
  <Head title="Verify Code" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">

      <div class="text-center mb-8">
        <Link :href="homeUrl" class="inline-flex items-center gap-2 mb-4">
          <div class="w-10 h-10 bg-emerald-600 rounded-xl flex items-center justify-center">
            <span class="text-white font-bold text-lg">A</span>
          </div>
        </Link>
        <h1 class="text-2xl font-bold text-emerald-900">Check Your Email</h1>
        <p class="text-emerald-600 text-sm mt-1">
          A 6-digit verification code has been sent to your email address.
        </p>
      </div>

      <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8">

        <div v-if="page.props.flash?.success" class="bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-3 mb-4">
          {{ page.props.flash.success }}
        </div>

        <div v-if="form.errors.otp_code" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-4">
          {{ form.errors.otp_code }}
        </div>

        <form @submit.prevent="submit">
          <div class="flex justify-center gap-2 sm:gap-3 mb-6">
            <input
              v-for="(digit, i) in digits"
              :key="i"
              :ref="(el) => setRef(el, i)"
              :value="digit"
              type="text"
              inputmode="numeric"
              maxlength="1"
              autocomplete="one-time-code"
              class="w-11 h-12 sm:w-12 sm:h-14 text-center text-lg font-bold rounded-lg border transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              :class="form.errors.otp_code ? 'border-red-300 bg-red-50' : 'border-emerald-200 bg-emerald-50/50'"
              :disabled="form.processing"
              @input="handleInput(i, $event)"
              @keydown="handleKeydown(i, $event)"
              @paste="handlePaste"
            />
          </div>

          <button
            type="submit"
            :disabled="form.processing || otpString.length !== 6"
            class="w-full flex items-center justify-center gap-2 bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all mb-3"
            :class="(form.processing || otpString.length !== 6) ? 'opacity-60 cursor-not-allowed' : 'hover:bg-emerald-700 active:bg-emerald-800 shadow-lg shadow-emerald-200'"
          >
            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <span v-else>Verify Code</span>
          </button>

          <div class="text-center">
            <button
              type="button"
              :disabled="form.processing"
              class="text-sm text-emerald-600 hover:text-emerald-800 font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
              @click="resend"
            >
              Resend Code
            </button>
          </div>
        </form>
      </div>

      <p class="text-center text-xs text-emerald-500 mt-6">
        Municipality of General Mamerto Natividad, Nueva Ecija
      </p>
    </div>
  </div>
</template>
