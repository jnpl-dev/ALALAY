<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ApplicationInfo from '@/Components/Application/ApplicationInfo.vue'
import DocumentViewer from '@/Components/Application/DocumentViewer.vue'
import DocumentMeta from '@/Components/Application/DocumentMeta.vue'
import ReviewTrail from '@/Components/Application/ReviewTrail.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import Fieldset from 'primevue/fieldset'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, computed } from 'vue'
import { formatCurrency } from '@/Utils/formatCurrency'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Treasurer' }, { label: 'Cheques' }, { label: 'Review' }])

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
const acknowledgeLoading = ref(false)
const claimLoading = ref(false)

const form = useForm({ remarks: '' })

const canReview = computed(() => props.application.status === 'with_treasurer')
const isChequeReady = computed(() => props.application.status === 'cheque_ready')

function viewVoucher() {
  viewerUrl.value = props.voucher?.signed_url || null
  viewerTitle.value = 'Voucher Document'
}

function confirmReady() {
  confirm.require({
    message: 'Acknowledge this voucher and mark as Cheque Ready? The applicant will be notified.',
    header: 'Acknowledge & Ready',
    icon: 'pi pi-check-circle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Acknowledge & Ready', severity: 'success' },
    accept: () => {
      acknowledgeLoading.value = true
      form.post(route('treasurer.cheques.acknowledge', props.application.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Voucher acknowledged. Cheque marked as ready for claiming.'),
        onError: () => {
          toast.error('Failed to acknowledge')
          acknowledgeLoading.value = false
        },
        onFinish: () => { acknowledgeLoading.value = false },
      })
    },
  })
}

function confirmClaim() {
  confirm.require({
    message: 'Mark this cheque as completed? The applicant has claimed the cheque.',
    header: 'Mark as Complete',
    icon: 'pi pi-check',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Mark Complete', severity: 'success' },
    accept: () => {
      claimLoading.value = true
      form.post(route('treasurer.cheques.claim', props.application.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Cheque marked as completed'),
        onError: () => {
          toast.error('Failed to mark as complete')
          claimLoading.value = false
        },
        onFinish: () => { claimLoading.value = false },
      })
    },
  })
}
</script>

<template>
  <Head :title="'Cheque - ' + application.reference_code" />

  <div class="grid grid-cols-12 gap-8 transition duration-200 ease-[cubic-bezier(0.16,1,0.3,1)]">
    <div class="col-span-12 lg:col-span-8">
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div>
            <div class="font-semibold text-xl">{{ application.reference_code }}</div>
            <AppStatusBadge :status="application.status" class="mt-1" />
          </div>
          <Button icon="pi pi-arrow-left" label="Back" severity="secondary" text
            @click="router.get(route('treasurer.cheques.index'))" />
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

        <template v-if="canReview">
          <Divider />

          <div class="flex gap-3">
            <Button label="Acknowledge &amp; Ready" icon="pi pi-check-circle" severity="success" @click="confirmReady" :loading="acknowledgeLoading" class="active:scale-[0.98] transition-transform" />
          </div>
        </template>

        <template v-if="isChequeReady">
          <Divider />

          <div class="flex gap-3">
            <Button label="Mark as Complete" icon="pi pi-check" severity="success" @click="confirmClaim" :loading="claimLoading" class="active:scale-[0.98] transition-transform" />
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

    <DocumentViewer :url="viewerUrl" :title="viewerTitle" @close="viewerUrl = null" />
  </div>
</template>
