<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import { usePolling } from '@/Composables/usePolling'
import { useFieldValidation } from '@/Composables/useFieldValidation'
import DocumentScanner from '@/Components/Application/DocumentScanner.vue'

const props = defineProps({
  application: Object,
  documents: Array,
  reviews: Array,
  resubmission_docs_required: Array,
})

const homeUrl = route('home')
const trackUrl = route('track')

const toast = ref(null)
let toastTimer = null

function showToast(message, type) {
  toast.value = { message, type }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = null }, 4000)
}

watch(() => usePage().props.flash, (val) => {
  if (val?.success) showToast(val.success, 'success')
  if (val?.error) showToast(val.error, 'error')
}, { immediate: true })

watch(() => usePage().props.errors, (errors) => {
  if (errors?.documents) showToast(errors.documents, 'error')
}, { deep: true })

onBeforeUnmount(() => clearTimeout(toastTimer))

const hasApplication = computed(() => !!props.application)
const isReturned = computed(() => props.application?.status === 'returned_to_applicant')

const pollParams = computed(() => {
  if (!props.application?.reference_code) return { reference_code: '' }
  return { reference_code: props.application.reference_code }
})

const { lastChecked } = usePolling(
  route('track.poll'),
  pollParams,
  (data) => {
    if (data.changed && props.application?.reference_code) {
      router.reload({ only: ['application', 'reviews'] })
    }
  },
  20,
  { enabled: () => !!props.application?.reference_code },
)

watch(() => props.application?.reference_code, () => {
  lastChecked.value = null
})

const lookupForm = useForm({
  reference_code: '',
})

const refCodeValid = useFieldValidation(
  route('validate.reference-code'),
  () => lookupForm.reference_code,
  {},
  { debounceMs: 400 },
)

function lookupApplication() {
  const code = lookupForm.reference_code.trim()
  if (!code) return
  router.get(route('track.show', code))
}

const capturedDocs = ref({})
const isSubmitting = ref(false)

function onDocCapture(docId, payload) {
  capturedDocs.value[docId] = payload.file || payload
}

function onDocClear(docId) {
  delete capturedDocs.value[docId]
}

function submitResubmission() {
  const ids = Object.keys(capturedDocs.value)
  if (!ids.length) return

  isSubmitting.value = true
  const fd = new FormData()
  ids.forEach((id, i) => {
    fd.append(`documents[${i}]`, capturedDocs.value[id])
    fd.append(`document_ids[${i}]`, id)
  })

  router.post(route('track.resubmit', props.application.reference_code), fd, {
    preserveState: true,
    preserveScroll: true,
    onError: () => { isSubmitting.value = false },
    onFinish: () => { isSubmitting.value = false },
  })
}

const stageLabels = {
  aics_screening: 'AICS Screening',
  mswdo_review: 'MSWDO Review',
  assistance_coding: 'Assistance Coding',
  voucher_creation: 'Voucher Creation',
  accountant_review: 'Accountant Review',
  treasurer_review: 'Treasurer Review',
  mayors_approval: "Mayor's Approval",
}

const decisionLabels = {
  approved: 'Approved',
  coded: 'Coded',
  voucher_created: 'Created',
  returned: 'Returned',
  pending: 'Pending',
}

const decisionBadgeClass = (decision) => {
  if (decision === 'approved' || decision === 'coded' || decision === 'voucher_created') return 'bg-emerald-100 text-emerald-700'
  if (decision === 'returned') return 'bg-amber-100 text-amber-700'
  return 'bg-gray-100 text-gray-600'
}

const statusConfig = {
  submitted: { label: 'Submitted', color: 'bg-blue-100 text-blue-700' },
  screening: { label: 'Under AICS Screening', color: 'bg-cyan-100 text-cyan-700' },
  returned_to_applicant: { label: 'Returned for Revision', color: 'bg-amber-100 text-amber-700' },
  mswdo_review: { label: 'Under MSWDO Review', color: 'bg-cyan-100 text-cyan-700' },
  social_case_study_uploaded: { label: 'Case Study Uploaded', color: 'bg-indigo-100 text-indigo-700' },
  assistance_coding: { label: 'Assistance Coding', color: 'bg-purple-100 text-purple-700' },
  voucher_creation: { label: 'Voucher Creation', color: 'bg-teal-100 text-teal-700' },
  voucher_checking: { label: 'Voucher Checking', color: 'bg-emerald-100 text-emerald-700' },
  voucher_returned: { label: 'Voucher Returned', color: 'bg-orange-100 text-orange-700' },
  with_treasurer: { label: 'With Treasurer', color: 'bg-blue-100 text-blue-700' },
  budget_checking: { label: 'Budget Checking', color: 'bg-violet-100 text-violet-700' },
  on_hold: { label: 'On Hold', color: 'bg-gray-100 text-gray-700' },
  cheque_ready: { label: 'Cheque Ready', color: 'bg-green-100 text-green-700' },
  claimed: { label: 'Claimed', color: 'bg-gray-100 text-gray-700' },
}

const statusInfo = computed(() =>
  statusConfig[props.application?.status] ?? { label: props.application?.status, color: 'bg-gray-100 text-gray-700' }
)

