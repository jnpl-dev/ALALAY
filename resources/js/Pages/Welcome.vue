<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { useScrollReveal } from '@/Composables/useScrollReveal.js'
import TurnstileWidget from '@/Components/TurnstileWidget.vue'

const { t } = useI18n()
useScrollReveal()

const programs = [
  {
    titleKey: 'programs.medical',
    descKey: 'programs.medical_desc',
    photo: '/images/assistance/medical.jpg',
    icon: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
  },
  {
    titleKey: 'programs.burial',
    descKey: 'programs.burial_desc',
    photo: '/images/assistance/burial.jpg',
    icon: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
  },
  {
    titleKey: 'programs.hospital',
    descKey: 'programs.hospital_desc',
    photo: '/images/assistance/hospital.jpg',
    icon: 'M15.75 11.25v-4.5m0 4.5v4.5m-4.5-4.5H12m0 0h-.75m.75 0h.75M12 6.75l-3 3m0 0l3 3m-3-3h7.5M3 12c0 8.284 6.716 15 15 15m0 0c-2.12 0-4.12-.44-5.94-1.22M18 27c3.17 0 6.07-1.08 8.36-2.89M18 27V15',
  },
]

const steps = [
  { number: '01', titleKey: 'how_it_works.step1_title', descKey: 'how_it_works.step1_desc' },
  { number: '02', titleKey: 'how_it_works.step2_title', descKey: 'how_it_works.step2_desc' },
  { number: '03', titleKey: 'how_it_works.step3_title', descKey: 'how_it_works.step3_desc' },
  { number: '04', titleKey: 'how_it_works.step4_title', descKey: 'how_it_works.step4_desc' },
  { number: '05', titleKey: 'how_it_works.step5_title', descKey: 'how_it_works.step5_desc' },
]

const faqs = reactive([
  { qKey: 'faqs.q1', aKey: 'faqs.a1', open: false },
  { qKey: 'faqs.q2', aKey: 'faqs.a2', open: false },
  { qKey: 'faqs.q3', aKey: 'faqs.a3', open: false },
  { qKey: 'faqs.q4', aKey: 'faqs.a4', open: false },
  { qKey: 'faqs.q5', aKey: 'faqs.a5', open: false },
  { qKey: 'faqs.q6', aKey: 'faqs.a6', open: false },
])

const contactForm = useForm({
  name: '',
  email: '',
  message: '',
  company_website: '',
  'cf-turnstile-response': '',
})

const onContactTurnstileToken = (token) => {
  contactForm['cf-turnstile-response'] = token
}

const submitContact = () => {
  contactForm.post(route('contact.send'), {
    preserveScroll: true,
    onSuccess: () => {
      contactForm.reset()
      contactForm['cf-turnstile-response'] = ''
    },
  })
}

const heroMounted = ref(false)

onMounted(() => {
  requestAnimationFrame(() => {
    heroMounted.value = true
  })
})

const toggleFaq = (i) => {
  faqs[i].open = !faqs[i].open
}

const applyUrl = route('apply')
const trackUrl = route('track')
</script>

