<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import AppExportButton from '@/Components/Common/AppExportButton.vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import DatePicker from 'primevue/datepicker'
import Paginator from 'primevue/paginator'
import Skeleton from 'primevue/skeleton'
import { ref, toRaw, watch, computed } from 'vue'
import { usePolling } from '@/Composables/usePolling'
import { formatDate } from '@/Utils/formatDate'
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

const props = defineProps({
  applications: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  tab: { type: String, default: 'to_create' },
  categories: { type: Array, default: () => [] },
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

const total = props.applications?.total ?? 0
const route = window.route

let filterTimer = null

const tabIndex = ['to_create', 'completed'].indexOf(props.tab)
const search = ref(props.filters.search || '')
const category = ref(props.filters.category || '')
const from = ref(parseDate(props.filters.from))
const to = ref(parseDate(props.filters.to))

const categoryOptions = [{ label: 'All Categories', value: '' }, ...props.categories.map(c => ({ label: c, value: c }))]

const tableData = ref([])

watch(() => props.applications, (val) => {
  tableData.value = val?.data ? [...toRaw(val.data)] : []
}, { deep: true })

const exportParams = computed(() => ({
  tab: props.tab,
  search: search.value || null,
  category: category.value || null,
  from: formatDateParam(from.value),
  to: formatDateParam(to.value),
}))

const pollParams = computed(() => ({
  tab: props.tab,
  search: props.filters.search || null,
  category: props.filters.category || null,
}))

usePolling(
  route('mswdo.vouchers.poll'),
  pollParams,
  (data) => {
    if (data.data) tableData.value = data.data
  },
)

watch([from, to], applyFilters)

watch(search, () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(applyFilters, 300)
})

watch(category, applyFilters)

function applyFilters() {
  router.get(route('mswdo.vouchers.index'), {
    tab: props.tab,
    search: search.value || null,
    category: category.value || null,
    from: formatDateParam(from.value),
    to: formatDateParam(to.value),
    page: 1,
  }, { replace: true })
}

function onTabChange(event) {
  const tabValues = ['to_create', 'completed']
  router.get(route('mswdo.vouchers.index'), {
    tab: tabValues[event.index],
    search: search.value || null,
    category: category.value || null,
    from: formatDateParam(from.value),
    to: formatDateParam(to.value),
  }, { replace: true })
}

function onPage(event) {
  router.get(route('mswdo.vouchers.index'), {
    tab: props.tab,
    search: props.filters.search || null,
    category: props.filters.category || null,
    from: formatDateParam(from.value),
    to: formatDateParam(to.value),
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="MSWDO - Vouchers" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Vouchers</div>
          <AppExportButton
            :url="route('mswdo.vouchers.export')"
            :params="exportParams"
          />
        </div>

        <div class="flex flex-wrap gap-4 mb-6">
          <div class="flex-1 min-w-48">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search reference code, name, category..." class="w-full"
                @keyup.enter="applyFilters" />
            </IconField>
          </div>
          <div class="w-44">
            <Select v-model="category" :options="categoryOptions" option-label="label" option-value="value" placeholder="All Categories" class="w-full" @change="applyFilters" />
          </div>
          <div class="flex items-center gap-2">
            <DatePicker v-model="from" dateFormat="yy-mm-dd" placeholder="From" :showIcon="true" showClear />
            <span class="text-muted-color">—</span>
            <DatePicker v-model="to" dateFormat="yy-mm-dd" placeholder="To" :showIcon="true" showClear />
          </div>
        </div>

        <TabView :activeIndex="tabIndex" @tab-change="onTabChange">
          <TabPanel header="To Create">
            <Deferred data="applications">
              <DataTable :value="toRaw(tableData)" striped-rows class="w-full">
                <Column field="reference_code" header="Reference" sortable />
                <Column field="claimant_name" header="Claimant" sortable />
                <Column field="category_name" header="Category" sortable />
                <Column field="code_type" header="Code" sortable />
                <Column field="amount" header="Amount" sortable>
                  <template #body="{ data }">
                    {{ data.amount ? formatCurrency(data.amount) : '—' }}
                  </template>
                </Column>
                <Column field="status" header="Status" sortable>
                  <template #body="{ data }">
                    <AppStatusBadge :status="data.status" />
                  </template>
                </Column>
                <Column field="created_at" header="Submitted" sortable>
                  <template #body="{ data }">
                    {{ formatDate(data.created_at) }}
                  </template>
                </Column>
                <Column header="Actions" style="width: 6rem">
                  <template #body="{ data }">
                    <Button icon="pi pi-receipt" severity="info" text rounded size="small" v-tooltip="'Create voucher'"
                      @click="router.get(route('mswdo.vouchers.show', data.id))" />
                  </template>
                </Column>
                <template #empty>
                  <AppEmptyState icon="pi pi-inbox" message="No applications pending voucher creation" />
                </template>
              </DataTable>

              <Paginator
                v-if="total > (props.applications?.per_page ?? 10)"
                :first="((props.applications?.current_page ?? 1) - 1) * (props.applications?.per_page ?? 10)"
                :rows="props.applications?.per_page ?? 10"
                :total-records="total"
                @page="onPage"
                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                class="mt-4"
              />

              <template #fallback>
                <div class="flex flex-col gap-2">
                  <Skeleton v-for="i in 5" :key="i" height="3rem" />
                </div>
              </template>
            </Deferred>
          </TabPanel>

          <TabPanel header="Created">
            <Deferred data="applications">
              <DataTable :value="toRaw(tableData)" striped-rows class="w-full">
                <Column field="reference_code" header="Reference" sortable />
                <Column field="claimant_name" header="Claimant" sortable />
                <Column field="category_name" header="Category" sortable />
                <Column field="code_type" header="Code" sortable />
                <Column field="amount" header="Amount" sortable>
                  <template #body="{ data }">
                    {{ data.amount ? formatCurrency(data.amount) : '—' }}
                  </template>
                </Column>
                <Column field="status" header="Status" sortable>
                  <template #body="{ data }">
                    <AppStatusBadge :status="data.status" />
                  </template>
                </Column>
                <Column header="Actions" style="width: 6rem">
                  <template #body="{ data }">
                    <Button icon="pi pi-eye" severity="info" text rounded size="small" v-tooltip="'View voucher'"
                      @click="router.get(route('mswdo.vouchers.show', data.id))" />
                  </template>
                </Column>
                <template #empty>
                  <AppEmptyState icon="pi pi-check-circle" message="No completed vouchers" />
                </template>
              </DataTable>

              <Paginator
                v-if="total > (props.applications?.per_page ?? 10)"
                :first="((props.applications?.current_page ?? 1) - 1) * (props.applications?.per_page ?? 10)"
                :rows="props.applications?.per_page ?? 10"
                :total-records="total"
                @page="onPage"
                template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
                class="mt-4"
              />

              <template #fallback>
                <div class="flex flex-col gap-2">
                  <Skeleton v-for="i in 5" :key="i" height="3rem" />
                </div>
              </template>
            </Deferred>
          </TabPanel>
        </TabView>
      </div>
    </div>
  </div>
</template>
