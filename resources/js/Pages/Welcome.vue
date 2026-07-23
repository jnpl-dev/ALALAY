<script setup>
import { ref, reactive, onMounted } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { useScrollReveal } from '@/Composables/useScrollReveal.js'

useScrollReveal()

const programs = [
  {
    title: 'Medical Assistance',
    desc: 'Financial support for hospitalization, medicines, laboratory tests, and other medical expenses for individuals in crisis.',
    icon: 'M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z',
  },
  {
    title: 'Burial Assistance',
    desc: 'Financial assistance for funeral and burial expenses of indigent families who have lost a loved one.',
    icon: 'M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z',
  },
  {
    title: 'Hospital Assistance',
    desc: 'Support for educational expenses including school supplies, uniforms, and other learning materials for students.',
    icon: 'M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342',
  },
]

const steps = [
  { number: '01', title: 'Check Eligibility', desc: 'Verify if you meet the criteria for your chosen assistance program.' },
  { number: '02', title: 'Submit Requirements', desc: 'Prepare and submit the required documents through our online portal.' },
  { number: '03', title: 'Application Review', desc: 'Your application will be reviewed by the MSWDO office for evaluation.' },
  { number: '04', title: 'Get Notified', desc: 'Receive SMS updates on your application status in real-time.' },
  { number: '05', title: 'Claim Assistance', desc: 'Once approved, claim your assistance at the Municipal Hall.' },
]

const faqs = reactive([
  {
    q: 'What is AICS?',
    a: 'AICS stands for Assistance to Individuals in Crisis Situation, a program of the Department of Social Welfare and Development (DSWD) that provides integrated services to individuals and families experiencing crisis situations.',
    open: false,
  },
  {
    q: 'Who can apply for assistance?',
    a: 'Residents of General Mamerto Natividad, Nueva Ecija who are in crisis situation may apply. Priority is given to indigent families, persons with disabilities, senior citizens, and other vulnerable sectors.',
    open: false,
  },
  {
    q: 'What documents do I need to submit?',
    a: 'Required documents vary depending on the type of assistance. Common requirements include a valid ID, barangay certificate of indigency, medical certificate (for medical assistance), and death certificate (for burial assistance).',
    open: false,
  },
  {
    q: 'How long does the application process take?',
    a: 'The processing time varies depending on the type of assistance and completeness of requirements. Generally, applications are processed within 3-5 working days after submission of complete requirements.',
    open: false,
  },
  {
    q: 'How do I track my application?',
    a: 'You can track your application using the reference code provided upon submission. Visit the Track page on our website and enter your reference code to see the real-time status of your application.',
    open: false,
  },
  {
    q: 'Can I apply for multiple types of assistance?',
    a: 'Yes, you may apply for different types of assistance. However, each application is evaluated independently based on the specific criteria and available funds for each program.',
    open: false,
  },
])

const contactForm = useForm({
  name: '',
  email: '',
  message: '',
})

