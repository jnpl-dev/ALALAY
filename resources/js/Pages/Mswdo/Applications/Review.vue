<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ApplicationInfo from '@/Components/Application/ApplicationInfo.vue'
import DocumentViewer from '@/Components/Application/DocumentViewer.vue'
import DocumentMeta from '@/Components/Application/DocumentMeta.vue'
import DocumentScanner from '@/Components/Application/DocumentScanner.vue'
import ReviewTrail from '@/Components/Application/ReviewTrail.vue'
import ReturnModal from '@/Components/Application/ReturnModal.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import Button from 'primevue/button'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, computed } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  application: { type: Object, required: true },
  documents: { type: Array, default: () => [] },
  reviews: { type: Array, default: () => [] },
  socialCaseStudy: { type: Object, default: null },
})

const toast = useToast()
const confirm = useConfirm()
const route = window.route

const viewerUrl = ref(null)
const viewerTitle = ref('')
const viewerIdx = ref(0)
const viewerDocuments = ref([])
const showReturnModal = ref(false)
const form = useForm({
  social_case_study: null,
  page_count: 1,
  remarks: '',
})

const canReview = computed(() => props.application.status === 'mswdo_review')
const hasScs = computed(() => !!props.socialCaseStudy)
const scsDocName = computed(() => 'Social Case Study')

function onDocCapture(payload) {
  form.social_case_study = payload.file
  form.page_count = payload.pageCount || 1
}

function viewDocument(doc, idx) {
  viewerUrl.value = route('mswdo.applications.document-url', [props.application.id, doc.id])
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

function confirmApprove() {
  confirm.require({
    message: 'Approve this application and upload the social case study? This will move the application to Assistance Coding.',
    header: 'Confirm Approval',
    icon: 'pi pi-check-circle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Approve', severity: 'success' },
    accept: () => {
      form.post(route('mswdo.applications.approve', props.application.id), {
        preserveState: true,
        preserveScroll: true,
        onError: () => toast.error('Approval failed. Check that the social case study PDF is attached.'),
      })
    },
  })
}

function confirmReturn() {
  confirm.require({
    message: 'Return this application to the applicant for revisions?',
    header: 'Confirm Return',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Continue', severity: 'warn' },
    accept: () => {
      openReturnModal()
    },
  })
}

function openReturnModal() {
  showReturnModal.value = true
}

function onReturnConfirmed(data) {
  router.post(route('mswdo.applications.return', props.application.id), data, {
    preserveState: true,
    preserveScroll: true,
    onError: () => toast.error('Return failed'),
  })
  showReturnModal.value = false
}

function viewScs() {
  viewerUrl.value = props.socialCaseStudy?.signed_url || null
  viewerTitle.value = 'Social Case Study'
  viewerIdx.value = 0
  viewerDocuments.value = []
}
</script>

<template>
  <Head :title="'Review - ' + application.reference_code" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12 lg:col-span-8">
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="font-semibold text-xl">{{ application.reference_code }}</div>
            <AppStatusBadge :status="application.status" class="mt-1" />
          </div>
          <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text
            @click="router.get(route('mswdo.applications.index'))" />
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

        <hr v-if="canReview" class="border-surface my-6" />

        <div v-if="canReview" class="space-y-6">
          <div>
            <h3 class="font-semibold text-surface-900 mb-4 text-sm uppercase tracking-wide text-muted-color">
              Capture Social Case Study
            </h3>
            <DocumentScanner
              :doc-name="scsDocName"
              :required="true"
              capture-type="multi"
              scanner-size="a4"
              @captured="onDocCapture"
            />
          </div>

          <div class="flex gap-3">
            <Button label="Approve & Forward" icon="pi pi-check" severity="success" @click="confirmApprove" :loading="form.processing" :disabled="!form.social_case_study" />
            <Button label="Return" icon="pi pi-undo" severity="warn" @click="confirmReturn" />
          </div>
          <p v-if="!form.social_case_study" class="text-xs text-muted-color">Capture the social case study document before approving.</p>
        </div>

        <div v-else-if="!canReview && !hasScs" class="text-sm text-muted-color py-4 text-center">
          This application is no longer in MSWDO review.
        </div>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-4">
      <div class="card" style="position: sticky; top: 6rem; min-width: 300px;">
        <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Review Trail</h3>
        <ReviewTrail :reviews="reviews" />
      </div>
    </div>

    <DocumentViewer
      :url="viewerUrl" :title="viewerTitle" :documents="viewerDocuments"
      :current-index="viewerIdx" @close="closeViewer" @prev="prevDoc" @next="nextDoc" />
    <ReturnModal v-model:visible="showReturnModal" :submitted-documents="documents" @confirmed="onReturnConfirmed" />
  </div>
</template>
