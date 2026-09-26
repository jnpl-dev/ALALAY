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
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, computed } from 'vue'
import { formatCurrency } from '@/Utils/formatCurrency'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Budget Office' }, { label: 'Vouchers' }, { label: 'Check' }])

const props = defineProps({
  application: { type: Object, required: true },
  documents: { type: Array, default: () => [] },
  reviews: { type: Array, default: () => [] },
  assistanceCode: { type: Object, default: null },
  voucher: { type: Object, default: null },
})

const toast = useToast()
const confirm = useConfirm()
const route = window.route

const viewerUrl = ref(null)
const viewerTitle = ref('')
const viewerIdx = ref(0)
const viewerDocuments = ref([])
const showHoldDialog = ref(false)
const approveLoading = ref(false)
const holdLoading = ref(false)
const releaseLoading = ref(false)
const form = useForm({ remarks: '' })

const canCheck = computed(() => props.application.status === 'budget_checking')
const isOnHold = computed(() => props.application.status === 'voucher_on_hold')

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
    message: 'Approve this voucher and forward it to the Accountant?',
    header: 'Confirm Approval',
    icon: 'pi pi-check-circle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Approve', severity: 'success' },
    accept: () => {
      approveLoading.value = true
      form.post(route('budget-office.vouchers.approve', props.application.id), {
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

function confirmHold() {
  showHoldDialog.value = true
  form.clearErrors()
  form.reset()
}

function submitHold() {
  holdLoading.value = true
  form.post(route('budget-office.vouchers.hold', props.application.id), {
    preserveState: true,
    preserveScroll: true,
    onError: () => {
      toast.error('Failed to place voucher on hold')
      holdLoading.value = false
    },
    onFinish: () => {
      holdLoading.value = false
      showHoldDialog.value = false
    },
  })
}

function confirmReleaseHold() {
  confirm.require({
    message: 'Release this voucher from hold and forward it to the Accountant?',
    header: 'Confirm Release',
    icon: 'pi pi-check-circle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Release', severity: 'success' },
    accept: () => {
      releaseLoading.value = true
      form.post(route('budget-office.vouchers.release-hold', props.application.id), {
        preserveState: true,
        preserveScroll: true,
        onError: () => {
          toast.error('Failed to release hold')
          releaseLoading.value = false
        },
        onFinish: () => { releaseLoading.value = false },
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
            @click="router.get(route('budget-office.vouchers.index'))" />
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
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Assistance Coding</h3>
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

        <template v-if="canCheck">
          <Divider />

          <div class="flex gap-3">
            <Button label="Approve & Forward" icon="pi pi-check" severity="success" @click="confirmApprove"
              :loading="approveLoading" class="active:scale-[0.98] transition-transform" />
            <Button label="Place on Hold" icon="pi pi-pause-circle" severity="warn" outlined @click="confirmHold"
              :loading="holdLoading" class="active:scale-[0.98] transition-transform" />
          </div>
        </template>

        <template v-if="isOnHold">
          <Divider />

          <div class="flex gap-3">
            <Button label="Release Hold & Forward" icon="pi pi-play" severity="success" @click="confirmReleaseHold"
              :loading="releaseLoading" class="active:scale-[0.98] transition-transform" />
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

    <Dialog v-model:visible="showHoldDialog" header="Place Voucher on Hold" :modal="true" :closable="false" class="w-full max-w-lg">
      <div class="space-y-4">
        <p class="text-sm text-muted-color">Provide remarks explaining why this voucher is being placed on hold.</p>
        <div>
          <label class="block text-muted-color font-medium mb-2">Remarks</label>
          <Textarea v-model="form.remarks" rows="4" placeholder="Optional remarks..." class="w-full" :invalid="!!form.errors.remarks" />
          <p v-if="form.errors.remarks" class="text-xs text-red-400 mt-1">{{ form.errors.remarks }}</p>
        </div>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <Button label="Cancel" severity="secondary" outlined :disabled="form.processing" @click="showHoldDialog = false" />
          <Button label="Place on Hold" icon="pi pi-pause-circle" severity="warn" :loading="holdLoading" @click="submitHold" />
        </div>
      </template>
    </Dialog>
  </div>
</template>