const submitContact = () => {
  contactForm.post(route('home'), {
    preserveScroll: true,
    onSuccess: () => contactForm.reset(),
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
            Municipality of General Mamerto Natividad, Nueva Ecija
            <span class="absolute inset-0 rounded-full animate-shimmer bg-gradient-to-r from-transparent via-white/30 to-transparent"></span>
          </div>
          <h1
            class="mb-6 text-4xl font-bold leading-tight text-gray-900 sm:text-5xl lg:text-6xl"
            :style="heroMounted ? { animation: 'hero-reveal 0.8s var(--ease-out) 0.1s forwards' } : { opacity: 0 }"
          >
            Assistance to Individuals<br />
            <span class="text-emerald-500">in Crisis Situation</span>
          </h1>
          <p
            class="max-w-2xl mb-10 text-lg leading-relaxed text-gray-800 sm:text-xl"
            :style="heroMounted ? { animation: 'hero-reveal 0.8s var(--ease-out) 0.2s forwards' } : { opacity: 0 }"
          >
            ALALAY is the official digital platform that streamlines AICS applications,
            enabling real-time tracking and seamless delivery of assistance to those
            who need it most.
          </p>
          <div
            class="flex flex-col gap-4 sm:flex-row"
            :style="heroMounted ? { animation: 'hero-reveal 0.8s var(--ease-out) 0.3s forwards' } : { opacity: 0 }"
          >
            <Link
              :href="applyUrl"
              class="inline-flex items-center justify-center gap-2 bg-emerald-600 text-white px-8 py-3.5 rounded-xl font-bold text-base hover:bg-emerald-600 active:bg-emerald-700 transition-[background,transform] duration-150 shadow-lg shadow-emerald-500/25 press-feedback"
            >
              Apply for Assistance
              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
              </svg>
            </Link>
            <Link
              :href="trackUrl"
              class="inline-flex items-center justify-center gap-2 border-2 border-emerald-500 text-emerald-600 px-8 py-3.5 rounded-xl font-semibold text-base hover:bg-emerald-50 active:bg-emerald-100 transition-[background,border-color,transform] duration-150 press-feedback"
            >
              Track Application
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
          <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">About ALALAY</span>
          <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
            Serving the Community with Compassion
          </h2>
          <p class="max-w-2xl mx-auto text-gray-700">
            ALALAY is the digital transformation initiative of the Local Government Unit of
            General Mamerto Natividad to modernize and streamline the AICS program.
          </p>
        </div>
        <div class="grid gap-8 md:grid-cols-3">
          <div class="p-8 border bg-emerald-50 rounded-2xl border-emerald-200 animate-reveal animate-stagger-1 hover-lift">
            <div class="flex items-center justify-center mb-5 w-14 h-14 bg-emerald-100 rounded-xl">
              <svg class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
              </svg>
            </div>
            <h3 class="mb-3 text-lg font-bold text-emerald-900">Who We Serve</h3>
            <p class="text-sm leading-relaxed text-emerald-700">
              We serve the residents of General Mamerto Natividad, with priority given to
              indigent families, senior citizens, persons with disabilities, and individuals
              facing crisis situations.
            </p>
          </div>
          <div class="p-8 border bg-emerald-50 rounded-2xl border-emerald-200 animate-reveal animate-stagger-2 hover-lift">
            <div class="flex items-center justify-center mb-5 w-14 h-14 bg-emerald-100 rounded-xl">
              <svg class="w-7 h-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
              </svg>
            </div>
            <h3 class="mb-3 text-lg font-bold text-emerald-600">Our Mission</h3>
            <p class="text-sm leading-relaxed text-emerald-500">
              To provide timely, efficient, and compassionate assistance to individuals and
              families in crisis through a transparent and streamlined digital process.
            </p>
          </div>
          <div class="p-8 border bg-emerald-50 rounded-2xl border-emerald-200 animate-reveal animate-stagger-3 hover-lift">
            <div class="flex items-center justify-center mb-5 w-14 h-14 bg-emerald-100 rounded-xl">
              <svg class="w-7 h-7 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21v-8.25M15.75 21v-8.25M8.25 21v-8.25M3 9l9-6 9 6m-1.5 12V10.332A48.36 48.36 0 0012 9.75c-2.551 0-5.056.2-7.5.582V21M3 21h18M12 6.75h.008v.008H12V6.75z" />
              </svg>
            </div>
            <h3 class="mb-3 text-lg font-bold text-emerald-800">Our Vision</h3>
            <p class="text-sm leading-relaxed text-emerald-600">
              A community where no individual in crisis is left behind, empowered by a
              responsive and technology-driven social welfare system.
            </p>
          </div>
        </div>
      </div>
    </section>

    <section id="programs" class="py-20 bg-white">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-16 text-center animate-reveal">
          <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">Assistance Programs</span>
          <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
            Types of Assistance Available
          </h2>
          <p class="max-w-2xl mx-auto text-gray-700">
            The AICS program offers a range of assistance to address various needs of
            individuals and families in crisis situations.
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
              class="flex items-center justify-center w-12 h-12 mb-4 rounded-xl"
              :class="i % 2 === 0 ? 'bg-emerald-100 text-emerald-600' : 'bg-emerald-100 text-emerald-500'"
            >
              <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" :d="p.icon" />
              </svg>
            </div>
            <h3 class="mb-2 text-base font-bold text-gray-800">{{ p.title }}</h3>
            <p class="text-sm leading-relaxed text-gray-700">{{ p.desc }}</p>
          </div>
        </div>
      </div>
    </section>

    <section id="how-it-works" class="py-20 bg-white">
      <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
        <div class="mb-16 text-center animate-reveal">
          <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">How It Works</span>
          <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
            Simple Steps to Get Assistance
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
              <h3 class="mb-2 text-sm font-bold text-gray-700">{{ step.title }}</h3>
              <p class="text-xs leading-relaxed text-gray-700">{{ step.desc }}</p>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="faqs" class="py-20 bg-white">
      <div class="max-w-3xl px-4 mx-auto sm:px-6 lg:px-8">
        <div class="mb-12 text-center animate-reveal">
          <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">FAQs</span>
          <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
            Frequently Asked Questions
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
              <span class="pr-4 text-sm font-semibold text-gray-800">{{ faq.q }}</span>
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
                {{ faq.a }}
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
            <span class="text-sm font-semibold tracking-widest uppercase text-emerald-500">Contact</span>
            <h2 class="mt-3 mb-4 text-3xl font-bold text-gray-800 sm:text-4xl">
              Get in Touch
            </h2>
            <p class="mb-8 leading-relaxed text-gray-700">
              Have questions or need assistance? Visit our office or reach out to us through
              the following channels.
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
                  <h4 class="text-sm font-semibold text-gray-800">Address</h4>
                  <p class="text-sm text-gray-700">Municipal Hall, General Mamerto Natividad, Nueva Ecija</p>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 rounded-xl shrink-0">
                  <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-800">Email</h4>
                  <p class="text-sm text-gray-700">mswdo@gmn.gov.ph</p>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 rounded-xl shrink-0">
                  <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-800">Phone</h4>
                  <p class="text-sm text-gray-700">(044) 123-4567</p>
                </div>
              </div>
              <div class="flex items-start gap-4">
                <div class="flex items-center justify-center w-10 h-10 bg-emerald-100 rounded-xl shrink-0">
                  <svg class="w-5 h-5 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-semibold text-gray-800">Office Hours</h4>
                  <p class="text-sm text-gray-700">Monday – Friday, 8:00 AM – 5:00 PM</p>
                </div>
              </div>
            </div>
          </div>
          <div class="p-8 border bg-emerald-50 rounded-2xl border-emerald-200 animate-reveal animate-stagger-2">
            <h3 class="mb-4 text-lg font-bold text-emerald-900">Send Us a Message</h3>
            <form @submit.prevent="submitContact" class="space-y-4">
              <div>
                <label for="contact-name" class="block text-sm font-medium text-emerald-800 mb-1.5">Full Name</label>
                <input
                  id="contact-name"
                  v-model="contactForm.name"
                  type="text"
                  class="w-full px-4 py-2.5 rounded-lg border border-emerald-200 bg-white text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400"
                  placeholder="Juan Dela Cruz"
                />
              </div>
              <div>
                <label for="contact-email" class="block text-sm font-medium text-emerald-800 mb-1.5">Email Address</label>
                <input
                  id="contact-email"
                  v-model="contactForm.email"
                  type="email"
                  class="w-full px-4 py-2.5 rounded-lg border border-emerald-200 bg-white text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400"
                  placeholder="juan@example.com"
                />
              </div>
              <div>
                <label for="contact-message" class="block text-sm font-medium text-emerald-800 mb-1.5">Message</label>
                <textarea
                  id="contact-message"
                  v-model="contactForm.message"
                  rows="4"
                  class="w-full px-4 py-2.5 rounded-lg border border-emerald-200 bg-white text-gray-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400 resize-none"
                  placeholder="How can we help you?"
                ></textarea>
              </div>
              <button
                type="submit"
                :disabled="contactForm.processing"
                class="w-full bg-emerald-500 text-white px-6 py-2.5 rounded-xl font-semibold text-sm hover:bg-emerald-600 active:bg-emerald-600 transition-[background,transform] duration-150 press-feedback"
                :class="contactForm.processing ? 'opacity-60 cursor-not-allowed' : ''"
              >
                {{ contactForm.processing ? 'Sending...' : 'Send Message' }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </section>
  </PublicLayout>
</template>
