<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import { useI18n } from 'vue-i18n'
import { usePolling } from '@/Composables/usePolling'
import { useFieldValidation } from '@/Composables/useFieldValidation'
import DocumentScanner from '@/Components/Application/DocumentScanner.vue'

const { t } = useI18n()

const props = defineProps({
  application: Object,
  documents: Array,
  reviews: Array,
  resubmission_docs_required: Array,
  otp_required: Boolean,
  otp_sent: Boolean,
  otp_expired: Boolean,
  otp_attempts: Number,
  otp_resend_count: Number,
  otp_resend_available_at: [String, null],
  otp_resend_limit: Number,
  otp_cooldown_seconds: Number,
  reference_code: String,
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

const hasApplication = computed(() => !!props.application)

const otpForm = useForm({
  otp_code: '',
})

const otpPageErrors = computed(() => usePage().props.errors || {})

const digits = ref(['', '', '', '', '', ''])
const inputRefs = ref([])

const otpString = computed(() => digits.value.join(''))

function clearOtpError() {
  otpForm.errors.otp_code = null
  if (usePage().props.errors) {
    delete usePage().props.errors.otp_code
  }
}

function sendOtp() {
  if (!props.reference_code || !otpResendAllowed.value) return
  router.post(route('track.send-otp', props.reference_code), {}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      clearOtpError()
      nowTs.value = Date.now()
    },
  })
}

const nowTs = ref(Date.now())
const cooldownTimer = ref(null)

const otpResendAvailableAt = computed(() => {
  if (!props.otp_resend_available_at) return null
  const ts = new Date(props.otp_resend_available_at).getTime()
  return Number.isNaN(ts) ? null : ts
})

const otpResendCount = computed(() => Number(props.otp_resend_count ?? 0))
const otpResendLimit = computed(() => Number(props.otp_resend_limit ?? 3))

const otpResendCooldownLeft = computed(() => {
  if (!otpResendAvailableAt.value) return 0
  return Math.max(0, Math.ceil((otpResendAvailableAt.value - nowTs.value) / 1000))
})

const otpResendAllowed = computed(() =>
  otpResendCooldownLeft.value === 0 && otpResendCount.value < otpResendLimit.value
)

const otpResendLabel = computed(() => {
  if (otpResendCount.value >= otpResendLimit.value) return 'Resend limit reached'
  return otpResendCooldownLeft.value > 0
    ? `${new Intl.NumberFormat().format(otpResendCooldownLeft.value)}s`
    : 'Resend OTP'
})

watch(() => [props.otp_resend_available_at, props.otp_sent], () => {
  nowTs.value = Date.now()
  if (cooldownTimer.value) clearInterval(cooldownTimer.value)
  cooldownTimer.value = setInterval(() => { nowTs.value = Date.now() }, 1000)
}, { immediate: true })

onBeforeUnmount(() => {
  clearTimeout(toastTimer)
  if (cooldownTimer.value) clearInterval(cooldownTimer.value)
})

function submitOtp() {
  if (!props.reference_code || otpString.value.length !== 6) return
  otpForm.otp_code = otpString.value
  otpForm.post(route('track.verify-otp', props.reference_code), {
    preserveState: false,
    preserveScroll: true,
  })
}

function handleInput(index, e) {
  clearOtpError()
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

function handleKeydown(index, e) {
  if (e.key === 'Backspace' && !digits.value[index] && index > 0) {
    inputRefs.value[index - 1]?.focus()
  }
  if (e.key === 'Enter') {
    submitOtp()
  }
}

function handlePaste(e) {
  e.preventDefault()
  const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6)
  paste.split('').forEach((char, i) => {
    if (i < 6) digits.value[i] = char
  })
  const nextEmpty = digits.value.findIndex(d => !d)
  const focusIndex = nextEmpty === -1 ? 5 : nextEmpty
  inputRefs.value[focusIndex]?.focus()
}

function setRef(el, index) {
  if (el) inputRefs.value[index] = el
}

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
  aics_screening: () => t('stage.aics_screening'),
  mswdo_review: () => t('stage.mswdo_review'),
  assistance_coding: () => t('stage.assistance_coding'),
  internal_audit_review: () => t('stage.internal_audit_review'),
  voucher_creation: () => t('stage.voucher_creation'),
  budget_checking: () => t('stage.budget_checking'),
  voucher_recording: () => t('stage.voucher_recording'),
  treasurer_acknowledgment: () => t('stage.treasurer_acknowledgment'),
  treasurer_review: () => t('stage.treasurer_review'),
}

