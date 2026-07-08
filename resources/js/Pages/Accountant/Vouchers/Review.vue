<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ApplicationInfo from '@/Components/Application/ApplicationInfo.vue'
import DocumentViewer from '@/Components/Application/DocumentViewer.vue'
import DocumentMeta from '@/Components/Application/DocumentMeta.vue'
import ReviewTrail from '@/Components/Application/ReviewTrail.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import Button from 'primevue/button'
import Dialog from 'primevue/dialog'
import Textarea from 'primevue/textarea'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, computed } from 'vue'
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

const props = defineProps({
  application: { type: Object, required: true },
  reviews: { type: Array, default: () => [] },
  assistanceCode: { type: Object, default: null },
  voucher: { type: Object, default: null },
})

const toast = useToast()
const confirm = useConfirm()
const route = window.route

const viewerUrl = ref(null)
const viewerTitle = ref('')
const showReturnDialog = ref(false)
const returnRemarks = ref('')

const form = useForm({ remarks: '' })

const canReview = computed(() => props.application.status === 'voucher_checking')

function viewVoucher() {
  viewerUrl.value = props.voucher?.signed_url || null
  viewerTitle.value = 'Voucher Document'
}

function confirmApprove() {
  confirm.require({
    message: 'Approve this voucher and forward to the Treasurer?',
    header: 'Confirm Approval',
    icon: 'pi pi-check-circle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Approve', severity: 'success' },
    accept: () => {
      form.post(route('accountant.vouchers.approve', props.application.id), {
        preserveScroll: true,
        onSuccess: () => toast.success('Voucher approved'),
        onError: () => toast.error('Approval failed'),
      })
    },
  })
}

function submitReturn() {
  form.remarks = returnRemarks.value
  form.post(route('accountant.vouchers.return', props.application.id), {
    preserveScroll: true,
    onSuccess: () => {
      showReturnDialog.value = false
      returnRemarks.value = ''
      toast.success('Voucher returned')
    },
    onError: () => toast.error('Return failed'),
  })
}
</script>

<template>
  <Head :title="'Voucher - ' + application.reference_code" />

  <div class="grid grid-cols-12 gap-8">
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

        <hr class="border-surface my-6" />

        <div v-if="assistanceCode">
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Assistance Code</h3>
          <div class="bg-surface-50 rounded-lg border border-surface p-4">
            <dl class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <dt class="text-muted-color">Code Type</dt>
                <dd class="font-medium text-surface-900">{{ assistanceCode.code_type }}</dd>
              </div>
              <div>
                <dt class="text-muted-color">Amount</dt>
                <dd class="font-medium text-surface-900">{{ formatCurrency(assistanceCode.amount) }}</dd>
              </div>
              <div>
                <dt class="text-muted-color">Assigned by</dt>
                <dd class="font-medium text-surface-900">{{ assistanceCode.assigned_by }}</dd>
              </div>
            </dl>
          </div>
        </div>

        <hr class="border-surface my-6" />

        <div v-if="voucher">
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Voucher Document</h3>
          <DocumentMeta
            :uploaded-by="voucher.prepared_by"
            :uploaded-at="voucher.prepared_at"
            :page-count="voucher.page_count"
            :file-size="voucher.file_size_label"
            :version="voucher.version"
            :returned-by="voucher.returned_by"
            :returned-at="voucher.returned_at"
            :return-remarks="voucher.adjustment_remarks"
          />
          <div class="mt-3">
            <Button icon="pi pi-eye" label="View Voucher" severity="secondary" outlined @click="viewVoucher" />
          </div>
        </div>

        <template v-if="canReview">
          <hr class="border-surface my-6" />

          <div class="flex gap-3">
            <Button label="Approve & Forward" icon="pi pi-check" severity="success" @click="confirmApprove" :loading="form.processing" />
            <Button label="Return to MSWDO" icon="pi pi-undo" severity="warn" @click="showReturnDialog = true" :loading="form.processing" />
          </div>
        </template>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-4">
      <div class="card" style="position: sticky; top: 6rem; min-width: 300px;">
        <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Review Trail</h3>
        <ReviewTrail :reviews="reviews" />
      </div>
    </div>

    <Dialog v-model:visible="showReturnDialog" header="Return Voucher" :modal="true" class="w-full max-w-md" @after-hide="returnRemarks = ''">
      <div class="space-y-4">
        <p class="text-sm text-muted-color">Return this voucher to MSWDO for revision. Provide remarks to guide the revision.</p>
        <Textarea v-model="returnRemarks" placeholder="Explain why the voucher is being returned..." class="w-full" rows="4" :invalid="form.errors.remarks ? true : false" />
        <p v-if="form.errors.remarks" class="text-xs text-red-500">{{ form.errors.remarks }}</p>
      </div>
      <div class="flex justify-end gap-2 mt-6">
        <Button label="Cancel" severity="secondary" outlined @click="showReturnDialog = false" />
        <Button label="Submit Return" icon="pi pi-undo" severity="warn" @click="submitReturn" :loading="form.processing" />
      </div>
    </Dialog>

    <DocumentViewer :url="viewerUrl" :title="viewerTitle" @close="viewerUrl = null" />
  </div>
</template>
