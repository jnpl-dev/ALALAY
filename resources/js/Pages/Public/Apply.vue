<script setup>
import { ref, reactive, computed, watch, onMounted, onBeforeUnmount } from 'vue'
import { Head, Link, useForm, usePage, router } from '@inertiajs/vue3'
import axios from 'axios'
import DocumentScanner from '@/Components/Application/DocumentScanner.vue'
import { usePsgcAddress } from '@/Composables/usePsgcAddress.js'
import { jsPDF } from 'jspdf'

const props = defineProps({
  categories: Array,
})

const flash = computed(() => usePage().props.flash)

const toast = ref(null)
let toastTimer = null

function showToast(message, type) {
  toast.value = { message, type }
  clearTimeout(toastTimer)
  toastTimer = setTimeout(() => { toast.value = null }, 4000)
}

watch(() => usePage().props.flash, (val) => {
  if (val?.success && !val?.reference_code) showToast(val.success, 'success')
  if (val?.error) showToast(val.error, 'error')
}, { immediate: true })

onBeforeUnmount(() => clearTimeout(toastTimer))

const homeUrl = route('home')
const applyUrl = route('apply')
const trackUrl = route('track')
const copied = ref(false)

function copyCode(code) {
  navigator.clipboard.writeText(code).then(() => {
    copied.value = true
    setTimeout(() => copied.value = false, 2000)
  })
}

const steps = ['Category', 'Applicant Info', 'Documents', 'Summary', 'Complete']
const currentStep = ref(0)
const submittedCode = ref(null)

if (flash.value?.reference_code) {
  currentStep.value = 4
  submittedCode.value = flash.value.reference_code
}

const form = useForm({
  category_id: null,
  claimant_last_name: '',
  claimant_first_name: '',
  claimant_middle_name: '',
  claimant_name_extension: '',
  claimant_sex: '',
  claimant_dob: '',
  claimant_address: '',
  claimant_phone: '',
  claimant_email: '',
  claimant_relationship_to_beneficiary: '',
  beneficiary_last_name: '',
  beneficiary_first_name: '',
  beneficiary_middle_name: '',
  beneficiary_name_extension: '',
  beneficiary_sex: '',
  beneficiary_dob: '',
  beneficiary_address: '',
  documents: [],
  document_ids: [],
})

const claimantAddr = reactive(usePsgcAddress())
const beneficiaryAddr = reactive(usePsgcAddress())
const sameAddress = ref(false)

watch(() => claimantAddr.addressString, (val) => { form.claimant_address = val })
watch(() => beneficiaryAddr.addressString, (val) => { form.beneficiary_address = val })

watch(sameAddress, (val) => {
  if (val) {
    beneficiaryAddr.selectedProvince = claimantAddr.selectedProvince
    beneficiaryAddr.selectedCity = claimantAddr.selectedCity
    beneficiaryAddr.selectedBarangay = claimantAddr.selectedBarangay
    beneficiaryAddr.street = claimantAddr.street
    if (claimantAddr.selectedProvince) {
      beneficiaryAddr.cities = [...claimantAddr.cities]
      beneficiaryAddr.barangays = [...claimantAddr.barangays]
    }
  }
})

const beneficiaryEligible = ref(null)
const beneficiaryEligibilityMsg = ref('')
let eligibilityTimer = null

watch([() => form.beneficiary_first_name, () => form.beneficiary_last_name, () => form.beneficiary_middle_name], () => {
  clearTimeout(eligibilityTimer)
  beneficiaryEligible.value = null
  beneficiaryEligibilityMsg.value = ''

  if (!form.beneficiary_first_name || !form.beneficiary_last_name) return

  eligibilityTimer = setTimeout(async () => {
    try {
      const res = await axios.get(route('validate.beneficiary'), {
        params: {
          beneficiary_first_name: form.beneficiary_first_name,
          beneficiary_last_name: form.beneficiary_last_name,
          beneficiary_middle_name: form.beneficiary_middle_name || null,
        }
      })
      beneficiaryEligible.value = res.data.eligible
      if (!res.data.eligible) {
        beneficiaryEligibilityMsg.value = res.data.message
      }
    } catch {
      // silent fail
    }
  }, 500)
})

import { useFieldValidation } from '@/Composables/useFieldValidation'

const phoneValid = useFieldValidation(
  route('validate.phone'),
  () => form.claimant_phone,
  {},
  { debounceMs: 400 },
)

