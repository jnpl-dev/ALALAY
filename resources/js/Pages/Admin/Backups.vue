<script setup>
import { Head, router, useForm, usePage, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { formatDateTime } from '@/Utils/formatDate'
import { ref, toRaw, watch, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Paginator from 'primevue/paginator'
import Skeleton from 'primevue/skeleton'
import Dialog from 'primevue/dialog'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'
import { useConfirm } from '@/Composables/useConfirm'
import { useToast } from '@/Composables/useToast'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Admin' }, { label: 'Backup & Restore' }])

const props = defineProps({
  files: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

const route = window.route
const confirm = useConfirm()
const toast = useToast()
const page = usePage()

const runForm = useForm({ destination: 'both' })
const pendingFile = ref(null)

const showDestinationDialog = ref(false)

const destinationOptions = [
  { label: 'Both (local + offsite)', value: 'both' },
  { label: 'Local only', value: 'local' },
  { label: 'Offsite only (Supabase)', value: 'offsite' },
]

const destinationHint = computed(() => ({
  both: 'Saved to storage/app/backups and uploaded to Supabase offsite storage.',
  local: 'Saved to storage/app/backups only. No offsite copy is made.',
  offsite: 'Uploaded to Supabase; the local copy is removed after a successful upload. Kept locally if the upload fails.',
}[runForm.destination] || 'Choose where this backup should be saved.'))

/**
 * Toast the server's backup_result flash (set by BackupController, ignored
 * by the global AppLayout watcher → exactly one toast per action).
 */
function toastBackupResult(okSummary, errorSummary) {
  const result = page.props.flash?.backup_result
  if (!result?.message) return
  if (result.status === 'success') {
    toast.success(okSummary, result.message)
  } else {
    toast.error(errorSummary, result.message)
  }
}

function toastValidationErrors(summary, errors) {
  const first = errors && typeof errors === 'object' ? Object.values(errors)[0] : null
  toast.error(summary, Array.isArray(first) ? first[0] : (first || 'Request could not be completed.'))
}

function parseDate(str) {
  if (!str) return null
  const [y, m, d] = String(str).split('-')
  return new Date(parseInt(y), parseInt(m) - 1, parseInt(d))
}

function formatDateParam(date) {
  if (!date) return null
  const d = new Date(date)
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

function formatBytes(bytes) {
  if (bytes === null || bytes === undefined) return '—'
  if (bytes < 1024) return `${bytes} B`
  const units = ['KB', 'MB', 'GB', 'TB']
  let value = bytes
  let i = -1
  do {
    value /= 1024
    i++
  } while (value >= 1024 && i < units.length - 1)
  return `${value.toFixed(value < 10 ? 1 : 0)} ${units[i]}`
}

function locationSeverity(location) {
  if (location === 'both') return 'success'
  if (location === 'offsite') return 'info'
  return 'warn'
}

function locationLabel(location) {
  if (location === 'both') return 'Both'
  if (location === 'offsite') return 'Offsite'
  return 'Local'
}

const search = ref(props.filters.search || '')
const from = ref(parseDate(props.filters.from))
const to = ref(parseDate(props.filters.to))
const location = ref(props.filters.location || '')

const locationOptions = [
  { label: 'All Locations', value: '' },
  { label: 'Local', value: 'local' },
  { label: 'Offsite', value: 'offsite' },
  { label: 'Both', value: 'both' },
]

watch([from, to], applyFilters)

function filterParams() {
  return {
    search: search.value,
    from: formatDateParam(from.value),
    to: formatDateParam(to.value),
    location: location.value,
  }
}

function applyFilters() {
  router.get(route('admin.backups.index'), filterParams(), { replace: true })
}

function onPage(event) {
  router.get(route('admin.backups.index'), {
    ...filterParams(),
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}

function runBackupNow() {
  confirm.require({
    message: 'Run a database backup now? The dump is compressed and encrypted. You will choose where to save it in the next step. This may take a few seconds.',
    header: 'Confirm Backup',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancel',
    acceptLabel: 'Continue',
    rejectClass: 'p-button-outlined',
    acceptClass: 'p-button-success',
    accept: () => {
      showDestinationDialog.value = true
    },
  })
}

function submitBackup() {
  runForm.post(route('admin.backups.run'), {
    preserveScroll: true,
    onSuccess: () => {
      showDestinationDialog.value = false
      toastBackupResult('Backup complete', 'Backup failed')
    },
    onError: (errors) => {
      showDestinationDialog.value = false
      toastValidationErrors('Backup failed', errors)
    },
    onFinish: () => {
      runForm.destination = 'both'
    },
  })
}

function confirmRestore(file) {
  confirm.require({
    message: `Restore the database from "${file}"? A safety snapshot is created first so you can roll back. All users will be logged out and the system will be in maintenance mode while the restore runs.`,
    header: 'Confirm Restore',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancel',
    acceptLabel: 'Restore',
    rejectClass: 'p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: () => {
      pendingFile.value = file
      router.post(route('admin.backups.restore'), { file }, {
        preserveScroll: true,
        onSuccess: () => toastBackupResult('Restore complete', 'Restore failed'),
        onError: (errors) => toastValidationErrors('Restore failed', errors),
        onFinish: () => { pendingFile.value = null },
      })
    },
  })
}

function confirmDelete(file) {
  confirm.destroy('Confirm Delete', `Delete backup "${file}" from local and offsite storage? This cannot be undone.`, () => {
    pendingFile.value = file
    router.delete(route('admin.backups.destroy', file), {
      preserveScroll: true,
      onSuccess: () => toastBackupResult('Backup deleted', 'Delete failed'),
      onError: (errors) => toastValidationErrors('Delete failed', errors),
      onFinish: () => { pendingFile.value = null },
    })
  })
}
</script>

<template>
  <Head title="Backup & Restore" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Backup & Restore</div>
          <Button
            label="Backup Now"
            icon="pi pi-download"
            :loading="runForm.processing"
            @click="runBackupNow"
          />
        </div>

        <div class="flex flex-wrap gap-4 mb-6">
          <div class="flex-1 min-w-48">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search filename..." class="w-full"
                @keyup.enter="applyFilters" />
            </IconField>
          </div>
          <div class="w-44">
            <Select v-model="location" :options="locationOptions" option-label="label" option-value="value" placeholder="All Locations" class="w-full" @change="applyFilters" />
          </div>
          <div class="flex items-center gap-2">
            <DatePicker v-model="from" dateFormat="yy-mm-dd" placeholder="From" :showIcon="true" showClear />
            <span class="text-muted-color">—</span>
            <DatePicker v-model="to" dateFormat="yy-mm-dd" placeholder="To" :showIcon="true" showClear />
          </div>
        </div>

        <Deferred data="files">
          <DataTable :value="toRaw(files?.data ?? [])" striped-rows class="w-full">
            <Column field="file" header="File" style="min-width: 20rem">
              <template #body="{ data }">
                <span class="break-all text-sm">{{ data.file }}</span>
              </template>
            </Column>
            <Column field="size" header="Size">
              <template #body="{ data }">
                <span class="text-sm whitespace-nowrap">{{ formatBytes(data.size) }}</span>
              </template>
            </Column>
            <Column field="created_at" header="Created" sortable>
              <template #body="{ data }">
                <span class="text-sm whitespace-nowrap">{{ formatDateTime(data.created_at) }}</span>
              </template>
            </Column>
            <Column field="location" header="Location">
              <template #body="{ data }">
                <Tag :value="locationLabel(data.location)" :severity="locationSeverity(data.location)" />
              </template>
            </Column>
            <Column header="Actions" style="width: 8rem">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button
                    icon="pi pi-undo"
                    severity="warn"
                    text
                    rounded
                    size="small"
                    v-tooltip="'Restore'"
                    :loading="pendingFile === data.file"
                    :disabled="pendingFile !== null && pendingFile !== data.file"
                    @click="confirmRestore(data.file)"
                  />
                  <Button
                    icon="pi pi-trash"
                    severity="danger"
                    text
                    rounded
                    size="small"
                    v-tooltip="'Delete'"
                    :loading="pendingFile === data.file"
                    :disabled="pendingFile !== null && pendingFile !== data.file"
                    @click="confirmDelete(data.file)"
                  />
                </div>
              </template>
            </Column>
          </DataTable>

          <Paginator
            v-if="(files?.total ?? 0) > (files?.per_page ?? 20)"
            :first="((files?.current_page ?? 1) - 1) * (files?.per_page ?? 20)"
            :rows="files?.per_page ?? 20"
            :total-records="files?.total ?? 0"
            @page="onPage"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
            class="mt-4"
          />

          <template #fallback>
            <div class="space-y-3">
              <Skeleton v-for="i in 5" :key="i" width="100%" height="3.5rem" />
            </div>
          </template>
        </Deferred>
      </div>
    </div>

    <Dialog
      v-model:visible="showDestinationDialog"
      header="Backup Destination"
      modal
      :closable="!runForm.processing"
      :mask-closable="!runForm.processing"
      :dismissable-mask="!runForm.processing"
      class="w-full max-w-md"
    >
      <div class="flex flex-col gap-4 py-2">
        <p class="text-sm text-muted-color">
          Choose where this backup should be saved.
        </p>
        <Select
          v-model="runForm.destination"
          :options="destinationOptions"
          option-label="label"
          option-value="value"
          placeholder="Destination"
          class="w-full"
          :disabled="runForm.processing"
        />
        <p class="text-xs text-muted-color leading-relaxed">
          {{ destinationHint }}
        </p>
      </div>

      <template #footer>
        <Button
          label="Cancel"
          text
          severity="secondary"
          :disabled="runForm.processing"
          @click="showDestinationDialog = false"
        />
        <Button
          label="Start Backup"
          icon="pi pi-play"
          :loading="runForm.processing"
          @click="submitBackup"
        />
      </template>
    </Dialog>
  </div>
</template>
