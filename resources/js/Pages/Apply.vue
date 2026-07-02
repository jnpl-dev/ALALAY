<script setup>
import { Head, Link } from '@inertiajs/vue3'

const homeUrl = route('home')
</script>

<template>
  <Head title="Apply" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50">
    <nav class="border-b border-emerald-100 bg-white/80 backdrop-blur-sm">
      <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <Link :href="homeUrl" class="flex items-center gap-2">
          <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-sm">A</span>
          </div>
          <span class="font-semibold text-emerald-900 text-lg">ALALAY</span>
        </Link>
        <Link
          :href="homeUrl"
          class="text-sm text-emerald-700 hover:text-emerald-900 font-medium"
        >
          Back to Home
        </Link>
      </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
      <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12 text-center">
        <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-6">
          <span class="text-3xl">📋</span>
        </div>
        <h1 class="text-2xl sm:text-3xl font-bold text-emerald-900 mb-3">Apply for Assistance</h1>
        <p class="text-emerald-600 mb-8 leading-relaxed">
          The online application form will be available soon. In the meantime, you may visit
          the MSWDO office at the Municipal Hall of General Mamerto Natividad to apply in person.
        </p>
        <div class="bg-emerald-50 rounded-xl p-6 text-left text-sm text-emerald-800 space-y-2 mb-8">
          <p class="font-semibold">For inquiries:</p>
          <p>📍 Municipal Hall, General Mamerto Natividad, Nueva Ecija</p>
          <p>📞 (044) 123-4567</p>
          <p>📧 mswdo@gmn.gov.ph</p>
        </div>
        <Link
          :href="homeUrl"
          class="inline-flex items-center gap-2 bg-emerald-600 text-white px-6 py-3 rounded-xl font-semibold text-sm hover:bg-emerald-700 transition-colors"
        >
          <span>←</span>
          <span>Back to Home</span>
        </Link>
      </div>
    </main>
  </div>
</template>
