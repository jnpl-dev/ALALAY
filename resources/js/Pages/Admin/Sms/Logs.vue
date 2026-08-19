<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import AppExportButton from '@/Components/Common/AppExportButton.vue'
import { formatDateTime } from '@/Utils/formatDate'
import { smsStatusSeverity, smsEventSeverity } from '@/Utils/severityMappings'
import { ref, toRaw, watch, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import DatePicker from 'primevue/datepicker'
import Paginator from 'primevue/paginator'
import Skeleton from 'primevue/skeleton'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Admin' }, { label: 'SMS Notification' }, { label: 'Logs' }])

const props = defineProps({
  logs: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  events: { type: Array, default: () => [] },
  statuses: { type: Array, default: () => [] },
})

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

const search = ref(props.filters.search || '')
const event = ref(props.filters.event || '')
const status = ref(props.filters.status || '')
const from = ref(parseDate(props.filters.from))
const to = ref(parseDate(props.filters.to))

const eventOptions = [{ label: 'All Events', value: '' }, ...props.events.map(e => ({ label: e, value: e }))]
const statusOptions = [{ label: 'All Statuses', value: '' }, ...props.statuses.map(s => ({ label: s.charAt(0).toUpperCase() + s.slice(1), value: s }))]

const route = window.route

const exportParams = computed(() => ({
  search: search.value,
  event: event.value,
  status: status.value,
  from: formatDateParam(from.value),
  to: formatDateParam(to.value),
}))

watch([from, to], applyFilters)

function applyFilters() {
  router.get(route('admin.sms.logs'), {
    search: search.value,
    event: event.value,
    status: status.value,
    from: formatDateParam(from.value),
    to: formatDateParam(to.value),
  }, { replace: true })
}

function onPage(pageEvent) {
  router.get(route('admin.sms.logs'), {
    search: search.value,
    event: event.value,
    status: status.value,
    from: formatDateParam(from.value),
    to: formatDateParam(to.value),
    page: pageEvent.page + 1,
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="SMS Logs" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">SMS Logs</div>
          <AppExportButton
            :url="route('admin.sms.logs.export')"
            :params="exportParams"
          />
        </div>

        <div class="flex flex-wrap gap-4 mb-6">
          <div class="flex-1 min-w-48">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search phone, message, reference..." class="w-full"
                @keyup.enter="applyFilters" />
            </IconField>
          </div>
          <div class="w-44">
            <Select v-model="event" :options="eventOptions" option-label="label" option-value="value" placeholder="All Events" class="w-full" @change="applyFilters" />
          </div>
          <div class="w-44">
            <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" placeholder="All Statuses" class="w-full" @change="applyFilters" />
          </div>
          <div class="flex items-center gap-2">
            <DatePicker v-model="from" dateFormat="yy-mm-dd" placeholder="From" :showIcon="true" showClear />
            <span class="text-muted-color">—</span>
            <DatePicker v-model="to" dateFormat="yy-mm-dd" placeholder="To" :showIcon="true" showClear />
          </div>
        </div>

        <Deferred data="logs">
          <DataTable :value="toRaw(logs?.data ?? [])" striped-rows class="w-full">
            <Column field="created_at" header="Date" sortable>
              <template #body="{ data }">
                <span class="text-sm whitespace-nowrap">{{ formatDateTime(data.created_at) }}</span>
              </template>
            </Column>
            <Column field="reference_code" header="Reference" sortable>
              <template #body="{ data }">
                <span v-if="data.reference_code !== '—'" class="font-medium">{{ data.reference_code }}</span>
                <span v-else class="text-muted-color">—</span>
              </template>
            </Column>
            <Column field="recipient_phone" header="Recipient" sortable />
            <Column field="event_label" header="Event" sortable>
              <template #body="{ data }">
                <Tag :value="data.event_label" :severity="smsEventSeverity(data.trigger_event)" />
              </template>
            </Column>
            <Column field="status_label" header="Status" sortable>
              <template #body="{ data }">
                <Tag :value="data.status_label" :severity="smsStatusSeverity(data.status)" />
              </template>
            </Column>
            <Column field="message_body" header="Message" style="min-width: 14rem">
              <template #body="{ data }">
                <span class="text-sm">{{ data.message_body }}</span>
              </template>
            </Column>
            <Column field="provider_response" header="Provider" sortable>
              <template #body="{ data }">
                <span v-if="data.provider_response" class="text-sm text-muted-color whitespace-nowrap">{{ data.provider_response.driver ? 'log' : data.provider_response.status ?? 'failed' }}</span>
                <span v-else class="text-muted-color">—</span>
              </template>
            </Column>
          </DataTable>

          <Paginator
            v-if="(logs?.total ?? 0) > (logs?.per_page ?? 20)"
            :first="((logs?.current_page ?? 1) - 1) * (logs?.per_page ?? 20)"
            :rows="logs?.per_page ?? 20"
            :total-records="logs?.total ?? 0"
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
  </div>
</template>