const timelineSteps = computed(() => {
  if (!props.application) return []
  const steps = []
  const currentStatus = props.application.status

  const latestReview = props.reviews?.[0]
  const isSubmitting = currentStatus === 'submitted' && latestReview?.to_status === 'returned_to_applicant'

  if (currentStatus !== 'submitted' || isSubmitting) {
    const isClaimed = currentStatus === 'claimed'
    steps.push({
      key: currentStatus,
      label: statusConfig[currentStatus]?.label ?? currentStatus,
      isCompleted: isClaimed,
      isCurrent: !isClaimed,
      timestamp: isClaimed ? props.application.claimed_at : null,
    })
  }

  ;(props.reviews ?? []).forEach((r) => {
    steps.push({
      key: r.id ?? r.stage + r.created_at,
      label: stageLabels[r.stage] ?? r.stage,
      isCompleted: true,
      isCurrent: false,
      decision: r.decision,
      timestamp: r.created_at,
    })
  })

  steps.push({
    key: 'submitted',
    label: 'Submitted',
    isCompleted: true,
    isCurrent: currentStatus === 'submitted' && !isSubmitting,
    timestamp: props.application.created_at,
  })

  return steps
})
</script>

<template>
  <Head title="Track Application" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50">
    <Teleport to="body">
      <div v-if="toast"
        class="fixed top-4 right-4 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 max-w-sm"
        :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"
      >
        <i :class="toast.type === 'success' ? 'pi pi-check-circle' : 'pi pi-exclamation-circle'"></i>
        {{ toast.message }}
      </div>
    </Teleport>
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
              <div v-for="(step, i) in timelineSteps" :key="step.key" class="relative flex gap-4 pb-6 last:pb-0">
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
                    <span v-if="step.decision" class="text-[10px] px-1.5 py-0.5 rounded-full font-semibold uppercase tracking-wider" :class="decisionBadgeClass(step.decision)">
                      {{ decisionLabels[step.decision] ?? step.decision }}
                    </span>
                    <span v-if="step.isCurrent" class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold uppercase tracking-wider">Current</span>
                  </div>
                  <div v-if="!step.isCurrent" class="text-xs text-gray-400 mt-1">{{ step.timestamp ?? props.application.created_at }}</div>
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
          <div v-if="props.application.resubmission_remarks" class="text-sm bg-amber-50 rounded-lg p-3 mb-4 space-y-1">
            <p class="font-semibold text-amber-800">Remark <span v-if="props.application.reviewer_role" class="text-xs bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full ml-1">{{ props.application.reviewer_role }}</span></p>
            <p class="text-amber-700">{{ props.application.resubmission_remarks }}</p>
          </div>

          <div class="space-y-4 mb-6">
            <div v-for="doc in resubmission_docs_required" :key="doc.id" class="border border-amber-200 rounded-xl p-4 bg-amber-50/50">
              <DocumentScanner
                :docName="doc.doc_name"
                :required="true"
                :captureType="doc.capture_type || 'single'"
                :scannerSize="doc.scanner_size || 'a4'"
                @captured="(payload) => onDocCapture(doc.id, payload)"
                @cleared="() => onDocClear(doc.id)"
              />
            </div>
          </div>

          <div class="flex justify-end">
            <button
              @click="submitResubmission"
              :disabled="isSubmitting || Object.keys(capturedDocs).length !== resubmission_docs_required.length"
              class="px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
              :class="isSubmitting ? 'bg-emerald-500' : 'bg-emerald-600 hover:bg-emerald-700'"
            >
              {{ isSubmitting ? 'Submitting...' : 'Submit Resubmission' }}
            </button>
          </div>

          <!-- Resubmission Progress Modal -->
          <Teleport to="body">
            <div
              v-if="isSubmitting"
              class="fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-6"
            >
              <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8">
                <h3 class="text-lg font-bold text-gray-900 mb-1 text-center">Submitting Resubmission</h3>
                <p class="text-sm text-gray-500 mb-6 text-center">Uploading corrected documents...</p>

                <div class="w-full bg-gray-200 rounded-full h-3 mb-4 overflow-hidden">
                  <div class="h-full bg-emerald-600 rounded-full animate-pulse" style="width: 60%" />
                </div>

                <div class="space-y-1.5 mb-4">
                  <div
                    v-for="(doc, i) in resubmission_docs_required"
                    :key="doc.id"
                    class="flex items-center gap-2 text-xs"
                  >
                    <span v-if="capturedDocs[doc.id]" class="text-emerald-600 shrink-0">
                      <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                      </svg>
                    </span>
                    <span v-else class="w-3.5 h-3.5 shrink-0 rounded-full border-2 border-gray-300" />
                    <span :class="capturedDocs[doc.id] ? 'text-gray-700' : 'text-gray-400'">
                      {{ doc.doc_name }}
                    </span>
                  </div>
                </div>

                <p class="text-xs text-gray-400 text-center">Please wait while your documents are being submitted.</p>
              </div>
            </div>
          </Teleport>
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
          <p v-else-if="refCodeValid.isChecking.value && lookupForm.reference_code" class="text-xs text-gray-400 mt-2">Checking...</p>
          <p v-else-if="refCodeValid.isValid.value === false" class="text-xs text-amber-600 mt-2">{{ refCodeValid.message.value }}</p>
        </form>
      </div>

    </main>
  </div>
</template>
