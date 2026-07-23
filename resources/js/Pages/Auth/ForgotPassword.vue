<script setup>
import { ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

const loginUrl = route('login')

const form = useForm({
  email: '',
})

const submit = () => {
  form.post(route('password.email'), {
    preserveState: false,
  })
}
</script>

<template>
  <Head title="Forgot Password" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <Link :href="route('home')" class="inline-flex items-center gap-2 mb-4">
          <img src="/images/logo/alalay-logo.png" alt="ALALAY" class="h-10 w-auto">
        </Link>
        <h1 class="text-2xl font-bold text-emerald-900">Forgot Password</h1>
        <p class="text-emerald-600 text-sm mt-1">Enter your email to receive a reset link</p>
      </div>

      <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8">
        <div v-if="form.wasSuccessful" class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
          <p class="text-sm font-medium text-emerald-800">Password reset link sent!</p>
          <p class="text-sm text-emerald-600 mt-1">Check your email inbox and follow the instructions.</p>
        </div>

        <form v-else @submit.prevent="submit" class="space-y-5">
          <div v-if="form.errors.email" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            {{ form.errors.email }}
          </div>

          <div>
            <label for="email" class="block text-sm font-medium text-emerald-900 mb-1.5">Email Address</label>
            <input
              id="email"
              v-model="form.email"
              type="email"
              autocomplete="email"
              placeholder="you@example.com"
              class="w-full px-4 py-2.5 rounded-lg border text-emerald-900 placeholder-emerald-400 text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
              :class="form.errors.email ? 'border-red-300 bg-red-50' : 'border-emerald-200 bg-emerald-50/50'"
              @input="form.errors.email = null"
            />
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
              <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
            </svg>
            <span>{{ form.processing ? 'Sending...' : 'Send Reset Link' }}</span>
          </button>
        </form>

        <div class="text-center mt-6">
          <Link :href="loginUrl" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">
            ← Back to sign in
          </Link>
        </div>
      </div>

      <p class="text-center text-xs text-emerald-500 mt-6">
        Municipality of General Mamerto Natividad, Nueva Ecija
      </p>
    </div>
  </div>
</template>
