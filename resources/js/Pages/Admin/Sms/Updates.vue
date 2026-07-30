<script setup>
import { ref, computed, watch } from 'vue'
import { Head, useForm, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import Textarea from 'primevue/textarea'
import Skeleton from 'primevue/skeleton'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Admin' }, { label: 'Settings' }, { label: 'SMS Updates' }])

const toast = useToast()

const route = window.route
const isEditing = ref(false)

const props = defineProps({
  templates: { type: Array, default: () => [] },
})

const form = useForm({ templates: [] })

let initialValues = {}

const templateLabels = {
  sms_template_submission_complete: 'Submission',
  sms_template_under_review: 'Under Review',
  sms_template_resubmission_needed: 'Need for Resubmission',
  sms_template_cheque_ready: 'Cheque Ready',
}

const templateDescriptions = {
  sms_template_submission_complete: 'Sent when a new application is submitted.',
  sms_template_under_review: 'Sent when the application is forwarded for review.',
  sms_template_resubmission_needed: 'Sent when the application is returned for changes.',
  sms_template_cheque_ready: 'Sent when the treasurer marks the cheque ready.',
}

const availablePlaceholders = computed(() => {
  return '{reference_code}, {claimant_name}, {track_url}'
})

watch(() => props.templates, (data) => {
  if (data.length) {
    const values = data.map(t => ({ ...t }))
    initialValues = JSON.parse(JSON.stringify(values))
    form.templates = values
  }
}, { immediate: true })

const isDirty = computed(() => {
  return JSON.stringify(form.templates) !== JSON.stringify(initialValues)
})

function submit() {
  form.post(route('admin.sms.updates'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false
      toast.success('Saved', 'SMS templates updated successfully.')
    },
  })
}

function cancelEdit() {
  form.templates = JSON.parse(JSON.stringify(initialValues))
  form.clearErrors()
  isEditing.value = false
}

function handleButtonClick() {
  if (!isEditing.value) {
    isEditing.value = true
  } else {
    submit()
  }
}
</script>

<template>
  <Head title="SMS — Updates" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Updates</div>
          <div class="flex gap-2">
            <Button
              v-if="isEditing"
              type="button"
              label="Cancel"
              icon="pi pi-times"
              severity="secondary"
              @click="cancelEdit"
            />
            <Button
              :label="isEditing ? 'Save Templates' : 'Edit Templates'"
              :icon="isEditing ? 'pi pi-check' : 'pi pi-pencil'"
              :loading="form.processing"
              @click="handleButtonClick"
            />
          </div>
        </div>

        <p class="text-sm text-muted-color mb-4">These SMS messages are sent automatically when the corresponding workflow event occurs. Available placeholders: <code class="bg-surface-100 px-1 rounded text-emerald-600">{{ availablePlaceholders }}</code></p>

        <Deferred data="templates">
          <form @submit.prevent="handleButtonClick">
            <div v-if="form.templates.length" class="flex flex-col gap-6">
              <div v-for="(template, i) in form.templates" :key="template.key" class="p-4 border border-surface rounded-lg">
                <label class="block font-medium mb-1">{{ templateLabels[template.key] || template.key }}</label>
                <p class="text-xs text-muted-color mb-3">{{ templateDescriptions[template.key] }}</p>
                <Textarea
                  v-model="template.value"
                  class="w-full"
                  :disabled="!isEditing"
                  rows="3"
                  autoResize
                />
              </div>
            </div>

            <div v-else class="py-8 text-center text-muted-color">
              <i class="pi pi-envelope text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
              <p>No SMS templates found.</p>
            </div>
          </form>

          <template #fallback>
            <div class="space-y-6">
              <div v-for="i in 4" :key="i" class="p-4 border border-surface rounded-lg">
                <Skeleton width="40%" height="1.25rem" class="mb-2" />
                <Skeleton width="60%" height="0.75rem" class="mb-3" />
                <Skeleton width="100%" height="4rem" />
              </div>
            </div>
          </template>
        </Deferred>
      </div>
    </div>
  </div>
</template>