onMounted(async () => {
  await claimantAddr.fetchProvinces()
  await beneficiaryAddr.fetchProvinces()
  const ne = claimantAddr.provinces?.find(p => p.name === 'Nueva Ecija')
  if (ne) {
    await Promise.all([
      claimantAddr.setProvince(ne),
      beneficiaryAddr.setProvince(ne),
    ])
    const gmn = claimantAddr.cities?.find(c => c.name === 'General Mamerto Natividad')
    if (gmn) {
      claimantAddr.setCity(gmn)
      beneficiaryAddr.setCity(gmn)
    }
  }
})

const selectedCategory = computed(() =>
  props.categories?.find(c => c.id === form.category_id)
)

const isRepresentative = computed(() =>
  form.claimant_relationship_to_beneficiary === 'Representative'
)

const allRequiredDocs = computed(() =>
  selectedCategory.value?.required_documents?.filter(d => d.is_active) ?? []
)

const capturedCount = computed(() =>
  allRequiredDocs.value.filter(d => form.document_ids.includes(d.id)).length
)

const allMandatoryCaptured = computed(() => {
  const mandatory = allRequiredDocs.value.filter(d => d.is_mandatory)
  const captured = mandatory.every(d => form.document_ids.includes(d.id))
  if (isRepresentative.value) {
    const authDoc = allRequiredDocs.value.find(d => d.doc_name === 'Authorization Letter')
    if (authDoc) {
      return captured && form.document_ids.includes(authDoc.id)
    }
  }
  return captured
})

// Step 3 pagination
const currentDocIndex = ref(0)
const visibleDocs = computed(() =>
  allRequiredDocs.value.filter(d =>
    d.doc_name !== 'Authorization Letter' || isRepresentative.value
  )
)
const currentDoc = computed(() => visibleDocs.value[currentDocIndex.value])

// Store captured raw data for previews + deferred PDF conversion
const capturedDocs = ref({}) // docId -> { pages: [...], docName }
const docPreviews = ref({})  // docId -> { preview: dataUrl, pageCount }

// Submit progress
const submitting = ref(false)
const submitProgress = ref(0)
const submitMessage = ref('')
const submitStep = ref('')
const submitTotal = ref(0)
const submitCurrent = ref(0)

onBeforeUnmount(() => {
  Object.values(docPreviews.value).forEach(dp => {
    if (dp?.preview?.startsWith('blob:')) URL.revokeObjectURL(dp.preview)
  })
})

function selectCategory(category) {
  form.category_id = category.id
  form.documents = []
  form.document_ids = []
  capturedDocs.value = {}
  docPreviews.value = {}
  currentDocIndex.value = 0
  currentStep.value = 1
}

function isStep2Valid() {
  const r = form
  return (
    r.claimant_last_name &&
    r.claimant_first_name &&
    r.claimant_sex &&
    r.claimant_dob &&
    r.claimant_address &&
    r.claimant_phone &&
    r.claimant_relationship_to_beneficiary &&
    r.beneficiary_last_name &&
    r.beneficiary_first_name &&
    r.beneficiary_sex &&
    r.beneficiary_dob &&
    r.beneficiary_address
  )
}

function nextStep() {
  if (currentStep.value === 1 && !isStep2Valid()) return
  if (currentStep.value === 2 && !allMandatoryCaptured.value) return
  currentStep.value++
}

function prevStep() {
  if (currentStep.value > 0) currentStep.value--
}

function onDocCapture(docId, payload) {
  const doc = allRequiredDocs.value.find(d => d.id === docId)
  if (!doc) return
  capturedDocs.value[docId] = {
    pages: payload.pages || [],
    docName: doc.doc_name,
  }
  docPreviews.value[docId] = {
    preview: payload.preview,
    pageCount: payload.pageCount,
  }
  if (!form.document_ids.includes(docId)) {
    form.document_ids.push(docId)
    form.documents.push(payload.file)
  } else {
    const idx = form.document_ids.indexOf(docId)
    form.documents[idx] = payload.file
  }
}

function onDocClear(docId) {
  delete capturedDocs.value[docId]
  delete docPreviews.value[docId]
  const idx = form.document_ids.indexOf(docId)
  if (idx >= 0) {
    form.document_ids.splice(idx, 1)
    form.documents.splice(idx, 1)
  }
}

function goToDoc(i) {
  if (i >= 0 && i < visibleDocs.value.length) {
    currentDocIndex.value = i
  }
}

