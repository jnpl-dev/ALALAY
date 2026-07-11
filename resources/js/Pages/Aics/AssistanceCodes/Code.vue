<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ApplicationInfo from '@/Components/Application/ApplicationInfo.vue'
import DocumentViewer from '@/Components/Application/DocumentViewer.vue'
import DocumentMeta from '@/Components/Application/DocumentMeta.vue'
import ReviewTrail from '@/Components/Application/ReviewTrail.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import Select from 'primevue/select'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import { useToast } from '@/Composables/useToast'
import { formatCurrency } from '@/Utils/formatCurrency'
import { ref, computed } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  application: { type: Object, required: true },
  documents: { type: Array, default: () => [] },
  reviews: { type: Array, default: () => [] },
  socialCaseStudy: { type: Object, default: null },
  code_references: { type: Array, default: () => [] },
})

const toast = useToast()
const route = window.route

const viewerUrl = ref(null)
const viewerTitle = ref('')
const viewerIdx = ref(0)
const viewerDocuments = ref([])
const hasScs = computed(() => !!props.socialCaseStudy)

const codeOptions = props.code_references.map(c => ({
  label: `${c.code_type} — ${c.description || ''}`.trim(),
  value: c.id,
  amount: c.default_amount,
}))

const form = useForm({
  assistance_code_reference_id: null,
  amount: 0,
})

const selectedCode = computed({
  get: () => codeOptions.find(c => c.value === form.assistance_code_reference_id) || null,
  set: (val) => {
    form.assistance_code_reference_id = val?.value || null
    if (val) form.amount = val.amount
  },
})

function viewDocument(doc, idx) {
  viewerUrl.value = route('aics.applications.document-url', [props.application.id, doc.id])
  viewerTitle.value = doc.doc_name
  viewerIdx.value = idx
  viewerDocuments.value = props.documents
}

function closeViewer() {
  viewerUrl.value = null
}

function prevDoc() {
  const idx = viewerIdx.value - 1
  if (idx >= 0) viewDocument(props.documents[idx], idx)
}

function nextDoc() {
  const idx = viewerIdx.value + 1
  if (idx < props.documents.length) viewDocument(props.documents[idx], idx)
}

function viewScs() {
  viewerUrl.value = props.socialCaseStudy?.signed_url || null
  viewerTitle.value = 'Social Case Study'
  viewerIdx.value = 0
  viewerDocuments.value = []
}

function submit() {
  form.post(route('aics.assistance-codes.store', props.application.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => toast.success('Code assigned'),
    onError: () => toast.error('Validation error'),
  })
}
</script>

<template>
  <Head :title="'Code - ' + application.reference_code" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12 lg:col-span-8">
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="font-semibold text-xl">{{ application.reference_code }}</div>
            <AppStatusBadge :status="application.status" class="mt-1" />
          </div>
          <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text
            @click="router.get(route('aics.assistance-codes.index'))" />
        </div>

        <ApplicationInfo :application="application" />

        <hr class="border-surface my-6" />

        <div>
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Documents</h3>
          <div v-if="documents.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div v-for="(doc, idx) in documents" :key="doc.id"
              class="relative group border border-surface rounded-lg overflow-hidden cursor-pointer hover:border-primary transition-colors"
              @click="viewDocument(doc, idx)">
              <div class="aspect-[3/4] flex items-center justify-center bg-surface-50 dark:bg-surface-800 overflow-hidden">
                <template v-if="doc.signed_url">
                  <div v-if="doc.mime_type === 'application/pdf'" class="flex flex-col items-center gap-2 text-muted-color">
                    <i class="pi pi-file-pdf text-4xl"></i>
                    <span class="text-[10px] font-medium">PDF</span>
                  </div>
                  <img v-else :src="doc.signed_url" :alt="doc.doc_name"
                    class="w-full h-full object-cover" loading="lazy" />
                </template>
                <i v-else class="pi pi-file text-3xl text-muted-color"></i>
              </div>
              <div class="px-2 py-1.5">
                <p class="text-xs text-surface-700 truncate">{{ doc.doc_name }}</p>
              </div>
              <div class="absolute inset-0 bg-black/0 group-hover:bg-black/30 transition-colors flex items-center justify-center">
                <span class="opacity-0 group-hover:opacity-100 text-white bg-primary px-3 py-1.5 rounded-lg text-xs font-medium transition-opacity shadow-lg">
                  <i class="pi pi-eye mr-1"></i> View
                </span>
              </div>
            </div>
          </div>
          <div v-else class="text-sm text-muted-color py-4 text-center">
            No documents uploaded
          </div>
        </div>

        <hr v-if="hasScs" class="border-surface my-6" />

        <div v-if="hasScs">
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Social Case Study</h3>
          <DocumentMeta
            :uploaded-by="socialCaseStudy.uploaded_by"
            :uploaded-at="socialCaseStudy.conducted_at"
            :page-count="socialCaseStudy.page_count"
            :file-size="socialCaseStudy.file_size_label"
          />
          <div class="mt-3">
            <Button icon="pi pi-eye" label="View Case Study" severity="secondary" outlined @click="viewScs" />
          </div>
        </div>

        <template v-if="!application.assistance_code">
          <hr class="border-surface my-6" />

          <div>
            <h3 class="font-semibold text-surface-900 mb-4 text-sm uppercase tracking-wide text-muted-color">Assign Assistance Code</h3>
            <form @submit.prevent="submit" class="space-y-4 max-w-lg">
              <div>
                <label class="block text-muted-color font-medium mb-2">Code Type <span class="text-red-500">*</span></label>
                <Select v-model="selectedCode" :options="codeOptions" option-label="label" placeholder="Select code type" class="w-full" :invalid="!!form.errors.assistance_code_reference_id" />
                <p v-if="form.errors.assistance_code_reference_id" class="text-xs text-red-500 mt-1">{{ form.errors.assistance_code_reference_id }}</p>
              </div>
              <div>
                <label class="block text-muted-color font-medium mb-2">Amount <span class="text-red-500">*</span></label>
                <InputNumber v-model="form.amount" :min="0" :step="100" placeholder="0.00" inputClass="w-full" class="w-full" :invalid="!!form.errors.amount" mode="currency" currency="PHP" locale="en-PH" />
                <p v-if="form.errors.amount" class="text-xs text-red-500 mt-1">{{ form.errors.amount }}</p>
              </div>
              <Button type="submit" label="Assign Code" icon="pi pi-check" :loading="form.processing" />
            </form>
          </div>
        </template>

        <div v-else class="mt-6 p-4 bg-surface-50 rounded-lg border border-surface">
          <h3 class="font-semibold text-surface-900 mb-2 text-sm uppercase tracking-wide text-muted-color">Assigned Code</h3>
          <div class="grid grid-cols-2 gap-3 text-sm">
            <div>
              <span class="text-muted-color">Code Type:</span>
              <p class="font-medium">{{ application.assistance_code.code_type }}</p>
            </div>
            <div>
              <span class="text-muted-color">Amount:</span>
              <p class="font-medium">{{ formatCurrency(application.assistance_code.amount) }}</p>
            </div>
            <div>
              <span class="text-muted-color">Assigned by:</span>
              <p class="font-medium">{{ application.assistance_code.assigned_by }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-4">
      <div class="card" style="min-width: 300px;">
        <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Review Trail</h3>
        <ReviewTrail :reviews="reviews" />
      </div>
    </div>

    <DocumentViewer
      :url="viewerUrl" :title="viewerTitle" :documents="viewerDocuments"
      :current-index="viewerIdx" @close="closeViewer" @prev="prevDoc" @next="nextDoc" />
  </div>
</template>
