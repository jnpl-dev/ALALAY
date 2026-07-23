<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'

const page = usePage()

const auth = computed(() => page.props.auth)

const applyUrl = route('apply')
const trackUrl = route('track')

const isScrolled = ref(false)
const mobileMenuOpen = ref(false)
const activeSection = ref('home')

const sections = [
  { id: 'home', label: 'Home' },
  { id: 'about', label: 'About' },
  { id: 'programs', label: 'Assistance Programs' },
  { id: 'how-it-works', label: 'How It Works' },
  { id: 'faqs', label: 'FAQs' },
  { id: 'contact', label: 'Contact' },
]

const scrollToSection = (id) => {
  activeSection.value = id
  mobileMenuOpen.value = false
  if (id === 'home') {
    window.scrollTo({ top: 0, behavior: 'smooth' })
    return
  }
  const el = document.getElementById(id)
  if (el) {
    const offset = 80
    const top = el.getBoundingClientRect().top + window.scrollY - offset
    window.scrollTo({ top, behavior: 'smooth' })
  }
}

const handleScroll = () => {
  isScrolled.value = window.scrollY > 20
  const offsets = sections.map(s => {
    const el = document.getElementById(s.id)
    if (!el) return { id: s.id, offset: Infinity }
    const rect = el.getBoundingClientRect()
    return { id: s.id, offset: Math.abs(rect.top) }
  })
  const closest = offsets.reduce((a, b) => a.offset < b.offset ? a : b)
  if (closest.offset < 300) {
    activeSection.value = closest.id
  }
}

onMounted(() => {
  window.addEventListener('scroll', handleScroll)
})
onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll)
})
</script>