const decisionLabels = {
  approved: () => t('decision.approved'),
  coded: () => t('decision.coded'),
  voucher_created: () => t('decision.created'),
  returned: () => t('decision.returned'),
  hold: () => t('decision.hold'),
  pending: () => t('decision.pending'),
}

const decisionBadgeClass = (decision) => {
  if (decision === 'approved' || decision === 'coded' || decision === 'voucher_created') return 'bg-emerald-100 text-emerald-700'
  if (decision === 'returned' || decision === 'hold') return 'bg-amber-100 text-amber-700'
  return 'bg-gray-100 text-gray-600'
}

const statusConfig = {
  submitted: { label: () => t('status.submitted'), color: 'bg-blue-100 text-blue-700' },
  returned_to_applicant: { label: () => t('status.returned_revision'), color: 'bg-amber-100 text-amber-700' },
  mswdo_review: { label: () => t('status.mswdo_review'), color: 'bg-cyan-100 text-cyan-700' },
  social_case_study_uploaded: { label: () => t('status.case_study'), color: 'bg-indigo-100 text-indigo-700' },
  assistance_coding: { label: () => t('status.assistance_coding'), color: 'bg-purple-100 text-purple-700' },
  internal_audit_review: { label: () => t('status.internal_audit_review'), color: 'bg-fuchsia-100 text-fuchsia-700' },
  returned_assistance_coding: { label: () => t('status.returned_assistance_coding'), color: 'bg-orange-100 text-orange-700' },
  voucher_creation: { label: () => t('status.voucher_creation'), color: 'bg-teal-100 text-teal-700' },
  budget_checking: { label: () => t('status.budget_checking'), color: 'bg-violet-100 text-violet-700' },
  voucher_on_hold: { label: () => t('status.voucher_on_hold'), color: 'bg-gray-100 text-gray-700' },
  voucher_recording: { label: () => t('status.voucher_recording'), color: 'bg-emerald-100 text-emerald-700' },
  with_treasurer: { label: () => t('status.with_treasurer'), color: 'bg-blue-100 text-blue-700' },
  cheque_ready: { label: () => t('status.cheque_ready'), color: 'bg-green-100 text-green-700' },
  claimed: { label: () => t('status.claimed'), color: 'bg-gray-100 text-gray-700' },
}

const statusInfo = computed(() => {
  const config = statusConfig[props.application?.status]
  return config ? { label: config.label(), color: config.color } : { label: props.application?.status, color: 'bg-gray-100 text-gray-700' }
})

const timelineSteps = computed(() => {
  if (!props.application) return []
  const steps = []
  const currentStatus = props.application.status

  const latestReview = props.reviews?.[0]
  const isSubmitted = currentStatus === 'submitted' && latestReview?.to_status === 'returned_to_applicant'

  if (currentStatus !== 'submitted' || isSubmitted) {
    const isClaimed = currentStatus === 'claimed'
    const config = statusConfig[currentStatus]
    steps.push({
      key: currentStatus,
      label: config ? config.label() : currentStatus,
      isCompleted: isClaimed,
      isCurrent: !isClaimed,
      timestamp: isClaimed ? props.application.claimed_at : null,
    })
  }

  ;(props.reviews ?? []).forEach((r) => {
    steps.push({
      key: r.id ?? r.stage + r.created_at,
      label: stageLabels[r.stage]?.() ?? r.stage,
      isCompleted: true,
      isCurrent: false,
      decision: r.decision,
      timestamp: r.created_at,
    })
  })

  steps.push({
    key: 'submitted',
    label: t('status.submitted'),
    isCompleted: true,
    isCurrent: currentStatus === 'submitted' && !isSubmitted,
    timestamp: props.application.created_at,
  })

  return steps
})
</script>