<template>
  <Head title="ALALAY — AICS Digital Management System" />
  <PublicLayout>
    <div class="fixed inset-0 pointer-events-none" style="background-image: linear-gradient(rgba(0,0,0,0.025) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.025) 1px, transparent 1px); background-size: 60px 60px;"></div>
    <section id="home" class="relative flex items-center min-h-screen pt-20 overflow-hidden bg-gradient-to-br from-emerald-50 to-white">
      <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(circle at 25% 50%, #f59e0b 0%, transparent 50%), radial-gradient(circle at 75% 80%, #059669 0%, transparent 50%);"></div>
      <div class="relative px-4 py-20 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="max-w-3xl">
          <div
            class="relative inline-flex items-center gap-2 overflow-hidden bg-emerald-100 text-emerald-800 text-sm font-medium px-4 py-1.5 rounded-full mb-6"
            :style="heroMounted ? { animation: 'hero-reveal 0.8s var(--ease-out) forwards' } : { opacity: 0 }"
          >
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            {{ $t('hero.badge') }}
            <span class="absolute inset-0 rounded-full animate-shimmer bg-gradient-to-r from-transparent via-white/30 to-transparent"></span>
          </div>
          <h1
            class="mb-6 text-4xl font-bold leading-tight text-gray-900 sm:text-5xl lg:text-6xl"
            :style="heroMounted ? { animation: 'hero-reveal 0.8s var(--ease-out) 0.1s forwards' } : { opacity: 0 }"
          >
            {{ $t('hero.title_l1') }}<br />
            <span class="text-emerald-500">{{ $t('hero.title_l2') }}</span>
          </h1>
          <p
            class="max-w-2xl mb-10 text-lg leading-relaxed text-gray-800 sm:text-xl"
            :style="heroMounted ? { animation: 'hero-reveal 0.8s var(--ease-out) 0.2s forwards' } : { opacity: 0 }"
          >
             {{ $t('hero.subtitle') }}
          </p>
          <div
            class="flex flex-col gap-4 sm:flex-row"
            :style="heroMounted ? { animation: 'hero-reveal 0.8s var(--ease-out) 0.3s forwards' } : { opacity: 0 }"
          >
            <Link
              :href="applyUrl"
              class="inline-flex items-center justify-center gap-2 bg-emerald-600 text-white px-8 py-3.5 rounded-xl font-semibold text-base hover:bg-emerald-600 active:bg-emerald-700 transition-[background,transform] duration-150 shadow-lg shadow-emerald-500/25 press-feedback"
            >
               {{ $t('hero.apply') }}
               <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </Link>
            <Link
              :href="trackUrl"
              class="inline-flex items-center justify-center gap-2 border-2 border-emerald-500 text-emerald-600 px-8 py-3.5 rounded-xl font-semibold text-base hover:bg-emerald-50 active:bg-emerald-100 transition-[background,border-color,transform] duration-150 shadow-lg shadow-emerald-500/25 press-feedback"
            >
              {{ $t('hero.track') }}
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </Link>
          </div>
        </div>
      </div>
      <div class="absolute -translate-x-1/2 bottom-8 left-1/2 scroll-indicator">
        <svg class="w-5 h-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
      </div>
    </section>

    <section id="about" class="py-20 bg-white">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-16 text-center animate-reveal">
          <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">{{ $t('about.badge') }}</span>
          <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
            {{ $t('about.title') }}
          </h2>
          <p class="max-w-2xl mx-auto text-gray-700">
            {{ $t('about.subtitle') }}
          </p>
        </div>
        <div class="grid gap-8 md:grid-cols-3">
          <div class="p-8 text-center border bg-emerald-50 rounded-2xl border-emerald-200 animate-reveal animate-stagger-1 hover-lift">
            <div class="flex items-center justify-center mx-auto mb-5 w-20 h-20 bg-emerald-100 rounded-2xl">
              <svg class="w-10 h-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M19.414 14.414C21 12.828 22 11.5 22 9.5a5.5 5.5 0 0 0-9.591-3.676.6.6 0 0 1-.818.001A5.5 5.5 0 0 0 2 9.5c0 2.3 1.5 4 3 5.5l5.535 5.362a2 2 0 0 0 2.879.052 2.12 2.12 0 0 0-.004-3 2.124 2.124 0 1 0 3-3 2.124 2.124 0 0 0 3.004 0 2 2 0 0 0 0-2.828l-1.881-1.882a2.41 2.41 0 0 0-3.409 0l-1.71 1.71a2 2 0 0 1-2.828 0 2 2 0 0 1 0-2.828l2.823-2.762" />
              </svg>
            </div>
            <h3 class="mb-3 text-lg font-bold text-emerald-900">{{ $t('about.who_we_serve') }}</h3>
            <p class="text-sm leading-relaxed text-emerald-700">
              {{ $t('about.who_we_serve_desc') }}
            </p>
          </div>
          <div class="p-8 text-center border bg-emerald-50 rounded-2xl border-emerald-200 animate-reveal animate-stagger-2 hover-lift">
            <div class="flex items-center justify-center mx-auto mb-5 w-20 h-20 bg-emerald-100 rounded-2xl">
              <svg class="w-10 h-10 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" />
                <circle cx="12" cy="12" r="6" />
                <circle cx="12" cy="12" r="2" />
              </svg>
            </div>
            <h3 class="mb-3 text-lg font-bold text-emerald-600">{{ $t('about.mission') }}</h3>
            <p class="text-sm leading-relaxed text-emerald-500">
              {{ $t('about.mission_desc') }}
            </p>
          </div>
          <div class="p-8 text-center border bg-emerald-50 rounded-2xl border-emerald-200 animate-reveal animate-stagger-3 hover-lift">
            <div class="flex items-center justify-center mx-auto mb-5 w-20 h-20 bg-emerald-100 rounded-2xl">
              <svg class="w-10 h-10 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                <circle cx="12" cy="12" r="3" />
              </svg>
            </div>
            <h3 class="mb-3 text-lg font-bold text-emerald-800">{{ $t('about.vision') }}</h3>
            <p class="text-sm leading-relaxed text-emerald-600">
              {{ $t('about.vision_desc') }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <section id="programs" class="py-20 bg-white">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-16 text-center animate-reveal">
          <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">{{ $t('programs.badge') }}</span>
          <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
            {{ $t('programs.title') }}
          </h2>
          <p class="max-w-2xl mx-auto text-gray-700">
            {{ $t('programs.subtitle') }}
          </p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="(p, i) in programs"
            :key="p.title"
            class="p-6 bg-white border border-gray-200 rounded-2xl hover-lift animate-reveal"
            :class="'animate-stagger-' + (i + 1)"
          >
            <div
              class="w-full h-40 mb-4 overflow-hidden rounded-xl bg-emerald-100"
            >
              <img :src="p.photo" :alt="$t(p.titleKey)" class="object-cover w-full h-full">
            </div>
            <h3 class="mb-2 text-base font-bold text-gray-800">{{ $t(p.titleKey) }}</h3>
            <p class="text-sm leading-relaxed text-gray-700">{{ $t(p.descKey) }}</p>
          </div>
        </div>
      </div>
    </section>

    <section id="how-it-works" class="py-20 bg-white">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-16 text-center animate-reveal">
          <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">{{ $t('how_it_works.badge') }}</span>
          <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
            {{ $t('how_it_works.title') }}
          </h2>
          <p class="max-w-2xl mx-auto text-gray-700">
            The application process is designed to be straightforward and accessible to everyone.
          </p>
        </div>
        <div class="relative steps-container animate-reveal">
          <div class="relative grid gap-8 lg:grid-cols-5">
            <div v-for="(step, i) in steps" :key="step.number" class="relative text-center step-item" :style="{ transitionDelay: (i * 400) + 'ms' }">
              <div
                class="relative z-10 flex items-center justify-center w-16 h-16 mx-auto mb-4 rounded-2xl"
                :class="i % 2 === 0 ? 'bg-emerald-100' : 'bg-emerald-100'"
              >
                <span
                  class="text-xl font-bold"
                  :class="i % 2 === 0 ? 'text-emerald-600' : 'text-emerald-500'"
                >{{ step.number }}</span>
              </div>
              <h3 class="mb-2 text-sm font-bold text-gray-700">{{ $t(step.titleKey) }}</h3>
              <p class="text-xs leading-relaxed text-gray-700">{{ $t(step.descKey) }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="faqs" class="py-20 bg-white">
      <div class="max-w-3xl px-4 mx-auto sm:px-6 lg:px-8">
        <div class="mb-12 text-center animate-reveal">
          <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">{{ $t('faqs.badge') }}</span>
          <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
            {{ $t('faqs.title') }}
          </h2>
        </div>
        <div class="space-y-3">
          <div
            v-for="(faq, i) in faqs"
            :key="i"
            class="overflow-hidden transition-all duration-200 bg-white border border-gray-200 rounded-xl"
            :class="faq.open ? 'shadow-md border-emerald-200' : 'hover:border-gray-300'"
          >
            <button
              @click="toggleFaq(i)"
              class="flex items-center justify-between w-full px-6 py-4 text-left"
            >
              <span class="pr-4 text-sm font-semibold text-gray-800">{{ $t(faq.qKey) }}</span>
              <svg
                class="w-5 h-5 transition-transform duration-200 text-emerald-400 shrink-0"
                :class="faq.open ? 'rotate-180' : ''"
                fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
              </svg>
            </button>
            <Transition
              @enter="(el) => { el.style.animation = 'accordion-in 250ms var(--ease-out) forwards' }"
              @leave="(el) => { el.style.animation = 'accordion-out 200ms var(--ease-out) forwards' }"
            >
              <div
                v-if="faq.open"
                class="px-6 pb-4 text-sm leading-relaxed text-gray-800"
              >
                {{ $t(faq.aKey) }}
              </div>
            </Transition>
          </div>
        </div>
      </div>
    </section>

    <section id="contact" class="py-20 bg-white">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="grid items-start gap-12 md:grid-cols-2">
          <div class="animate-reveal animate-stagger-1">
            <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">{{ $t('contact.badge') }}</span>
            <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
              {{ $t('contact.title') }}
            </h2>
            <p class="mb-8 leading-relaxed text-gray-700">
              {{ $t('contact.subtitle') }}
            </p>
            <div class="space-y-5">
              <div class="flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 rounded-xl shrink-0">
                  <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-800">{{ $t('contact.address') }}</h4>
                  <p class="text-sm text-gray-700">{{ $t('contact.address_value') }}</p>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 rounded-xl shrink-0">
                  <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-800">{{ $t('contact.email') }}</h4>
                  <p class="text-sm text-gray-700">{{ $t('contact.email_value') }}@{{ $t('contact.email_domain') }}</p>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 rounded-xl shrink-0">
                  <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-800">{{ $t('contact.phone') }}</h4>
                  <p class="text-sm text-gray-700">{{ $t('contact.phone_value') }}</p>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 rounded-xl shrink-0">
                  <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-800">{{ $t('contact.hours') }}</h4>
                  <p class="text-sm text-gray-700">{{ $t('contact.hours_value') }}</p>
                </div>
              </div>
            </div>
          </div>
          <div class="p-8 border bg-emerald-50 rounded-2xl border-emerald-200 animate-reveal animate-stagger-2">
            <h3 class="mb-4 text-lg font-bold text-emerald-900">{{ $t('contact.form_title') }}</h3>
            <form @submit.prevent="submitContact" class="space-y-4">
              <div>
                <label for="contact-name" class="block text-sm font-medium text-emerald-800 mb-1.5">{{ $t('contact.form_name') }}</label>
                <input
                  id="contact-name"
                  v-model="contactForm.name"
                  type="text"
                  class="w-full px-4 py-2.5 rounded-lg border border-emerald-200 bg-white text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400"
                  :placeholder="$t('contact.form_name_placeholder')"
                />
              </div>
              <div>
                <label for="contact-email" class="block text-sm font-medium text-emerald-800 mb-1.5">{{ $t('contact.form_email') }}</label>
                <input
                  id="contact-email"
                  v-model="contactForm.email"
                  type="email"
                  class="w-full px-4 py-2.5 rounded-lg border border-emerald-200 bg-white text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400"
                  :placeholder="$t('contact.form_email_placeholder') + '@' + $t('contact.form_email_domain')"
                />
              </div>
              <div>
                <label for="contact-message" class="block text-sm font-medium text-emerald-800 mb-1.5">{{ $t('contact.form_message') }}</label>
                <textarea
                  id="contact-message"
                  v-model="contactForm.message"
                  rows="4"
                  class="w-full px-4 py-2.5 rounded-lg border border-emerald-200 bg-white text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 resize-none"
                  :placeholder="$t('contact.form_message_placeholder')"
                ></textarea>
              </div>
              <div class="hidden">
                <label for="contact-website">Website</label>
                <input
                  id="contact-website"
                  v-model="contactForm.company_website"
                  type="text"
                  tabindex="-1"
                  autocomplete="off"
                />
              </div>
              <TurnstileWidget action="contact" @token="onContactTurnstileToken" class="flex justify-center" />
              <button
                type="submit"
                :disabled="contactForm.processing"
                class="w-full bg-emerald-500 text-white px-6 py-2.5 rounded-xl font-semibold text-sm hover:bg-emerald-600 active:bg-emerald-600 transition-[background,transform] duration-150 press-feedback"
                :class="contactForm.processing ? 'opacity-60 cursor-not-allowed' : ''"
              >
                {{ contactForm.processing ? $t('contact.form_sending') : $t('contact.form_submit') }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
