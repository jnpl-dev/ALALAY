<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import AppExportButton from '@/Components/Common/AppExportButton.vue'
import { formatDateTime } from '@/Utils/formatDate'
import { roleSeverity, moduleSeverity, actionSeverity } from '@/Utils/severityMappings'
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

useBreadcrumb([{ label: 'Admin' }, { label: 'Audit Logs' }])

const props = defineProps({
  logs: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  modules: { type: Array, default: () => [] },
  actions: { type: Array, default: () => [] },
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
const module = ref(props.filters.module || '')
const action = ref(props.filters.action || '')
const from = ref(parseDate(props.filters.from))
const to = ref(parseDate(props.filters.to))

const moduleOptions = [{ label: 'All Modules', value: '' }, ...props.modules.map(m => ({ label: m, value: m }))]
const actionOptions = [{ label: 'All Actions', value: '' }, ...props.actions.map(a => ({ label: a, value: a }))]

const route = window.route

const exportParams = computed(() => ({
  search: search.value,
  module: module.value,
  action: action.value,
  from: formatDateParam(from.value),
  to: formatDateParam(to.value),
}))

watch([from, to], applyFilters)

function applyFilters() {
  router.get(route('admin.audit-logs'), {
    search: search.value,
    module: module.value,
    action: action.value,
    from: formatDateParam(from.value),
    to: formatDateParam(to.value),
  }, { replace: true })
}

function onPage(event) {
  router.get(route('admin.audit-logs'), {
    search: search.value,
    module: module.value,
    action: action.value,
    from: formatDateParam(from.value),
    to: formatDateParam(to.value),
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Audit Logs" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Audit Logs</div>
          <AppExportButton
            :url="route('admin.audit-logs.export')"
            :params="exportParams"
          />
        </div>

        <div class="flex flex-wrap gap-4 mb-6">
          <div class="flex-1 min-w-48">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search description, module, action, IP..." class="w-full"
                @keyup.enter="applyFilters" />
            </IconField>
          </div>
          <div class="w-44">
            <Select v-model="module" :options="moduleOptions" option-label="label" option-value="value" placeholder="All Modules" class="w-full" @change="applyFilters" />
          </div>
          <div class="w-44">
            <Select v-model="action" :options="actionOptions" option-label="label" option-value="value" placeholder="All Actions" class="w-full" @change="applyFilters" />
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
            <Column field="user_name" header="User" sortable />
            <Column field="role_label" header="Role" sortable>
              <template #body="{ data }">
                <Tag :value="data.role_label" :severity="roleSeverity(data.role)" />
              </template>
            </Column>
            <Column field="module_label" header="Module" sortable>
              <template #body="{ data }">
                <Tag :value="data.module_label" :severity="moduleSeverity(data.module)" />
              </template>
            </Column>
            <Column field="action_label" header="Action" sortable>
              <template #body="{ data }">
                <Tag :value="data.action_label" :severity="actionSeverity(data.action)" />
              </template>
            </Column>
            <Column field="description" header="Description" style="min-width: 12rem" />
            <Column field="entity_type" header="Entity" sortable />
            <Column field="ip_address" header="IP" sortable />
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
