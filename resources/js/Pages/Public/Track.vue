<script setup>
import { ref, computed } from 'vue'
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import DocumentScanner from '@/Components/Application/DocumentScanner.vue'

const props = defineProps({
  application: Object,
  documents: Array,
  reviews: Array,
  resubmission_docs_required: Array,
})

const homeUrl = route('home')
const trackUrl = route('track')

const flash = usePage().props.flash

const hasApplication = computed(() => !!props.application)
const isReturned = computed(() => props.application?.status === 'returned_to_applicant')

const resubmittedDocs = ref([])

const lookupForm = useForm({
  reference_code: '',
})

function lookupApplication() {
  const code = lookupForm.reference_code.trim()
  if (!code) return
  router.get(route('track.show', code))
}

const resubmitForm = useForm({
  documents: [],
  document_ids: [],
})

function onDocCapture(docId, file) {
  const idx = resubmitForm.document_ids.indexOf(docId)
  if (idx >= 0) {
    resubmitForm.documents[idx] = file
  } else {
    resubmitForm.document_ids.push(docId)
    resubmitForm.documents.push(file)
  }
  if (!resubmittedDocs.value.includes(docId)) {
    resubmittedDocs.value.push(docId)
  }
}

function onDocClear(docId) {
  const idx = resubmitForm.document_ids.indexOf(docId)
  if (idx >= 0) {
    resubmitForm.document_ids.splice(idx, 1)
    resubmitForm.documents.splice(idx, 1)
  }
  resubmittedDocs.value = resubmittedDocs.value.filter(id => id !== docId)
}

function submitResubmission() {
  resubmitForm.post(route('track.resubmit', props.application.reference_code), {
    preserveScroll: true,
  })
}

const statusConfig = {
  submitted: { label: 'Submitted', color: 'bg-blue-100 text-blue-700' },
  returned_to_applicant: { label: 'Returned for Revision', color: 'bg-amber-100 text-amber-700' },
  resubmitted: { label: 'Resubmitted', color: 'bg-purple-100 text-purple-700' },
  aics_review: { label: 'Under AICS Review', color: 'bg-indigo-100 text-indigo-700' },
  mswdo_review: { label: 'Under MSWDO Review', color: 'bg-cyan-100 text-cyan-700' },
  approved: { label: 'Approved', color: 'bg-emerald-100 text-emerald-700' },
  claim_ready: { label: 'Ready for Claiming', color: 'bg-green-100 text-green-700' },
  claimed: { label: 'Claimed', color: 'bg-gray-100 text-gray-700' },
}

const statusInfo = computed(() =>
  statusConfig[props.application?.status] ?? { label: props.application?.status, color: 'bg-gray-100 text-gray-700' }
)

const timelineSteps = computed(() => {
  if (!props.reviews?.length && !props.application) return []
  const steps = []
  const allStatuses = ['submitted', 'aics_review', 'mswdo_review', 'approved', 'claim_ready', 'claimed']
  const currentStatus = props.application?.status
  const seen = new Set()
  props.reviews?.forEach(r => seen.add(r.to_status))
  allStatuses.forEach((s, i) => {
    const review = props.reviews?.find(r => r.to_status === s)
    const isCompleted = seen.has(s) || (s === 'submitted' && props.application?.created_at)
    const isCurrent = s === currentStatus
    steps.push({
      status: s,
      label: statusConfig[s]?.label || s,
      isCompleted: !!isCompleted,
      isCurrent: !!isCurrent,
      review,
      isLastParentApproved: s === 'approved' && !isCompleted && !isCurrent && currentStatus !== 'approved',
    })
  })
  return steps
})
</script>