async function submitApplication() {
  submitting.value = true
  submitProgress.value = 0
  submitMessage.value = ''

  const total = form.document_ids.length
  submitTotal.value = total
  submitCurrent.value = 0
  submitStep.value = 'converting'

  const pdfFiles = []

  for (let i = 0; i < total; i++) {
    const docId = form.document_ids[i]
    const doc = capturedDocs.value[docId]
    if (!doc || !doc.pages?.length) {
      pdfFiles.push(form.documents[i])
      submitCurrent.value = i + 1
      submitProgress.value = ((i + 1) / total) * 70
      continue
    }

    submitMessage.value = `Converting "${doc.docName}" to PDF...`
    submitCurrent.value = i + 1

    await new Promise((resolve) => setTimeout(resolve, 50))

    const pdf = new jsPDF({
      orientation: 'portrait',
      unit: 'mm',
      format: 'a4',
    })

    const pageW = pdf.internal.pageSize.getWidth()
    const pageH = pdf.internal.pageSize.getHeight()
    const margin = 10
    const maxW = pageW - margin * 2
    const maxH = pageH - margin * 2

    doc.pages.forEach((img, pi) => {
      if (pi > 0) pdf.addPage('a4', 'portrait')

      const imgAspect = img.width / img.height
      const pageAspect = maxW / maxH

      let drawW, drawH
      if (imgAspect > pageAspect) {
        drawW = maxW
        drawH = maxW / imgAspect
      } else {
        drawH = maxH
        drawW = maxH * imgAspect
      }

      pdf.addImage(img.data, 'JPEG', (pageW - drawW) / 2, (pageH - drawH) / 2, drawW, drawH)
    })

    const blob = pdf.output('blob')
    const file = new File([blob], `${doc.docName}.pdf`, { type: 'application/pdf' })
    pdfFiles.push(file)

    submitProgress.value = ((i + 1) / total) * 70
  }

  submitStep.value = 'submitting'
  submitMessage.value = 'Submitting your application...'
  submitProgress.value = 75

  form.documents = pdfFiles

  form.post(route('apply'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      submitting.value = false
      currentStep.value = 4
      submittedCode.value = flash.value?.reference_code
    },
    onError: () => {
      submitting.value = false
      submitMessage.value = ''
    },
    onFinish: () => {
      submitting.value = false
    },
  })
}

const statusLabel = (status) => ({
  submitted: 'Submitted',
  returned_to_applicant: 'Returned for Revision',
  resubmitted: 'Resubmitted',
  aics_review: 'Under AICS Review',
  mswdo_review: 'Under MSWDO Review',
  approved: 'Approved',
  claim_ready: 'Ready for Claiming',
  claimed: 'Claimed',
}[status] || status)
</script>

