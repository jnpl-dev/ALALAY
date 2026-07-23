<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import ApplicationInfo from '@/Components/Application/ApplicationInfo.vue'
import DocumentMeta from '@/Components/Application/DocumentMeta.vue'
import DocumentViewer from '@/Components/Application/DocumentViewer.vue'
import DocumentScanner from '@/Components/Application/DocumentScanner.vue'
import ReviewTrail from '@/Components/Application/ReviewTrail.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import Fieldset from 'primevue/fieldset'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, computed } from 'vue'
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

const props = defineProps({
  application: { type: Object, required: true },
  reviews: { type: Array, default: () => [] },
  socialCaseStudy: { type: Object, default: null },
  assistanceCode: { type: Object, default: null },
  existingVoucher: { type: Object, default: null },
  canEdit: { type: Boolean, default: true },
})

const toast = useToast()
const confirm = useConfirm()
const route = window.route

const viewerUrl = ref(null)
const viewerTitle = ref('')

const showExistingVoucher = ref(false)

const form = useForm({
  voucher_file: null,
  page_count: 1,
  adjustment_remarks: '',
})

const isRecreation = computed(() => !!props.existingVoucher)
const scsDocName = computed(() => 'Social Case Study')
const voucherDocName = computed(() => 'Voucher Document')

function onDocCapture(payload) {
  form.voucher_file = payload.file
  form.page_count = payload.pageCount || 1
}

function confirmSubmit() {
  confirm.require({
    message: isRecreation.value
      ? `Re-create Voucher v${props.existingVoucher.version}? Previous version will be replaced.`
      : 'Submit this voucher for Accountant review?',
    header: 'Confirm Voucher',
    icon: 'pi pi-receipt',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Submit', severity: 'success' },
    accept: () => {
      form.post(route('mswdo.vouchers.store', props.application.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Voucher submitted'),
        onError: () => toast.error('Validation error'),
      })
    },
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
            @click="router.get(route('mswdo.vouchers.index'))" />
        </div>

        <ApplicationInfo :application="application" />

        <Divider />

        <Transition name="slide-fade">
          <Fieldset v-if="assistanceCode" legend="Assistance Code">
            <dl class="grid grid-cols-2 gap-3 text-sm">
              <div>
                <dt class="text-muted-color">Code Type</dt>
                <dd class="font-medium">{{ assistanceCode.code_type }}</dd>
              </div>
              <div>
                <dt class="text-muted-color">Amount</dt>
                <dd class="font-medium">{{ formatCurrency(assistanceCode.amount) }}</dd>
              </div>
              <div>
                <dt class="text-muted-color">Assigned by</dt>
                <dd class="font-medium">{{ assistanceCode.assigned_by }}</dd>
              </div>
            </dl>
          </Fieldset>
        </Transition>

        <Transition name="slide-fade">
          <Fieldset v-if="socialCaseStudy" legend="Social Case Study">
            <DocumentMeta
              :uploaded-by="socialCaseStudy.uploaded_by"
              :uploaded-at="socialCaseStudy.conducted_at"
              :page-count="socialCaseStudy.page_count"
              :file-size="socialCaseStudy.file_size_label"
            />
            <div class="mt-3">
              <Button icon="pi pi-eye" label="View Case Study" severity="secondary" outlined @click="viewerUrl = socialCaseStudy.signed_url; viewerTitle = 'Social Case Study'" />
            </div>
          </Fieldset>
        </Transition>

        <Transition name="slide-fade">
          <Fieldset v-if="isRecreation && canEdit" legend="Previous Voucher">
            <DocumentMeta
              :uploaded-by="existingVoucher.prepared_by"
              :uploaded-at="existingVoucher.prepared_at"
              :page-count="existingVoucher.page_count"
              :file-size="existingVoucher.file_size_label"
              :version="existingVoucher.version"
              :returned-by="existingVoucher.returned_by"
              :returned-at="existingVoucher.returned_at"
              :return-remarks="existingVoucher.adjustment_remarks"
            />
            <div class="mt-3">
              <Button icon="pi pi-eye" label="View Previous Voucher" severity="secondary" outlined @click="viewerUrl = existingVoucher.signed_url; viewerTitle = 'Voucher v' + existingVoucher.version" />
            </div>
          </Fieldset>
        </Transition>

        <Transition name="slide-fade">
          <div v-if="canEdit">
            <Fieldset legend="Voucher Document">
              <DocumentScanner
                :doc-name="voucherDocName"
                :required="true"
                capture-type="single"
                scanner-size="a4"
                @captured="onDocCapture"
              />
            </Fieldset>

            <div class="flex gap-3 mt-6">
              <Button
                label="Submit Voucher"
                icon="pi pi-send"
                severity="success"
                @click="confirmSubmit"
                :loading="form.processing"
                :disabled="!form.voucher_file"
              />
              <Button label="Cancel" severity="secondary" outlined
                @click="router.get(route('mswdo.vouchers.index'))" />
            </div>
            <p v-if="!form.voucher_file" class="text-xs text-muted-color mt-2">Capture or upload the voucher document before submitting.</p>
          </div>
        </Transition>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-4">
      <div class="card sticky top-24" style="min-width: 300px;">
        <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Review Trail</h3>
        <ReviewTrail :reviews="reviews" />
      </div>
    </div>

    <DocumentViewer :url="viewerUrl" :title="viewerTitle" @close="viewerUrl = null" />
  </div>
</template>

<style scoped>
.slide-fade-enter-active {
  transition: all 0.3s ease-out;
}
.slide-fade-enter-from {
  opacity: 0;
  transform: translateY(8px);
}
</style>