<template>
  <Head :title="$t('track.title')" />

  <div class="min-h-screen bg-white">
    <div class="sticky top-0 z-50 border-b border-emerald-100 bg-white/95 backdrop-blur-md">
      <div class="max-w-5xl px-4 mx-auto sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
          <div class="flex items-center gap-3">
            <img src="/images/logo/alalay-logo.png" alt="ALALAY" class="h-8 w-auto">
            <div class="flex items-center gap-2 pl-3 border-l border-emerald-200">
              <img src="/images/logo/gmn.png" alt="GMN" class="h-6 opacity-60">
              <img src="/images/logo/dswd.png" alt="DSWD" class="h-6 opacity-60">
              <img src="/images/logo/AICS.png" alt="AICS" class="h-6 opacity-60">
            </div>
          </div>
          <Link :href="homeUrl" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
            {{ $t('track.back') }}
          </Link>
        </div>
      </div>
    </div>
    <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">

      <div v-if="otp_required && !hasApplication" class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12">
        <div class="text-center mb-6">
          <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
            </svg>
          </div>
          <h1 class="text-2xl font-bold text-emerald-900 mb-2">{{ $t('track.otp_title') }}</h1>
          <p class="text-emerald-600 text-sm">{{ $t('track.otp_description') }}</p>
        </div>

        <div v-if="!otp_sent" class="text-center">
          <button
            @click="sendOtp"
            :disabled="otpForm.processing"
            class="px-8 py-3 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors cursor-pointer disabled:opacity-50"
          >
            {{ otpForm.processing ? $t('track.otp_sending') : $t('track.otp_send') }}
          </button>
        </div>

        <div v-else>
          <div v-if="otp_expired" class="text-center">
            <p class="text-sm text-amber-600 mb-4">{{ $t('track.otp_expired') }}</p>
            <button
              @click="sendOtp"
              :disabled="!otpResendAllowed || otpForm.processing"
              class="px-8 py-3 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ otpForm.processing ? $t('track.otp_sending') : otpResendLabel }}
            </button>
          </div>

          <div v-else>
            <p class="text-sm text-gray-600 text-center mb-6">{{ $t('track.otp_enter') }}</p>

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
                :class="otpForm.errors.otp_code || otpPageErrors.otp_code ? 'border-red-300 bg-red-50' : 'border-emerald-200 bg-emerald-50/50'"
                :disabled="otpForm.processing"
                @input="handleInput(i, $event)"
                @keydown="handleKeydown(i, $event)"
                @paste="handlePaste"
              />
            </div>

            <div v-if="otpForm.errors.otp_code || otpPageErrors.otp_code" class="text-center mb-4">
              <p class="text-sm text-red-600">{{ otpForm.errors.otp_code || otpPageErrors.otp_code }}</p>
            </div>

            <div class="flex flex-col gap-3">
              <button
                @click="submitOtp"
                :disabled="otpForm.processing || otpString.length !== 6"
                class="w-full px-8 py-3 rounded-xl text-sm font-semibold text-white transition-colors cursor-pointer"
                :class="otpForm.processing || otpString.length !== 6 ? 'bg-gray-300 cursor-not-allowed' : 'bg-emerald-600 hover:bg-emerald-700'"
              >
                {{ otpForm.processing ? $t('track.otp_verifying') : $t('track.otp_verify') }}
              </button>

              <div class="text-center">
                <button
                  @click="sendOtp"
                  :disabled="!otpResendAllowed || otpForm.processing"
                  class="text-sm text-emerald-600 hover:text-emerald-800 font-medium transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ otpResendLabel }}
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-6">
          <Link :href="trackUrl" class="text-sm text-emerald-600 hover:text-emerald-800 font-medium">
            {{ $t('track.otp_different_code') }}
          </Link>
        </div>
      </div>

      <div v-if="hasApplication" class="space-y-6">
        <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-6 sm:p-8">
          <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
            <div>
              <h1 class="text-xl font-bold text-emerald-900">{{ $t('track.status_title') }}</h1>
              <p class="text-sm text-gray-500 mt-1">{{ props.application.category_name }}</p>
            </div>
            <span :class="['px-3 py-1.5 rounded-lg text-sm font-semibold', statusInfo.color]">
              {{ statusInfo.label }}
            </span>
          </div>

          <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div>
              <dt class="text-gray-500">{{ $t('track.status_ref_code') }}</dt>
              <dd class="font-mono font-bold text-emerald-900">{{ props.application.reference_code }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">{{ $t('track.status_beneficiary') }}</dt>
              <dd class="font-medium">{{ props.application.beneficiary_name }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">{{ $t('track.status_date') }}</dt>
              <dd class="font-medium">{{ props.application.created_at }}</dd>
            </div>
            <div>
              <dt class="text-gray-500">{{ $t('track.status_label') }}</dt>
              <dd class="font-semibold">{{ statusInfo.label }}</dd>
            </div>
          </dl>
        </div>

        <div v-if="timelineSteps.length" class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-6 sm:p-8">
          <h2 class="text-lg font-bold text-emerald-900 mb-6">{{ $t('track.timeline') }}</h2>
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
                      {{ decisionLabels[step.decision]?.() ?? step.decision }}
                    </span>
                    <span v-if="step.isCurrent" class="text-[10px] px-1.5 py-0.5 rounded-full bg-emerald-100 text-emerald-700 font-semibold uppercase tracking-wider">{{ $t('track.timeline_current') }}</span>
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
            <h2 class="text-lg font-bold text-amber-900">{{ $t('track.resubmission_title') }}</h2>
          </div>
          <div v-if="props.application.resubmission_remarks" class="text-sm bg-amber-50 rounded-lg p-3 mb-4 space-y-1">
            <p class="font-semibold text-amber-800">{{ $t('track.resubmission_remark') }} <span v-if="props.application.reviewer_role" class="text-xs bg-amber-200 text-amber-800 px-2 py-0.5 rounded-full ml-1">{{ props.application.reviewer_role }}</span></p>
            <p class="text-amber-700">{{ props.application.resubmission_remarks }}</p>
          </div>

          <div class="space-y-4 mb-6">
            <div v-for="doc in resubmission_docs_required" :key="doc.id" class="border border-amber-200 rounded-xl p-4 bg-amber-50/50">
              <DocumentScanner
                :docName="doc.doc_name"
                :required="true"
                :captureType="doc.capture_type || 'single'"
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
              {{ isSubmitting ? $t('apply.submitting') : $t('track.resubmission_submit') }}
            </button>
          </div>

          <Teleport to="body">
            <div
              v-if="isSubmitting"
              class="fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-6"
            >
              <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8">
                <h3 class="text-lg font-bold text-gray-900 mb-1 text-center">{{ $t('track.resubmission_modal_title') }}</h3>
                <p class="text-sm text-gray-500 mb-6 text-center">{{ $t('track.resubmission_uploading') }}</p>
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
                <p class="text-xs text-gray-400 text-center">{{ $t('track.resubmission_wait') }}</p>
              </div>
            </div>
          </Teleport>
        </div>

        <div class="text-center">
          <Link
            :href="trackUrl"
            class="text-sm text-emerald-600 hover:text-emerald-800 font-medium"
          >
            {{ $t('track.track_another') }}
          </Link>
        </div>
      </div>

      <div v-if="!hasApplication && !otp_required" class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12">
        <div class="text-center mb-8">
          <div class="w-16 h-16 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-emerald-900 mb-2">{{ $t('track.title') }}</h1>
          <p class="text-emerald-600">{{ $t('track.subtitle') }}</p>
        </div>

        <form @submit.prevent="lookupApplication" class="max-w-md mx-auto">
          <div class="flex gap-3">
            <input
              v-model="lookupForm.reference_code"
              type="text"
              :placeholder="$t('track.placeholder')"
              class="flex-1 px-4 py-3 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none uppercase"
              @keyup.enter="lookupApplication"
            />
            <button
              type="submit"
              :disabled="!lookupForm.reference_code.trim()"
              class="px-6 py-3 rounded-xl text-sm font-semibold text-white transition-colors cursor-pointer disabled:opacity-50"
              :class="lookupForm.reference_code.trim() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
            >
              {{ $t('track.button') }}
            </button>
          </div>
          <p v-if="lookupForm.errors.reference_code" class="text-xs text-red-500 mt-2">{{ lookupForm.errors.reference_code }}</p>
          <p v-else-if="refCodeValid.isChecking.value && lookupForm.reference_code" class="text-xs text-gray-400 mt-2">{{ $t('common.checking') }}</p>
          <p v-else-if="refCodeValid.isValid.value === false" class="text-xs text-amber-600 mt-2">{{ refCodeValid.message.value }}</p>
        </form>
      </div>

    </main>

    <Teleport to="body">
      <div v-if="toast"
        class="fixed top-4 right-4 z-[9999] px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 max-w-sm"
        :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"
      >
        <svg v-if="toast.type === 'success'" class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
        </svg>
        <svg v-else class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
        {{ toast.message }}
      </div>
    </Teleport>
  </div>
</template>
