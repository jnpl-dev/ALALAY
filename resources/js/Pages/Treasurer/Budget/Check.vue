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
import Divider from 'primevue/divider'
import Fieldset from 'primevue/fieldset'
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
const showHoldDialog = ref(false)
const holdRemarks = ref('')
const markReadyLoading = ref(false)
const holdDialogLoading = ref(false)
const holdSubmitting = ref(false)
const reEvaluateLoading = ref(false)

const form = useForm({ remarks: '' })

function openHoldDialog() {
  holdDialogLoading.value = true
  showHoldDialog.value = true
}

const isBudgetChecking = computed(() => props.application.status === 'budget_checking')
const isChequeReady = computed(() => props.application.status === 'cheque_ready')
const isOnHold = computed(() => props.application.status === 'on_hold')

function viewVoucher() {
  viewerUrl.value = props.voucher?.signed_url || null
  viewerTitle.value = 'Voucher Document'
}

function confirmMarkReady() {
  confirm.require({
    message: 'Approve this application and mark as Cheque Ready? The applicant will be notified.',
    header: 'Approve & Ready',
    icon: 'pi pi-check-circle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Approve & Ready', severity: 'success' },
    accept: () => {
      markReadyLoading.value = true
      form.post(route('treasurer.budget.mark-ready', props.application.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Marked as cheque ready'),
        onError: () => {
          toast.error('Failed to mark as ready')
          markReadyLoading.value = false
        },
        onFinish: () => { markReadyLoading.value = false },
      })
    },
  })
}

function submitHold() {
  holdSubmitting.value = true
  form.remarks = holdRemarks.value
  form.post(route('treasurer.budget.hold', props.application.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      showHoldDialog.value = false
      holdRemarks.value = ''
      toast.success(isBudgetChecking.value ? 'Application approved and placed on hold' : 'Application placed on hold')
    },
    onError: () => {
      toast.error('Failed to place on hold')
      holdSubmitting.value = false
    },
    onFinish: () => { holdSubmitting.value = false },
  })
}

function onHoldDialogHide() {
  holdRemarks.value = ''
  holdDialogLoading.value = false
  holdSubmitting.value = false
}

function confirmReEvaluate() {
  confirm.require({
    message: 'Re-evaluate and mark this application as Cheque Ready?',
    header: 'Confirm Re-evaluation',
    icon: 'pi pi-refresh',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Re-evaluate', severity: 'success' },
    accept: () => {
      reEvaluateLoading.value = true
      form.post(route('treasurer.budget.re-evaluate', props.application.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Application re-evaluated and marked as cheque ready'),
        onError: () => {
          toast.error('Failed to re-evaluate')
          reEvaluateLoading.value = false
        },
        onFinish: () => { reEvaluateLoading.value = false },
      })
    },
  })
}
</script>

<template>
  <Head :title="'Budget - ' + application.reference_code" />

  <div class="grid grid-cols-12 gap-8 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
    <div class="col-span-12 lg:col-span-8">
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="font-semibold text-xl">{{ application.reference_code }}</div>
            <AppStatusBadge :status="application.status" class="mt-1" />
          </div>
          <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text
            @click="router.get(route('treasurer.budget.index'))" />
        </div>

        <ApplicationInfo :application="application" />

        <Divider />

        <div v-if="assistanceCode">
          <Fieldset legend="Assistance Code">
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
          </Fieldset>
        </div>

        <Divider />

        <div v-if="voucher">
          <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Voucher Document</h3>
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

        <template v-if="isBudgetChecking || isOnHold || isChequeReady">
          <Divider />

          <div class="flex gap-3 flex-wrap">
            <Button v-if="isBudgetChecking" label="Approve &amp; Ready" icon="pi pi-check-circle" severity="success" @click="confirmMarkReady" :loading="markReadyLoading" class="active:scale-[0.98] transition-transform" />
            <Button v-if="isBudgetChecking" label="Approve but On Hold" icon="pi pi-pause-circle" severity="warn" @click="openHoldDialog" :loading="holdDialogLoading" class="active:scale-[0.98] transition-transform" />
            <Button v-if="isChequeReady" label="Place on Hold" icon="pi pi-pause-circle" severity="warn" @click="openHoldDialog" :loading="holdDialogLoading" class="active:scale-[0.98] transition-transform" />
            <Button v-if="isOnHold" label="Re-evaluate & Mark Ready" icon="pi pi-refresh" severity="success" @click="confirmReEvaluate" :loading="reEvaluateLoading" class="active:scale-[0.98] transition-transform" />
          </div>
        </template>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-4">
      <div class="card sticky top-24">
        <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Review Trail</h3>
        <ReviewTrail :reviews="reviews" />
      </div>
    </div>

    <Dialog v-model:visible="showHoldDialog" :header="isBudgetChecking ? 'Approve but On Hold' : 'Place on Hold'" :modal="true" class="w-full max-w-md" @after-hide="onHoldDialogHide">
      <div class="space-y-4">
        <p class="text-sm text-muted-color">{{ isBudgetChecking ? 'Approve this application but place it on hold. Provide a reason for the hold.' : 'Place this application on hold. Provide a reason for the hold.' }}</p>
        <Textarea v-model="holdRemarks" placeholder="Reason for hold..." class="w-full" rows="4" :invalid="form.errors.remarks ? true : false" />
        <p v-if="form.errors.remarks" class="text-xs text-red-500">{{ form.errors.remarks }}</p>
      </div>
      <div class="flex justify-end gap-2 mt-6">
        <Button label="Cancel" severity="secondary" outlined @click="showHoldDialog = false" />
        <Button :label="isBudgetChecking ? 'Approve & Hold' : 'Confirm Hold'" icon="pi pi-pause-circle" severity="warn" @click="submitHold" :loading="holdSubmitting" />
      </div>
    </Dialog>

    <DocumentViewer :url="viewerUrl" :title="viewerTitle" @close="viewerUrl = null" />
  </div>
</template>