<template>
  <Head title="Apply for Assistance" />

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
      <div v-if="currentStep < 4" class="mb-8">
        <div class="flex items-center justify-between max-w-xl mx-auto">
          <template v-for="(step, i) in steps.slice(0, 4)" :key="i">
            <div class="flex items-center">
              <div
                :class="[
                  'w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold transition-colors',
                  i < currentStep ? 'bg-emerald-600 text-white' :
                  i === currentStep ? 'bg-emerald-600 text-white ring-4 ring-emerald-200' :
                  'bg-gray-200 text-gray-400'
                ]"
              >
                {{ i < currentStep ? '✓' : i + 1 }}
              </div>
              <span
                v-if="i < 3"
                :class="[
                  'w-12 sm:w-20 h-0.5 mx-1 sm:mx-2 transition-colors',
                  i < currentStep ? 'bg-emerald-600' : 'bg-gray-200'
                ]"
              />
            </div>
          </template>
        </div>
        <p class="text-center text-sm text-gray-500 mt-3 font-medium">{{ steps[currentStep] }}</p>
      </div>

      <div v-if="Object.keys(form.errors).length && currentStep === 3" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm font-medium text-red-800 mb-1">Unable to submit</p>
        <ul class="text-sm text-red-600 list-disc list-inside">
          <li v-for="(msg, key) in form.errors" :key="key">{{ msg }}</li>
        </ul>
      </div>

        <div v-if="flash?.error && !toast" class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
        <p class="text-sm text-red-600">{{ flash.error }}</p>
      </div>

      <div v-if="(flash?.success && flash?.reference_code) || submittedCode" class="text-center">
        <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12">
          <div class="w-16 h-16 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <svg class="w-8 h-8 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-emerald-900 mb-2">Application Submitted!</h1>
          <p class="text-emerald-600 mb-6">Save your reference code to track your application.</p>
          <div class="bg-emerald-50 rounded-xl p-6 mb-6 inline-flex items-center gap-3">
            <div>
              <p class="text-xs text-emerald-600 uppercase tracking-wide font-semibold mb-1">Reference Code</p>
              <p class="text-2xl sm:text-3xl font-bold text-emerald-900 tracking-wider font-mono">{{ submittedCode || flash?.reference_code }}</p>
            </div>
            <button @click="copyCode(submittedCode || flash?.reference_code)" class="shrink-0 p-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors cursor-pointer border-none" title="Copy reference code">
              <svg v-if="!copied" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
              </svg>
              <svg v-else class="w-5 h-5 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
              </svg>
            </button>
          </div>
          <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <Link
              :href="trackUrl"
              class="inline-flex items-center justify-center gap-2 bg-emerald-600 text-white px-6 py-3 rounded-xl font-semibold text-sm hover:bg-emerald-700 transition-colors"
            >
              <span>Track Application</span>
            </Link>
            <Link
              :href="applyUrl"
              class="inline-flex items-center justify-center gap-2 bg-white text-emerald-700 px-6 py-3 rounded-xl font-semibold text-sm border border-emerald-200 hover:bg-emerald-50 transition-colors"
            >
              <span>Apply for Another</span>
            </Link>
          </div>
        </div>
      </div>

      <div v-else-if="currentStep === 0" class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12">
        <div class="text-center mb-8">
          <div class="w-14 h-14 bg-emerald-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-emerald-900 mb-2">Apply for Assistance</h1>
          <p class="text-emerald-600">Select the type of assistance you need.</p>
        </div>

        <div v-if="!categories?.length" class="text-center py-8">
          <p class="text-gray-500">No assistance categories are currently available. Please check back later.</p>
        </div>

        <div v-else class="flex flex-col gap-4">
          <button
            v-for="category in categories"
            :key="category.id"
            @click="selectCategory(category)"
            class="text-left p-5 rounded-xl border-2 border-emerald-100 hover:border-emerald-400 bg-white hover:bg-emerald-50 transition-all cursor-pointer"
          >
            <h3 class="font-bold text-emerald-900 text-lg mb-1">{{ category.category_name }}</h3>
            <p class="text-sm text-gray-600 mb-3">{{ category.category_description }}</p>
            <div>
              <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Required Documents</p>
              <ul class="text-xs text-gray-500 space-y-0.5">
                <li
                  v-for="doc in category.required_documents"
                  :key="doc.id"
                  class="flex items-center gap-1"
                >
                  <span>{{ doc.is_mandatory ? '*' : '' }}{{ doc.doc_name }}</span>
                  <span v-if="doc.is_mandatory" class="text-red-400 font-medium">(Required)</span>
                </li>
              </ul>
            </div>
          </button>
        </div>
      </div>

      <div v-else-if="currentStep === 1" class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12">
        <h2 class="text-xl font-bold text-emerald-900 mb-6">Applicant Information</h2>

        <div class="space-y-4 mb-8">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
              <input v-model="form.claimant_last_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
              <p v-if="form.errors.claimant_last_name" class="text-xs text-red-500 mt-1">{{ form.errors.claimant_last_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
              <input v-model="form.claimant_first_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
              <p v-if="form.errors.claimant_first_name" class="text-xs text-red-500 mt-1">{{ form.errors.claimant_first_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
              <input v-model="form.claimant_middle_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name Extension</label>
              <select v-model="form.claimant_name_extension" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white">
                <option value="">None</option>
                <option value="Jr.">Jr.</option>
                <option value="Sr.">Sr.</option>
                <option value="III">III</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sex <span class="text-red-500">*</span></label>
              <select v-model="form.claimant_sex" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white">
                <option value="">Select...</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
              <p v-if="form.errors.claimant_sex" class="text-xs text-red-500 mt-1">{{ form.errors.claimant_sex }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
              <input v-model="form.claimant_dob" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
              <p v-if="form.errors.claimant_dob" class="text-xs text-red-500 mt-1">{{ form.errors.claimant_dob }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Address <span class="text-red-500">*</span></label>
            <div class="space-y-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <select v-model="claimantAddr.selectedProvince" @change="claimantAddr.setProvince(claimantAddr.selectedProvince)" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white">
                    <option :value="null" disabled>{{ claimantAddr.loadingProvinces ? 'Loading...' : 'Select Province' }}</option>
                    <option v-for="p in claimantAddr.provinces" :key="p.code" :value="p">{{ p.name }}</option>
                  </select>
                </div>
                <div>
                  <select v-model="claimantAddr.selectedCity" @change="claimantAddr.setCity(claimantAddr.selectedCity)" :disabled="!claimantAddr.selectedProvince" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white disabled:opacity-50 disabled:cursor-not-allowed">
                    <option :value="null" disabled>{{ claimantAddr.loadingCities ? 'Loading...' : 'Select City/Municipality' }}</option>
                    <option v-for="c in claimantAddr.cities" :key="c.code" :value="c">{{ c.name }}</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <select v-model="claimantAddr.selectedBarangay" @change="claimantAddr.setBarangay(claimantAddr.selectedBarangay)" :disabled="!claimantAddr.selectedCity" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white disabled:opacity-50 disabled:cursor-not-allowed">
                    <option :value="null" disabled>{{ claimantAddr.loadingBarangays ? 'Loading...' : 'Select Barangay' }}</option>
                    <option v-for="b in claimantAddr.barangays" :key="b.code" :value="b">{{ b.name }}</option>
                  </select>
                </div>
                <div>
                  <input v-model="claimantAddr.street" type="text" placeholder="Street / House No." class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
                </div>
              </div>
            </div>
            <input v-model="form.claimant_address" type="hidden" />
            <p v-if="form.errors.claimant_address" class="text-xs text-red-500 mt-1">{{ form.errors.claimant_address }}</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number <span class="text-red-500">*</span></label>
              <input v-model="form.claimant_phone" type="tel" placeholder="0917xxxxxxx" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
              <p v-if="form.errors.claimant_phone" class="text-xs text-red-500 mt-1">{{ form.errors.claimant_phone }}</p>
              <p v-else-if="phoneValid.isChecking.value && form.claimant_phone" class="text-xs text-gray-400 mt-1">Checking...</p>
              <p v-else-if="phoneValid.isValid.value === false" class="text-xs text-amber-600 mt-1">{{ phoneValid.message.value }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
              <input v-model="form.claimant_email" type="email" placeholder="Optional" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
              <p v-if="form.errors.claimant_email" class="text-xs text-red-500 mt-1">{{ form.errors.claimant_email }}</p>
            </div>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Relationship to Beneficiary <span class="text-red-500">*</span></label>
            <select v-model="form.claimant_relationship_to_beneficiary" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white">
              <option value="">Select relationship...</option>
              <option value="Spouse">Spouse</option>
              <option value="Parent">Parent</option>
              <option value="Child">Child</option>
              <option value="Sibling">Sibling</option>
              <option value="Grandparent">Grandparent</option>
              <option value="Grandchild">Grandchild</option>
              <option value="Representative">Representative</option>
            </select>
            <p v-if="form.errors.claimant_relationship_to_beneficiary" class="text-xs text-red-500 mt-1">{{ form.errors.claimant_relationship_to_beneficiary }}</p>
          </div>
        </div>

        <h2 class="text-xl font-bold text-emerald-900 mb-4">Beneficiary Information</h2>

        <div class="space-y-4 mb-8">
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
              <input v-model="form.beneficiary_last_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
              <p v-if="form.errors.beneficiary_last_name" class="text-xs text-red-500 mt-1">{{ form.errors.beneficiary_last_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
              <input v-model="form.beneficiary_first_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
              <p v-if="form.errors.beneficiary_first_name" class="text-xs text-red-500 mt-1">{{ form.errors.beneficiary_first_name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Middle Name</label>
              <input v-model="form.beneficiary_middle_name" type="text" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
            </div>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Name Extension</label>
              <select v-model="form.beneficiary_name_extension" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white">
                <option value="">None</option>
                <option value="Jr.">Jr.</option>
                <option value="Sr.">Sr.</option>
                <option value="III">III</option>
              </select>
            </div>
            <div class="sm:col-span-3">
              <p v-if="beneficiaryEligible === false" class="text-xs bg-amber-50 border border-amber-200 text-amber-800 rounded-lg px-3 py-2">
                {{ beneficiaryEligibilityMsg }}
              </p>
              <p v-else-if="beneficiaryEligible === null && (form.beneficiary_first_name || form.beneficiary_last_name)" class="text-xs text-gray-400">
                Checking beneficiary eligibility...
              </p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Sex <span class="text-red-500">*</span></label>
              <select v-model="form.beneficiary_sex" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white">
                <option value="">Select...</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
              <p v-if="form.errors.beneficiary_sex" class="text-xs text-red-500 mt-1">{{ form.errors.beneficiary_sex }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth <span class="text-red-500">*</span></label>
              <input v-model="form.beneficiary_dob" type="date" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none" />
              <p v-if="form.errors.beneficiary_dob" class="text-xs text-red-500 mt-1">{{ form.errors.beneficiary_dob }}</p>
            </div>
          </div>

          <label class="flex items-center gap-2 mb-2 cursor-pointer">
            <input type="checkbox" v-model="sameAddress" class="w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500" />
            <span class="text-sm text-gray-700">Same address as claimant</span>
          </label>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Address <span class="text-red-500">*</span></label>
            <div class="space-y-2">
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <select v-model="beneficiaryAddr.selectedProvince" @change="beneficiaryAddr.setProvince(beneficiaryAddr.selectedProvince)" :disabled="sameAddress" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white disabled:opacity-50 disabled:cursor-not-allowed">
                    <option :value="null" disabled>{{ beneficiaryAddr.loadingProvinces ? 'Loading...' : 'Select Province' }}</option>
                    <option v-for="p in beneficiaryAddr.provinces" :key="p.code" :value="p">{{ p.name }}</option>
                  </select>
                </div>
                <div>
                  <select v-model="beneficiaryAddr.selectedCity" @change="beneficiaryAddr.setCity(beneficiaryAddr.selectedCity)" :disabled="sameAddress || !beneficiaryAddr.selectedProvince" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white disabled:opacity-50 disabled:cursor-not-allowed">
                    <option :value="null" disabled>{{ beneficiaryAddr.loadingCities ? 'Loading...' : 'Select City/Municipality' }}</option>
                    <option v-for="c in beneficiaryAddr.cities" :key="c.code" :value="c">{{ c.name }}</option>
                  </select>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2">
                <div>
                  <select v-model="beneficiaryAddr.selectedBarangay" @change="beneficiaryAddr.setBarangay(beneficiaryAddr.selectedBarangay)" :disabled="sameAddress || !beneficiaryAddr.selectedCity" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none bg-white disabled:opacity-50 disabled:cursor-not-allowed">
                    <option :value="null" disabled>{{ beneficiaryAddr.loadingBarangays ? 'Loading...' : 'Select Barangay' }}</option>
                    <option v-for="b in beneficiaryAddr.barangays" :key="b.code" :value="b">{{ b.name }}</option>
                  </select>
                </div>
                <div>
                  <input v-model="beneficiaryAddr.street" type="text" placeholder="Street / House No." :disabled="sameAddress" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none disabled:opacity-50 disabled:cursor-not-allowed" />
                </div>
              </div>
            </div>
            <input v-model="form.beneficiary_address" type="hidden" />
            <p v-if="form.errors.beneficiary_address" class="text-xs text-red-500 mt-1">{{ form.errors.beneficiary_address }}</p>
          </div>
        </div>

        <div class="flex justify-between">
          <button @click="prevStep" class="px-6 py-2.5 rounded-xl text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 transition-colors cursor-pointer">Back</button>
          <button @click="nextStep" :disabled="!isStep2Valid()" :class="['px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors cursor-pointer', isStep2Valid() ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed']">Next</button>
        </div>
      </div>

      <div v-else-if="currentStep === 2" class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12">
        <h2 class="text-xl font-bold text-emerald-900 mb-2">Capture Documents</h2>
        <p class="text-sm text-gray-500 mb-6">
          Step {{ currentDocIndex + 1 }} of {{ visibleDocs.length }} &mdash; {{ capturedCount }}/{{ allRequiredDocs.length }} captured
        </p>

        <div v-if="form.errors.documents" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ form.errors.documents }}</p>
        </div>

        <div v-if="currentDoc" class="space-y-4">
          <div class="border border-gray-200 rounded-xl p-4">
            <DocumentScanner
              :key="currentDoc.id"
              :docName="currentDoc.doc_name"
              :required="currentDoc.is_mandatory || (isRepresentative && currentDoc.doc_name === 'Authorization Letter')"
              :captureType="currentDoc.capture_type || 'single'"
              :scannerSize="currentDoc.scanner_size || 'a4'"
              @captured="(payload) => onDocCapture(currentDoc.id, payload)"
              @cleared="() => onDocClear(currentDoc.id)"
            />
          </div>

          <!-- Multi-preview dots -->
          <div v-if="visibleDocs.length > 1" class="flex items-center justify-center gap-1.5">
            <button
              v-for="(d, i) in visibleDocs"
              :key="d.id"
              @click="goToDoc(i)"
              :class="[
                'w-2.5 h-2.5 rounded-full border-0 cursor-pointer transition-colors',
                i === currentDocIndex ? 'bg-emerald-600' :
                form.document_ids.includes(d.id) ? 'bg-emerald-300' : 'bg-gray-300'
              ]"
              :title="d.doc_name"
            />
          </div>

          <!-- Navigation -->
          <div class="flex justify-between items-center pt-2">
            <button
              v-if="currentDocIndex > 0"
              @click="goToDoc(currentDocIndex - 1)"
              class="px-4 py-2 rounded-lg text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 transition-colors cursor-pointer"
            >
              Previous
            </button>
            <div v-else />

            <button
              v-if="currentDocIndex < visibleDocs.length - 1"
              @click="goToDoc(currentDocIndex + 1)"
              class="px-4 py-2 rounded-lg text-sm font-medium text-white cursor-pointer"
              :class="form.document_ids.includes(currentDoc.id) ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed'"
              :disabled="!form.document_ids.includes(currentDoc.id)"
            >
              Next Document
            </button>
          </div>
        </div>

        <div class="border-t border-gray-200 mt-8 pt-6">
          <div class="flex justify-between">
            <button @click="prevStep" class="px-6 py-2.5 rounded-xl text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 transition-colors cursor-pointer">Back</button>
            <button @click="nextStep" :disabled="!allMandatoryCaptured" :class="['px-6 py-2.5 rounded-xl text-sm font-semibold text-white transition-colors cursor-pointer', allMandatoryCaptured ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-gray-300 cursor-not-allowed']">
              {{ allMandatoryCaptured ? 'Review Summary' : `Capture all required (${capturedCount}/${allRequiredDocs.length})` }}
            </button>
          </div>
        </div>
      </div>

      <div v-else-if="currentStep === 3" class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-12">
        <h2 class="text-xl font-bold text-emerald-900 mb-6">Summary & Confirmation</h2>
        <p class="text-sm text-gray-500 mb-6">Please review your application before submitting.</p>

        <div v-if="form.errors.category_id" class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
          <p class="text-sm text-red-600">{{ form.errors.category_id }}</p>
        </div>

        <div class="space-y-6">
          <div class="bg-emerald-50 rounded-xl p-4">
            <h3 class="font-semibold text-emerald-900 mb-2">Assistance Category</h3>
            <p class="text-sm text-emerald-700">{{ selectedCategory?.category_name }}</p>
          </div>

          <div class="bg-gray-50 rounded-xl p-4">
            <h3 class="font-semibold text-gray-900 mb-3">Claimant Information</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
              <div><dt class="text-gray-500">Full Name</dt><dd class="font-medium">{{ form.claimant_first_name }} {{ form.claimant_middle_name }} {{ form.claimant_last_name }} {{ form.claimant_name_extension }}</dd></div>
              <div><dt class="text-gray-500">Sex</dt><dd class="font-medium">{{ form.claimant_sex }}</dd></div>
              <div><dt class="text-gray-500">Date of Birth</dt><dd class="font-medium">{{ form.claimant_dob }}</dd></div>
              <div><dt class="text-gray-500">Phone</dt><dd class="font-medium">{{ form.claimant_phone }}</dd></div>
              <div class="sm:col-span-2"><dt class="text-gray-500">Address</dt><dd class="font-medium">{{ form.claimant_address }}</dd></div>
              <div><dt class="text-gray-500">Email</dt><dd class="font-medium">{{ form.claimant_email || '—' }}</dd></div>
              <div><dt class="text-gray-500">Relationship to Beneficiary</dt><dd class="font-medium">{{ form.claimant_relationship_to_beneficiary }}</dd></div>
            </dl>
          </div>

          <div class="bg-gray-50 rounded-xl p-4">
            <h3 class="font-semibold text-gray-900 mb-3">Beneficiary Information</h3>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
              <div><dt class="text-gray-500">Full Name</dt><dd class="font-medium">{{ form.beneficiary_first_name }} {{ form.beneficiary_middle_name }} {{ form.beneficiary_last_name }} {{ form.beneficiary_name_extension }}</dd></div>
              <div><dt class="text-gray-500">Sex</dt><dd class="font-medium">{{ form.beneficiary_sex }}</dd></div>
              <div><dt class="text-gray-500">Date of Birth</dt><dd class="font-medium">{{ form.beneficiary_dob }}</dd></div>
              <div class="sm:col-span-2"><dt class="text-gray-500">Address</dt><dd class="font-medium">{{ form.beneficiary_address }}</dd></div>
            </dl>
          </div>

          <div class="bg-gray-50 rounded-xl p-4">
            <h3 class="font-semibold text-gray-900 mb-3">Documents ({{ capturedCount }})</h3>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
              <div v-for="(docId, i) in form.document_ids" :key="docId" class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                <div class="w-full h-28 flex items-center justify-center bg-gray-50 overflow-hidden">
                  <img
                    v-if="docPreviews[docId]?.preview"
                    :src="docPreviews[docId].preview"
                    :alt="allRequiredDocs.find(d => d.id === docId)?.doc_name"
                    class="w-full h-full object-contain"
                  />
                  <div v-else class="text-gray-400 text-xs">No preview</div>
                </div>
                <div class="px-2 py-1.5 text-xs font-medium text-gray-700 truncate flex items-center justify-between">
                  <span class="truncate">{{ allRequiredDocs.find(d => d.id === docId)?.doc_name || 'Document' }}</span>
                  <span v-if="docPreviews[docId]?.pageCount > 1" class="shrink-0 text-gray-400 ml-1">{{ docPreviews[docId].pageCount }}p</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-between mt-8">
          <button @click="prevStep" :disabled="submitting" class="px-6 py-2.5 rounded-xl text-sm font-medium text-gray-600 border border-gray-300 hover:bg-gray-50 transition-colors cursor-pointer disabled:opacity-50">Back</button>
          <button @click="submitApplication" :disabled="submitting" class="px-8 py-2.5 rounded-xl text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed">
            {{ submitting ? 'Processing...' : 'Submit Application' }}
          </button>
        </div>
      </div>
    </main>

    <!-- Submit Progress Modal -->
    <Teleport to="body">
      <div
        v-if="submitting"
        class="fixed inset-0 z-[99999] bg-black/60 flex items-center justify-center p-6"
      >
        <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-8">
          <h3 class="text-lg font-bold text-gray-900 mb-1 text-center">Submitting Application</h3>
          <p class="text-sm text-gray-500 mb-6 text-center">{{ submitMessage }}</p>

          <!-- Progress bar -->
          <div class="w-full bg-gray-200 rounded-full h-3 mb-4 overflow-hidden">
            <div
              class="h-full bg-emerald-600 rounded-full transition-all duration-300 ease-out"
              :style="{ width: submitProgress + '%' }"
            />
          </div>

          <!-- Per-doc status -->
          <div v-if="submitStep === 'converting' && submitTotal > 0" class="space-y-1.5 mb-4">
            <div
              v-for="(docId, i) in form.document_ids"
              :key="docId"
              class="flex items-center gap-2 text-xs"
            >
              <span v-if="i < submitCurrent" class="text-emerald-600 shrink-0">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
              </span>
              <span v-else class="w-3.5 h-3.5 shrink-0 rounded-full border-2 border-gray-300" />
              <span :class="i < submitCurrent ? 'text-gray-700' : 'text-gray-400'">
                {{ capturedDocs[docId]?.docName || 'Document' }}
              </span>
              <span v-if="i === submitCurrent - 1" class="text-emerald-600 ml-auto animate-pulse">Converting...</span>
              <span v-else-if="i < submitCurrent - 1" class="text-emerald-600 ml-auto">Done</span>
            </div>
          </div>

          <p v-if="submitStep === 'submitting'" class="text-xs text-gray-400 text-center">Please wait while your application is being submitted.</p>
        </div>
      </div>
    </Teleport>

    <!-- Toast notification -->
    <Teleport to="body">
      <div v-if="toast"
        class="fixed top-4 right-4 z-[99999] px-5 py-3 rounded-xl shadow-lg text-sm font-medium transition-all duration-300 flex items-center gap-2 max-w-sm"
        :class="toast.type === 'success' ? 'bg-emerald-600 text-white' : 'bg-red-600 text-white'"
      >
        <i :class="toast.type === 'success' ? 'pi pi-check-circle' : 'pi pi-exclamation-circle'"></i>
        {{ toast.message }}
      </div>
    </Teleport>
  </div>
</template>
