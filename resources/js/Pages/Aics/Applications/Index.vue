<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import Tag from 'primevue/tag'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import { ref, toRaw, watch, computed } from 'vue'
import { usePolling } from '@/Composables/usePolling'
import { formatDate } from '@/Utils/formatDate'

const categorySeverity = (name) => {
  const map = { medical: 'info', hospital: 'warn', burial: 'danger' }
  return map[name?.toLowerCase()] || 'info'
}

const typeSeverity = (type) => type === 'online' ? 'info' : 'success'

defineOptions({ layout: AppLayout })

const props = defineProps({
  applications: { type: Object, required: true },
  tab: { type: String, default: 'pending' },
  search: { type: String, default: '' },
  category: { type: String, default: '' },
  categories: { type: Array, default: () => [] },
})

const total = props.applications?.total ?? 0
const route = window.route

const tabIndex = ['pending', 'screening', 'returned'].indexOf(props.tab)
const searchQuery = ref(props.search ?? '')
const categoryFilter = ref(props.category ?? '')
let filterTimer = null

const categoryOptions = [{ label: 'All Categories', value: '' }, ...props.categories.map(c => ({ label: c, value: c }))]

const tableData = ref([...toRaw(props.applications.data)])

watch(() => props.applications, (val) => {
  tableData.value = val?.data ? [...toRaw(val.data)] : []
}, { deep: true })

const pollParams = computed(() => ({
  tab: props.tab,
  search: props.search || null,
  category: props.category || null,
}))

usePolling(
  route('aics.applications.poll'),
  pollParams,
  (data) => {
    if (data.data) tableData.value = data.data
  },
)

function applyFilters() {
  router.get(route('aics.applications.index'), {
    tab: props.tab,
    search: searchQuery.value || null,
    category: categoryFilter.value || null,
    page: 1,
  }, { preserveState: true, replace: true })
}

watch(searchQuery, () => {
  clearTimeout(filterTimer)
  filterTimer = setTimeout(applyFilters, 300)
})

watch(categoryFilter, applyFilters)

function onTabChange(event) {
  const tabValues = ['pending', 'screening', 'returned']
  router.get(route('aics.applications.index'), { tab: tabValues[event.index], search: searchQuery.value || null, category: categoryFilter.value || null }, { replace: true })
}

function onPage(event) {
  router.get(route('aics.applications.index'), {
    tab: props.tab,
    search: searchQuery.value || null,
    category: categoryFilter.value || null,
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Applications" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div class="font-semibold text-xl">Applications</div>
          <div class="flex items-center gap-2">
            <Select v-model="categoryFilter" :options="categoryOptions" optionLabel="label" optionValue="value" placeholder="All Categories" class="w-48" />
            <IconField iconPosition="left">
              <InputIcon>
                <i class="pi pi-search" />
              </InputIcon>
              <InputText v-model="searchQuery" placeholder="Search" class="w-56" />
            </IconField>
          </div>
        </div>

        <TabView :activeIndex="tabIndex" @tab-change="onTabChange">
          <TabPanel header="Pending">
            <DataTable :value="toRaw(tableData)" striped-rows class="w-full">
              <Column field="reference_code" header="Reference" sortable />
              <Column field="claimant_name" header="Claimant" sortable />
              <Column field="category_name" header="Category" sortable>
                <template #body="{ data }">
                  <Tag :value="data.category_name" :severity="categorySeverity(data.category_name)" />
                </template>
              </Column>
              <Column field="submission_type" header="Type" sortable>
                <template #body="{ data }">
                  <Tag :value="data.submission_type" :severity="typeSeverity(data.submission_type)" />
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
                  <Button icon="pi pi-eye" severity="info" text rounded size="small"
                    @click="router.get(route('aics.applications.show', data.id))" />
                </template>
              </Column>
            </DataTable>

            <Paginator
              v-if="total > props.applications.per_page"
              :first="(props.applications.current_page - 1) * props.applications.per_page"
              :rows="props.applications.per_page"
              :total-records="total"
              @page="onPage"
              template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
              class="mt-4"
            />

            <AppEmptyState v-if="!props.applications.data.length" icon="pi pi-inbox" message="No pending applications" />
          </TabPanel>

          <TabPanel header="Screened">
            <DataTable :value="toRaw(tableData)" striped-rows class="w-full">
              <Column field="reference_code" header="Reference" sortable />
              <Column field="claimant_name" header="Claimant" sortable />
              <Column field="category_name" header="Category" sortable>
                <template #body="{ data }">
                  <Tag :value="data.category_name" :severity="categorySeverity(data.category_name)" />
                </template>
              </Column>
              <Column field="status" header="Status" sortable>
                <template #body="{ data }">
                  <Tag value="Screened" severity="info" />
                </template>
              </Column>
              <Column field="created_at" header="Submitted" sortable>
                <template #body="{ data }">
                  {{ formatDate(data.created_at) }}
                </template>
              </Column>
              <Column header="Actions" style="width: 6rem">
                <template #body="{ data }">
                  <Button icon="pi pi-eye" severity="info" text rounded size="small"
                    @click="router.get(route('aics.applications.show', data.id))" />
                </template>
              </Column>
            </DataTable>

            <Paginator
              v-if="total > props.applications.per_page"
              :first="(props.applications.current_page - 1) * props.applications.per_page"
              :rows="props.applications.per_page"
              :total-records="total"
              @page="onPage"
              template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
              class="mt-4"
            />

            <AppEmptyState v-if="!props.applications.data.length" icon="pi pi-inbox" message="No applications with MSWDO" />
          </TabPanel>

          <TabPanel header="Returned">
            <DataTable :value="toRaw(tableData)" striped-rows class="w-full">
              <Column field="reference_code" header="Reference" sortable />
              <Column field="claimant_name" header="Claimant" sortable />
              <Column field="category_name" header="Category" sortable>
                <template #body="{ data }">
                  <Tag :value="data.category_name" :severity="categorySeverity(data.category_name)" />
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
                  <Button icon="pi pi-eye" severity="info" text rounded size="small"
                    @click="router.get(route('aics.applications.show', data.id))" />
                </template>
              </Column>
            </DataTable>

            <Paginator
              v-if="total > props.applications.per_page"
              :first="(props.applications.current_page - 1) * props.applications.per_page"
              :rows="props.applications.per_page"
              :total-records="total"
              @page="onPage"
              template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
              class="mt-4"
            />

            <AppEmptyState v-if="!props.applications.data.length" icon="pi pi-undo" message="No returned applications" />
          </TabPanel>
        </TabView>
      </div>
    </div>
  </div>
</template>