<template>
  <Head title="ALALAY" />

  <div class="min-h-screen bg-white">
    <nav
      class="fixed top-0 left-0 right-0 z-50 transition-[background,box-shadow,border-color] duration-200"
      :class="isScrolled ? 'bg-white/95 backdrop-blur-md shadow-sm border-b border-emerald-100' : 'bg-transparent'"
    >
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-20">
          <div class="flex items-center gap-4">
            <button @click="scrollToSection('home')" class="flex items-center gap-3 group shrink-0">
              <img src="/images/logo/alalay-logo.png" alt="ALALAY" class="h-10 w-auto">
              <span class="hidden text-xl font-bold text-emerald-900 sm:block">ALALAY</span>
            </button>
            <div class="items-center hidden gap-2 pl-4 border-l lg:flex border-emerald-200">
              <img src="/images/logo/gmn.png" alt="GMN" class="h-8 opacity-60 ">
              <img src="/images/logo/dswd.png" alt="DSWD" class="h-8 opacity-60 ">
              <img src="/images/logo/AICS.png" alt="AICS" class="h-8 opacity-60 ">
            </div>
          </div>

          <div class="items-center hidden gap-1 md:flex">
            <button
              v-for="s in sections"
              :key="s.id"
              @click="scrollToSection(s.id)"
              class="px-3 py-2 text-sm font-medium transition-[background,color] duration-150 rounded-lg press-feedback"
              :class="activeSection === s.id
                ? 'text-emerald-700 bg-emerald-50'
                : 'text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50/50'"
            >
              {{ s.label }}
            </button>
          </div>

          <div class="flex items-center gap-3">
            <Link
              :href="applyUrl"
              class="hidden sm:inline-flex items-center gap-2 bg-emerald-700 text-white px-5 py-2.5 rounded-xl font-semibold text-sm shadow-lg shadow-emerald-200 hover:bg-emerald-800 active:bg-emerald-900 transition-[background,transform] duration-150 press-feedback"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
              </svg>
              Apply
            </Link>
            <Link
              :href="trackUrl"
              class="hidden sm:inline-flex items-center gap-2 bg-white text-emerald-700 px-5 py-2.5 rounded-xl font-semibold text-sm border-2 border-emerald-200 hover:border-emerald-300 hover:bg-emerald-50 active:bg-emerald-100 transition-[background,border-color,transform] duration-150 press-feedback"
            >
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
              Track
            </Link>
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="p-2 transition-colors rounded-lg md:hidden text-emerald-600 hover:bg-emerald-50 press-feedback"
            >
              <svg v-if="!mobileMenuOpen" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg v-else class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <Transition
        @enter="(el) => { el.style.animation = 'mobile-menu-in 250ms var(--ease-out) forwards' }"
        @leave="(el) => { el.style.animation = 'mobile-menu-out 200ms var(--ease-out) forwards' }"
      >
        <div
          v-if="mobileMenuOpen"
          class="overflow-hidden bg-white border-t shadow-lg md:hidden border-emerald-100"
        >
          <div class="px-4 py-3 space-y-1">
            <button
              v-for="s in sections"
              :key="s.id"
              @click="scrollToSection(s.id)"
              class="block w-full text-left px-4 py-2.5 rounded-lg text-sm font-medium transition-[background,transform] duration-150 press-feedback"
              :class="activeSection === s.id
                ? 'text-emerald-700 bg-emerald-50'
                : 'text-emerald-600 hover:bg-emerald-50/50'"
            >
              {{ s.label }}
            </button>
            <hr class="my-2 border-emerald-100">
            <div class="flex gap-2 pt-1">
              <Link
                :href="applyUrl"
                class="flex-1 text-center bg-emerald-700 text-white px-4 py-2.5 rounded-xl font-semibold text-sm press-feedback"
              >
                Apply
              </Link>
              <Link
                :href="trackUrl"
                class="flex-1 text-center bg-white text-emerald-700 px-4 py-2.5 rounded-xl font-semibold text-sm border-2 border-emerald-200 press-feedback"
              >
                Track
              </Link>
            </div>
          </div>
        </div>
      </Transition>
    </nav>

    <main>
      <slot />
    </main>

    <footer class="text-white bg-emerald-950">
      <div class="px-4 py-12 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 gap-8 md:grid-cols-4">
          <div class="md:col-span-2">
            <div class="flex items-center gap-3 mb-4">
              <img src="/images/logo/alalay-logo.png" alt="ALALAY" class="h-10 w-auto">
              <div>
                <span class="text-lg font-bold text-white">ALALAY</span>
                <p class="text-xs text-emerald-300">AICS Digital Management System</p>
              </div>
            </div>
            <p class="max-w-md text-sm leading-relaxed text-emerald-200">
              ALALAY is the official digital platform for the Assistance to Individuals
              in Crisis Situation (AICS) program of the Municipality of General Mamerto
              Natividad, Nueva Ecija.
            </p>
          </div>
          <div>
            <h4 class="mb-4 text-sm font-semibold tracking-wider uppercase text-emerald-200">Quick Links</h4>
            <ul class="space-y-2">
              <li><button @click="scrollToSection('about')" class="text-sm transition-[color,transform] duration-150 text-emerald-300 hover:text-white press-feedback">About</button></li>
              <li><button @click="scrollToSection('programs')" class="text-sm transition-[color,transform] duration-150 text-emerald-300 hover:text-white press-feedback">Assistance Programs</button></li>
              <li><button @click="scrollToSection('how-it-works')" class="text-sm transition-[color,transform] duration-150 text-emerald-300 hover:text-white press-feedback">How It Works</button></li>
              <li><button @click="scrollToSection('faqs')" class="text-sm transition-[color,transform] duration-150 text-emerald-300 hover:text-white press-feedback">FAQs</button></li>
              <li><button @click="scrollToSection('contact')" class="text-sm transition-[color,transform] duration-150 text-emerald-300 hover:text-white press-feedback">Contact</button></li>
            </ul>
          </div>
          <div>
            <h4 class="mb-4 text-sm font-semibold tracking-wider uppercase text-emerald-200">Services</h4>
            <ul class="space-y-2">
              <li><Link :href="applyUrl" class="text-sm transition-[color,transform] duration-150 text-emerald-300 hover:text-white press-feedback">Apply for Assistance</Link></li>
              <li><Link :href="trackUrl" class="text-sm transition-[color,transform] duration-150 text-emerald-300 hover:text-white press-feedback">Track Application</Link></li>
            </ul>
            <h4 class="mt-6 mb-4 text-sm font-semibold tracking-wider uppercase text-emerald-200">Agency Partners</h4>
            <div class="flex flex-wrap items-center gap-3">
              <img src="/images/logo/gmn.png" alt="GMN LGU" class="h-10 transition-opacity opacity-80 hover:opacity-100">
              <img src="/images/logo/dswd.png" alt="DSWD" class="h-10 transition-opacity opacity-80 hover:opacity-100">
              <img src="/images/logo/AICS.png" alt="AICS" class="h-10 transition-opacity opacity-80 hover:opacity-100">
            </div>
          </div>
        </div>
        <div class="flex flex-col items-center justify-between gap-4 pt-6 mt-10 border-t border-emerald-800 sm:flex-row">
          <p class="text-xs text-emerald-400">
            &copy; {{ new Date().getFullYear() }} Municipality of General Mamerto Natividad, Nueva Ecija. All rights reserved.
          </p>
          <div class="flex items-center gap-4 text-xs text-emerald-400">
            <span>Data Privacy Notice</span>
          </div>
        </div>
      </div>
    </footer>
  </div>
</template>
