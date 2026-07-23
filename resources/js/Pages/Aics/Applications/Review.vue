<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ApplicationInfo from '@/Components/Application/ApplicationInfo.vue'
import DocumentViewer from '@/Components/Application/DocumentViewer.vue'
import ReviewTrail from '@/Components/Application/ReviewTrail.vue'
import ReturnModal from '@/Components/Application/ReturnModal.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import DocumentThumbnail from '@/Components/Common/DocumentThumbnail.vue'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, computed, watch } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  application: { type: Object, required: true },
  documents: { type: Array, default: () => [] },
  reviews: { type: Array, default: () => [] },
})

const toast = useToast()
const confirm = useConfirm()
const route = window.route

const viewerUrl = ref(null)
const viewerTitle = ref('')
const viewerIdx = ref(0)
const showReturnModal = ref(false)
const approving = ref(false)
const returning = ref(false)

const canReview = computed(() => ['submitted', 'screening'].includes(props.application.status))

function viewDocument(doc, idx) {
  viewerUrl.value = route('aics.applications.document-url', [props.application.id, doc.id])
  viewerTitle.value = doc.doc_name
  viewerIdx.value = idx
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
    message: 'Approve this application and forward to MSWDO review?',
    header: 'Confirm Approval',
    icon: 'pi pi-check-circle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Approve', severity: 'success' },
    reject: () => { approving.value = false },
    accept: () => {
      approving.value = true
      router.post(route('aics.applications.approve', props.application.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onError: () => toast.error('Approval failed'),
        onFinish: () => { approving.value = false },
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
    reject: () => { returning.value = false },
    accept: () => {
      returning.value = true
      openReturnModal()
    },
  })
}

function openReturnModal() {
  showReturnModal.value = true
}

watch(showReturnModal, (val) => {
  if (!val) returning.value = false
})

function onReturnConfirmed(data) {
  router.post(route('aics.applications.return', props.application.id), data, {
    preserveState: true,
    preserveScroll: true,
    onError: () => toast.error('Return failed'),
    onFinish: () => { returning.value = false },
  })
  showReturnModal.value = false
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
            @click="router.get(route('aics.applications.index'))" />
        </div>

        <ApplicationInfo :application="application" />

        <Divider />

        <div>
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Documents</h3>
          <div v-if="documents.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
            <div v-for="(doc, idx) in documents" :key="doc.id"
              class="relative group border border-surface rounded-lg overflow-hidden cursor-pointer hover:border-primary transition-colors"
              @click="viewDocument(doc, idx)">
              <div class="aspect-[3/4] flex items-center justify-center bg-surface-50 dark:bg-surface-800 overflow-hidden">
                <DocumentThumbnail :doc="doc" />
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

        <Divider v-if="canReview" />

        <div v-if="canReview" class="flex gap-3">
          <Button label="Approve" icon="pi pi-check" severity="success" :loading="approving" @click="confirmApprove" />
          <Button label="Return" icon="pi pi-undo" severity="warn" :loading="returning" @click="confirmReturn" />
        </div>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-4">
      <div class="card sticky top-24 min-w-72">
        <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Review Trail</h3>
        <ReviewTrail :reviews="reviews" />
      </div>
    </div>

    <DocumentViewer
      :url="viewerUrl" :title="viewerTitle" :documents="documents"
      :current-index="viewerIdx" @close="closeViewer" @prev="prevDoc" @next="nextDoc" />
    <ReturnModal v-model:visible="showReturnModal" :submitted-documents="documents" @confirmed="onReturnConfirmed" />
  </div>
</template>
