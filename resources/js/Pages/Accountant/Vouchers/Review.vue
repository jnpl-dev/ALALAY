<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ApplicationInfo from '@/Components/Application/ApplicationInfo.vue'
import DocumentViewer from '@/Components/Application/DocumentViewer.vue'
import DocumentMeta from '@/Components/Application/DocumentMeta.vue'
import DocumentThumbnail from '@/Components/Common/DocumentThumbnail.vue'
import ReviewTrail from '@/Components/Application/ReviewTrail.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, computed } from 'vue'
import { formatCurrency } from '@/Utils/formatCurrency'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Accountant' }, { label: 'Vouchers' }, { label: 'Review' }])

const props = defineProps({
  application: { type: Object, required: true },
  documents: { type: Array, default: () => [] },
  reviews: { type: Array, default: () => [] },
  assistanceCode: { type: Object, default: null },
  socialCaseStudy: { type: Object, default: null },
  voucher: { type: Object, default: null },
})

const toast = useToast()
const confirm = useConfirm()
const route = window.route

const viewerUrl = ref(null)
const viewerTitle = ref('')
const viewerIdx = ref(0)
const viewerDocuments = ref([])
const approveLoading = ref(false)

const form = useForm({ remarks: '' })

const canReview = computed(() => props.application.status === 'voucher_recording')
const hasScs = computed(() => !!props.socialCaseStudy)

function viewDocument(doc, idx) {
  viewerUrl.value = doc.signed_url || null
  viewerTitle.value = doc.doc_name
  viewerIdx.value = idx
  viewerDocuments.value = props.documents
}

function viewVoucher() {
  viewerUrl.value = props.voucher?.signed_url || null
  viewerTitle.value = 'Voucher Document'
  viewerIdx.value = 0
  viewerDocuments.value = []
}

function viewScs() {
  viewerUrl.value = props.socialCaseStudy?.signed_url || null
  viewerTitle.value = 'Social Case Study'
  viewerIdx.value = 0
  viewerDocuments.value = []
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
    message: 'Approve this voucher and forward it to the Treasurer?',
    header: 'Confirm Approval',
    icon: 'pi pi-check-circle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Approve', severity: 'success' },
    accept: () => {
      approveLoading.value = true
      form.post(route('accountant.vouchers.approve', props.application.id), {
        preserveState: true,
        preserveScroll: true,
        onError: () => {
          toast.error('Approval failed')
          approveLoading.value = false
        },
        onFinish: () => { approveLoading.value = false },
      })
    },
  })
}
</script>

<template>
  <Head :title="'Voucher - ' + application.reference_code" />

  <div class="grid grid-cols-12 gap-8 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
    <div class="col-span-12 lg:col-span-8">
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="font-semibold text-xl">{{ application.reference_code }}</div>
            <AppStatusBadge :status="application.status" class="mt-1" />
          </div>
          <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text
            @click="router.get(route('accountant.vouchers.index'))" />
        </div>

        <ApplicationInfo :application="application" />

        <Divider />

        <div>
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Documents</h3>
          <div v-if="documents.length" class="grid grid-cols-2 sm:grid-cols-3 gap-3 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
            <div v-for="(doc, idx) in documents" :key="doc.id"
              class="relative group border border-surface rounded-lg overflow-hidden cursor-pointer hover:border-primary transition-colors duration-200"
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

        <Divider />

        <div>
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Assistance Code</h3>
          <dl v-if="assistanceCode" class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
            <div>
              <dt class="text-muted-color">Code Type</dt>
              <dd class="font-medium text-surface-900">{{ assistanceCode.code_type }}</dd>
            </div>
            <div>
              <dt class="text-muted-color">Amount</dt>
              <dd class="font-medium text-surface-900">{{ formatCurrency(assistanceCode.amount) }}</dd>
            </div>
            <div v-if="assistanceCode.description">
              <dt class="text-muted-color">Description</dt>
              <dd class="font-medium text-surface-900">{{ assistanceCode.description }}</dd>
            </div>
            <div v-if="assistanceCode.default_amount">
              <dt class="text-muted-color">Default Amount</dt>
              <dd class="font-medium text-surface-900">{{ formatCurrency(assistanceCode.default_amount) }}</dd>
            </div>
            <div>
              <dt class="text-muted-color">Assigned by</dt>
              <dd class="font-medium text-surface-900">{{ assistanceCode.assigned_by }}</dd>
            </div>
            <div v-if="assistanceCode.assigned_at">
              <dt class="text-muted-color">Assigned on</dt>
              <dd class="font-medium text-surface-900">{{ assistanceCode.assigned_at }}</dd>
            </div>
          </dl>
          <p v-else class="text-sm text-muted-color py-4 text-center">
            No assistance code has been assigned to this application yet.
          </p>
        </div>

        <Divider v-if="hasScs" />

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

        <Divider />

        <div>
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Voucher</h3>
          <div v-if="voucher">
            <DocumentMeta
              :uploaded-by="voucher.prepared_by"
              :uploaded-at="voucher.prepared_at"
              :page-count="voucher.page_count"
              :file-size="voucher.file_size_label"
              :version="voucher.version"
            />
            <div class="mt-3">
              <Button icon="pi pi-eye" label="View Voucher" severity="secondary" outlined @click="viewVoucher" />
            </div>
          </div>
          <p v-else class="text-sm text-muted-color py-4 text-center">
            No voucher has been attached to this application yet.
          </p>
        </div>

        <template v-if="canReview">
          <Divider />

          <div class="flex gap-3">
            <Button label="Approve & Forward" icon="pi pi-check" severity="success" @click="confirmApprove"
              :loading="approveLoading" class="active:scale-[0.98] transition-transform" />
          </div>
        </template>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-4">
      <div class="card sticky top-24" style="min-width: 300px;">
        <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Review Trail</h3>
        <ReviewTrail :reviews="reviews" />
      </div>
    </div>

    <DocumentViewer
      :url="viewerUrl" :title="viewerTitle" :documents="viewerDocuments"
      :current-index="viewerIdx" @close="closeViewer" @prev="prevDoc" @next="nextDoc" />
  </div>
</template>
