<script setup>
import { Head, Link } from '@inertiajs/vue3'

const loginUrl = route('login')
const applyUrl = route('apply')
const trackUrl = route('track')

const stats = [
  { label: 'Applications Processed', value: '1,200+' },
  { label: 'Beneficiaries Served', value: '850+' },
  { label: 'Municipal Barangays', value: '18' },
  { label: 'Years of Service', value: '5+' },
]

const features = [
  {
    icon: '📋',
    title: 'Online Application',
    desc: 'Submit your AICS application from anywhere, anytime. No need to visit the office.',
  },
  {
    icon: '🔍',
    title: 'Real-Time Tracking',
    desc: 'Track your application status using your unique reference code.',
  },
  {
    icon: '📱',
    title: 'SMS Notifications',
    desc: 'Receive instant SMS updates at every stage of your application.',
  },
  {
    icon: '🔒',
    title: 'Secure & Private',
    desc: 'Your personal data is protected in compliance with the Data Privacy Act.',
  },
]
</script>

<template>
  <Head title="Welcome" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50">
    <nav class="border-b border-emerald-100 bg-white/80 backdrop-blur-sm sticky top-0 z-50">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-sm">A</span>
          </div>
          <span class="font-semibold text-emerald-900 text-lg">ALALAY</span>
        </div>
        <div class="flex items-center gap-3">
          <Link
            :href="applyUrl"
            class="text-sm text-emerald-700 hover:text-emerald-900 font-medium px-3 py-2 rounded-lg hover:bg-emerald-50 transition-colors"
          >
            Apply
          </Link>
          <Link
            :href="trackUrl"
            class="text-sm text-emerald-700 hover:text-emerald-900 font-medium px-3 py-2 rounded-lg hover:bg-emerald-50 transition-colors"
          >
            Track
          </Link>
          <Link
            :href="loginUrl"
            class="text-sm bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 font-medium transition-colors"
          >
            Staff Login
          </Link>
        </div>
      </div>
    </nav>

    <main>
      <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pt-20 pb-16 text-center">
        <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-800 text-sm font-medium px-4 py-1.5 rounded-full mb-6">
          <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
          Municipality of General Mamerto Natividad, Nueva Ecija
        </div>
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-emerald-900 leading-tight mb-6">
          Assistance to Individuals<br />
          in Crisis Situation
        </h1>
        <p class="text-lg sm:text-xl text-emerald-700 max-w-2xl mx-auto mb-10 leading-relaxed">
          ALALAY is the digital AICS management system of GMN — streamline applications,
          track progress in real-time, and receive SMS notifications at every step.
        </p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
          <Link
            :href="applyUrl"
            class="inline-flex items-center justify-center gap-2 bg-emerald-600 text-white px-8 py-3.5 rounded-xl font-semibold text-base hover:bg-emerald-700 active:bg-emerald-800 transition-colors shadow-lg shadow-emerald-200"
          >
            <span>Apply for Assistance</span>
            <span class="text-lg">→</span>
          </Link>
          <Link
            :href="trackUrl"
            class="inline-flex items-center justify-center gap-2 bg-white text-emerald-700 px-8 py-3.5 rounded-xl font-semibold text-base border-2 border-emerald-200 hover:border-emerald-300 hover:bg-emerald-50 active:bg-emerald-100 transition-colors"
          >
            <span>Track Application</span>
            <span class="text-lg">→</span>
          </Link>
          <Link
            :href="loginUrl"
            class="inline-flex items-center justify-center gap-2 bg-emerald-800 text-white px-8 py-3.5 rounded-xl font-semibold text-base hover:bg-emerald-900 active:bg-emerald-950 transition-colors shadow-lg shadow-emerald-300"
          >
            <span>Staff Login</span>
            <span class="text-lg">→</span>
          </Link>
        </div>
      </section>

      <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-20">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 sm:gap-6">
          <div
            v-for="stat in stats"
            :key="stat.label"
            class="bg-white rounded-xl p-6 text-center border border-emerald-100 shadow-sm"
          >
            <div class="text-2xl sm:text-3xl font-bold text-emerald-600 mb-1">{{ stat.value }}</div>
            <div class="text-sm text-emerald-600/70">{{ stat.label }}</div>
          </div>
        </div>
      </section>

      <section class="bg-white py-16 border-t border-emerald-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <h2 class="text-2xl sm:text-3xl font-bold text-emerald-900 text-center mb-12">
            How It Works
          </h2>
          <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div
              v-for="(feat, i) in features"
              :key="feat.title"
              class="text-center"
            >
              <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center text-2xl mx-auto mb-4">
                {{ feat.icon }}
              </div>
              <div class="text-base font-semibold text-emerald-900 mb-2">{{ feat.title }}</div>
              <div class="text-sm text-emerald-600/70 leading-relaxed">{{ feat.desc }}</div>
            </div>
          </div>
        </div>
      </section>

      <section class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
        <h2 class="text-2xl sm:text-3xl font-bold text-emerald-900 mb-4">
          Ready to Get Started?
        </h2>
        <p class="text-emerald-700 mb-8 max-w-lg mx-auto">
          Submit your application online or visit the MSWDO office at the Municipal Hall.
        </p>
        <Link
          :href="applyUrl"
          class="inline-flex items-center gap-2 bg-emerald-600 text-white px-8 py-3.5 rounded-xl font-semibold hover:bg-emerald-700 transition-colors shadow-lg shadow-emerald-200"
        >
          <span>Start Your Application</span>
          <span class="text-lg">→</span>
        </Link>
      </section>
    </main>

    <footer class="border-t border-emerald-100 bg-white">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-center text-sm text-emerald-500">
        <p>&copy; {{ new Date().getFullYear() }} Municipality of General Mamerto Natividad, Nueva Ecija. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>
