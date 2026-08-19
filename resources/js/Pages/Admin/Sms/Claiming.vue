<script setup>
import { ref, watch } from 'vue'
import { Head, useForm, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import DatePicker from 'primevue/datepicker'
import { useConfirm } from '@/Composables/useConfirm'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Admin' }, { label: 'Settings' }, { label: 'SMS Claiming' }])

const toast = useToast()

const route = window.route
const confirm = useConfirm()
const isEditingTemplate = ref(false)

const props = defineProps({
  template: { type: String, default: '' },
  readyCount: { type: Number, default: 0 },
})

const templateForm = useForm({ value: '' })
let savedTemplate = ''

const claimingDate = ref(null)
const claimingForm = useForm({ claiming_date: '' })

const today = new Date()
const minDate = new Date(today.getFullYear(), today.getMonth(), today.getDate())

function formatDateParam(date) {
  if (!date) return ''
  const d = new Date(date)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

const templatePlaceholders = '{reference_code}, {claimant_name}, {track_url}, {claiming_date}'

watch(() => props.template, (value) => {
  if (value) {
    savedTemplate = value
    templateForm.value = value
  }
}, { immediate: true })

function startEditTemplate() {
  savedTemplate = templateForm.value
  isEditingTemplate.value = true
}

function cancelEditTemplate() {
  templateForm.value = savedTemplate
  templateForm.clearErrors()
  isEditingTemplate.value = false
}

function saveTemplate() {
  templateForm.post(route('admin.sms.claiming.template'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      isEditingTemplate.value = false
      toast.success('Saved', 'Claiming template updated successfully.')
    },
  })
}

function confirmTrigger() {
  if (!claimingDate.value) return

  const dateStr = formatDateParam(claimingDate.value)
  const count = props.readyCount

  confirm.require({
    message: `Send claiming SMS to ${count} applicant(s) for ${dateStr}?`,
    header: 'Trigger Claiming Notification',
    icon: 'pi pi-exclamation-triangle',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Send', severity: 'success' },
    accept: () => {
      claimingForm.claiming_date = dateStr
      claimingForm.post(route('admin.sms.claiming.trigger'), {
        preserveScroll: true,
        onSuccess: () => {
          claimingDate.value = null
          claimingForm.claiming_date = ''
          toast.success('Sent', 'Claiming notification sent successfully.')
        },
      })
    },
  })
}
</script>

<template>
  <Head title="SMS — Claiming" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12 lg:col-span-7">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Claiming</div>
          <div class="flex gap-2">
            <Button
              v-if="isEditingTemplate"
              type="button"
              label="Cancel"
              icon="pi pi-times"
              severity="secondary"
              @click="cancelEditTemplate"
            />
            <Button
              :label="isEditingTemplate ? 'Save Template' : 'Edit Template'"
              :icon="isEditingTemplate ? 'pi pi-check' : 'pi pi-pencil'"
              :loading="templateForm.processing"
              @click="isEditingTemplate ? saveTemplate() : startEditTemplate()"
            />
          </div>
        </div>

        <p class="text-sm text-muted-color mb-4">This message is sent when the admin triggers a mass claiming notification. Available placeholders: <code class="bg-surface-100 px-1 rounded text-emerald-600">{{ templatePlaceholders }}</code></p>

        <Deferred data="template">
          <Textarea
            v-model="templateForm.value"
            class="w-full"
            :disabled="!isEditingTemplate"
            rows="4"
            autoResize
          />

          <template #fallback>
            <Skeleton width="100%" height="6rem" />
          </template>
        </Deferred>
      </div>
    </div>

    <div class="col-span-12 lg:col-span-5">
      <div class="card">
        <div class="font-semibold text-xl mb-6">Trigger Mass Notification</div>

        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-lg">
          <div class="text-sm text-muted-color">Applications ready for claiming</div>
          <div class="text-3xl font-bold text-emerald-600">{{ readyCount }}</div>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-surface-700 mb-2">Claiming Date</label>
          <DatePicker
            v-model="claimingDate"
            :min-date="minDate"
            date-format="yy-mm-dd"
            class="w-full"
            placeholder="Select claiming date"
            show-icon
          />
        </div>

        <Button
          label="Send Claiming Notification"
          icon="pi pi-send"
          class="w-full"
          :disabled="!claimingDate || readyCount === 0 || claimingForm.processing"
          :loading="claimingForm.processing"
          @click="confirmTrigger"
        />
      </div>
    </div>
  </div>
</template>
