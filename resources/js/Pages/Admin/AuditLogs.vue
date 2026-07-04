<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import AppExportButton from '@/Components/Common/AppExportButton.vue'
import { formatDateTime } from '@/Utils/formatDate'
import { ref, toRaw } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Paginator from 'primevue/paginator'

defineOptions({ layout: AppLayout })

const props = defineProps({
  logs: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
  modules: { type: Array, default: () => [] },
  actions: { type: Array, default: () => [] },
})

const search = ref(props.filters.search || '')
const module = ref(props.filters.module || '')
const action = ref(props.filters.action || '')
const from = ref(props.filters.from || '')
const to = ref(props.filters.to || '')

const moduleOptions = [{ label: 'All Modules', value: '' }, ...props.modules.map(m => ({ label: m, value: m }))]
const actionOptions = [{ label: 'All Actions', value: '' }, ...props.actions.map(a => ({ label: a, value: a }))]

const route = window.route
const totalLogs = props.logs?.total ?? 0

function applyFilters() {
  router.get(route('admin.audit-logs'), {
    search: search.value,
    module: module.value,
    action: action.value,
    from: from.value,
    to: to.value,
  }, { replace: true })
}

function onPage(event) {
  router.get(route('admin.audit-logs'), {
    search: search.value,
    module: module.value,
    action: action.value,
    from: from.value,
    to: to.value,
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}

const roleSeverity = (role) => ({
  admin: 'danger',
  aics_staff: 'info',
  mswdo: 'success',
  accountant: 'warn',
  treasurer: 'contrast',
  mayors_office: 'info',
}[role] || 'info')

const moduleSeverity = (module) => ({
  auth: 'info',
  users: 'info',
  admin: 'warn',
  aics: 'success',
  mswdo: 'success',
  accountant: 'warn',
  treasurer: 'contrast',
  mayors_office: 'info',
  applications: 'info',
}[module] || 'info')

const actionSeverity = (action) => ({
  login: 'success',
  logout: 'contrast',
  aup_accepted: 'info',
  store: 'success',
  update: 'info',
  destroy: 'danger',
  'toggle-status': 'warn',
  'revoke-sessions': 'danger',
  index: 'info',
  show: 'info',
  export: 'warn',
  verify: 'info',
  accept: 'success',
}[action] || 'info')
</script>

<template>
  <Head title="Audit Logs" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Audit Logs</div>
          <AppExportButton :url="route('admin.audit-logs.export')" />
        </div>

        <div class="flex flex-wrap gap-4 mb-6">
          <div class="flex-1 min-w-48">
            <InputText v-model="search" placeholder="Search description, module, action, IP..." class="w-full"
              @keyup.enter="applyFilters" />
          </div>
          <div class="w-44">
            <Select v-model="module" :options="moduleOptions" option-label="label" option-value="value" placeholder="All Modules" class="w-full" @change="applyFilters" />
          </div>
          <div class="w-44">
            <Select v-model="action" :options="actionOptions" option-label="label" option-value="value" placeholder="All Actions" class="w-full" @change="applyFilters" />
          </div>
          <div class="flex items-center gap-2">
            <input v-model="from" type="date" @change="applyFilters"
              class="px-3 py-2 rounded-lg border border-surface-300 text-sm outline-none focus:ring-2 focus:ring-primary" />
            <span class="text-muted-color">—</span>
            <input v-model="to" type="date" @change="applyFilters"
              class="px-3 py-2 rounded-lg border border-surface-300 text-sm outline-none focus:ring-2 focus:ring-primary" />
          </div>
        </div>

        <DataTable :value="toRaw(props.logs.data)" striped-rows class="w-full">
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
          v-if="totalLogs > props.logs.per_page"
          :first="(props.logs.current_page - 1) * props.logs.per_page"
          :rows="props.logs.per_page"
          :total-records="totalLogs"
          @page="onPage"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
          class="mt-4"
        />
      </div>
    </div>
  </div>
</template>