<template>
  <Head title="Track Application" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50">
    <nav class="border-b border-emerald-100 bg-white/80 backdrop-blur-sm">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
        <Link :href="homeUrl" class="flex items-center gap-2">
          <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center">
            <span class="text-white font-bold text-sm">A</span>
          </div>
          <span class="font-semibold text-emerald-900 text-lg">ALALAY</span>
        </Link>
        <Link :href="homeUrl" class="text-sm text-emerald-700 hover:text-emerald-900 font-medium">
          Back to Home
        </Link>
      </div>
    </nav>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

      <div v-if="hasApplication" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-6 sm:p-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
              <h1 class="text-xl font-bold text-emerald-900">Application Status</h1>
              <p class="text-sm text-gray-500 mt-1">{{ props.application.category_name }}</p>
            </div>
            <span :class="['px-3 py-1.5 rounded-lg text-sm font-semibold', statusInfo.color]">
              {{ statusInfo.label }}
            </span>
          </div>

          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
              <dt class="text-gray-500">Reference Code</dt>
              <dd class="font-mono font-bold text-emerald-900">{{ props.application.reference_code }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Beneficiary</dt>
              <dd class="font-medium">{{ props.application.beneficiary_name }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Date Submitted</dt>
              <dd class="font-medium">{{ props.application.created_at }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">Status</dt>
              <dd class="font-semibold">{{ statusInfo.label }}</dd>
            </div>
          </dl>
        </div>

        <div v-if="timelineSteps.length" class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-6 sm:p-8">
          <h2 class="text-lg font-bold text-emerald-900 mb-6">Application Timeline</h2>
          <div class="relative">
            <div class="absolute left-4 top-2 bottom-2 w-0.5 bg-gray-200" />
            <div class="space-y-0">
              <div v-for="(step, i) in timelineSteps" :key="step.status" class="relative flex gap-4 pb-6 last:pb-0">
                <div class="relative z-10 flex-shrink-0 w-8 h-8 flex items-center justify-center">
                  <div v-if="step.isCompleted" class="w-8 h-8 rounded-full bg-emerald-500 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                  </div>
                  <div v-else-if="step.isCurrent" class="w-8 h-8 rounded-full bg-emerald-100 border-2 border-emerald-500 flex items-center justify-center">
                    <div class="w-2.5 h-2.5 rounded-full bg-emerald-500" />
                  </div>
                  <div v-else class="w-8 h-8 rounded-full bg-gray-100 border-2 border-gray-300 flex items-center justify-center">
                    <div class="w-2 h-2 rounded-full bg-gray-300" />
                  </div>
                </div>
                <div class="flex-1 min-w-0 pt-0.5">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span :class="['text-sm font-semibold', step.isCompleted ? 'text-emerald-700' : step.isCurrent ? 'text-emerald-900' : 'text-gray-400']">
                      {{ step.label }}
                    </span>
                    <span v-if="step.isCurrent" class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold uppercase tracking-wider">Current</span>
                  </div>
                  <div v-if="step.review" class="mt-1 space-y-1">
                    <p v-if="step.review.remarks" class="text-sm text-gray-600">{{ step.review.remarks }}</p>
                    <div class="flex items-center gap-2 text-xs text-gray-400">
                      <span>{{ step.review.reviewed_by }}</span>
                      <span>•</span>
                      <span>{{ step.review.created_at }}</span>
                    </div>
                  </div>
                  <div v-else-if="step.isCompleted && step.status === 'submitted'" class="text-xs text-gray-400 mt-1">
                    {{ props.application.created_at }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div v-if="isReturned && resubmission_docs_required?.length" class="bg-white rounded-2xl shadow-lg border border-amber-200 p-6 sm:p-8">
          <div class="flex items-center gap-2 mb-4">
            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h2 class="text-lg font-bold text-amber-900">Resubmission Required</h2>
          </div>
          <p v-if="props.application.resubmission_remarks" class="text-sm text-amber-700 bg-amber-50 rounded-lg p-3 mb-4">
            {{ props.application.resubmission_remarks }}
          </p>

          <div v-if="flash?.success" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
            <p class="text-sm text-emerald-700">{{ flash.success }}</p>
          </div>

          <div v-if="resubmitForm.errors.documents" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-600">{{ resubmitForm.errors.documents }}</p>
          </div>

          <div class="space-y-4 mb-6">
            <div v-for="docId in resubmission_docs_required" :key="docId" class="border border-amber-200 rounded-xl p-4 bg-amber-50/50">
              <DocumentScanner
                :docName="'Document'"
                :required="true"
                @captured="(file) => onDocCapture(docId, file)"
                @cleared="() => onDocClear(docId)"
              />
            </div>
          </div>

          <div class="flex justify-end">
            <button
              @click="submitResubmission"
              :disabled="resubmitForm.processing || !resubmittedDocs.length"
              class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              :class="resubmitForm.processing ? 'bg-emerald-500' : 'bg-emerald-600 hover:bg-emerald-700'"
            >
              {{ resubmitForm.processing ? 'Submitting...' : 'Submit Resubmission' }}
            </button>
          </div>
        </div>

        <div class="text-center">
          <Link
            :href="trackUrl"
            class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"
          >
            ← Track Another Application
          </Link>
        </div>
      </div>

      <div v-else class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12">
        <div class="text-center mb-8">
          <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-emerald-900 mb-2">Track Your Application</h1>
          <p class="text-emerald-600">Enter your reference code to check your application status.</p>
        </div>

        <div v-if="flash?.success" class="mb-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
          <p class="text-sm text-emerald-700">{{ flash.success }}</p>
        </div>

        <form @submit.prevent="lookupApplication" class="max-w-md mx-auto">
          <div class="flex gap-3">
            <input
              v-model="lookupForm.reference_code"
              type="text"
              placeholder="e.g. GMN-2026-A1B2C3"
              class="flex-1 px-4 py-3 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none uppercase"
              @keyup.enter="lookupApplication"
            />
            <button
              type="submit"
              :disabled="!lookupForm.reference_code.trim()"
              class="px-6 py-3 rounded-xl text-sm font-semibold text-white transition-colors cursor-pointer disabled:opacity-50"
              :class="lookupForm.reference_code.trim() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
            >
              Track
            </button>
          </div>
          <p v-if="lookupForm.errors.reference_code" class="text-xs text-red-500 mt-2">{{ lookupForm.errors.reference_code }}</p>
        </form>
      </div>

    </main>
  </div>
</template>
