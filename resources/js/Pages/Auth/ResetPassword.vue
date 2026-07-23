<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  token: String,
  email: String,
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const showPassword = ref(false)
const showConfirm = ref(false)

const submit = () => {
  form.post(route('password.update'), {
    preserveState: false,
  })
}
</script>

<template>
  <Head title="Reset Password" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <Link :href="route('home')" class="inline-flex items-center gap-2 mb-4">
          <img src="/images/logo/alalay-logo.png" alt="ALALAY" class="h-10 w-auto">
        </Link>
        <h1 class="text-2xl font-bold text-emerald-900">Reset Password</h1>
        <p class="text-emerald-600 text-sm mt-1">Enter your new password</p>
      </div>

      <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8">
        <form @submit.prevent="submit" class="space-y-5">
          <div v-if="form.errors.email" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            {{ form.errors.email }}
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-emerald-900 mb-1.5">Email Address</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              readonly
              class="w-full px-4 py-2.5 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-900 text-sm cursor-not-allowed"
            />
          </div>

          <div>
            <label for="password" class="block text-sm font-medium text-emerald-900 mb-1.5">New Password</label>
            <div class="relative">
              <input
                :type="showPassword ? 'text' : 'password'"
                id="password"
                v-model="form.password"
                autocomplete="new-password"
                placeholder="Enter new password"
                class="w-full px-4 py-2.5 pr-11 rounded-lg border text-emerald-900 placeholder-emerald-400 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                :class="form.errors.password ? 'border-red-300 bg-red-50' : 'border-emerald-200 bg-emerald-50/50'"
                @input="form.errors.password = null"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-400 hover:text-emerald-600 transition-colors"
                @click="showPassword = !showPassword"
              >
                <svg v-if="!showPassword" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p v-if="form.errors.password" class="text-xs text-red-500 mt-1.5">{{ form.errors.password }}</p>
          </div>

          <div>
            <label for="password_confirmation" class="block text-sm font-medium text-emerald-900 mb-1.5">Confirm New Password</label>
            <div class="relative">
              <input
                :type="showConfirm ? 'text' : 'password'"
                id="password_confirmation"
                v-model="form.password_confirmation"
                autocomplete="new-password"
                placeholder="Confirm new password"
                class="w-full px-4 py-2.5 pr-11 rounded-lg border text-emerald-900 placeholder-emerald-400 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                :class="form.errors.password_confirmation ? 'border-red-300 bg-red-50' : 'border-emerald-200 bg-emerald-50/50'"
                @input="form.errors.password_confirmation = null"
              />
              <button
                type="button"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-emerald-400 hover:text-emerald-600 transition-colors"
                @click="showConfirm = !showConfirm"
              >
                <svg v-if="!showConfirm" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                </svg>
                <svg v-else class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                </svg>
              </button>
            </div>
            <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 mt-1.5">{{ form.errors.password_confirmation }}</p>
          </div>

          <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex items-center justify-center gap-2 bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-semibold text-sm transition-all"
            :class="form.processing ? 'opacity-60 cursor-not-allowed' : 'hover:bg-emerald-700 active:bg-emerald-800 shadow-lg shadow-emerald-200'"
          >
            <svg v-if="form.processing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
            </svg>
            <svg v-else class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            <span>{{ form.processing ? 'Resetting...' : 'Reset Password' }}</span>
          </button>
        </form>
      </div>

      <p class="text-center text-xs text-emerald-500 mt-6">
        Municipality of General Mamerto Natividad, Nueva Ecija
      </p>
    </div>
  </div>
</template>
