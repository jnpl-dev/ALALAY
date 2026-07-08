<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import AppStatusBadge from '@/Components/Common/AppStatusBadge.vue'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Button from 'primevue/button'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import InputText from 'primevue/inputtext'
import Tag from 'primevue/tag'
import Select from 'primevue/select'
import Paginator from 'primevue/paginator'
import { ref, toRaw, watch } from 'vue'
import { formatDate } from '@/Utils/formatDate'
import { formatCurrency } from '@/Utils/formatCurrency'

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

const tabIndex = ['pending', 'cheque_ready', 'hold'].indexOf(props.tab)
const searchQuery = ref(props.search ?? '')
const categoryFilter = ref(props.category ?? '')
let filterTimer = null

const categoryOptions = [{ label: 'All Categories', value: '' }, ...props.categories.map(c => ({ label: c, value: c }))]

function applyFilters() {
  router.get(route('treasurer.budget.index'), {
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
  const tabValues = ['pending', 'cheque_ready', 'hold']
  router.get(route('treasurer.budget.index'), { tab: tabValues[event.index], search: searchQuery.value || null, category: categoryFilter.value || null }, { replace: true })
}

function onPage(event) {
  router.get(route('treasurer.budget.index'), {
    tab: props.tab,
    search: searchQuery.value || null,
    category: categoryFilter.value || null,
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Treasurer - Budget" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div class="font-semibold text-xl">Budget Checking</div>
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
            <DataTable :value="toRaw(props.applications.data)" striped-rows class="w-full">
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
                  <Button icon="pi pi-eye" severity="info" text rounded size="small"
                    @click="router.get(route('treasurer.budget.show', data.id))" />
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

            <AppEmptyState v-if="!props.applications.data.length" icon="pi pi-inbox" message="No pending budget checks" />
          </TabPanel>

          <TabPanel header="Cheque Ready">
            <DataTable :value="toRaw(props.applications.data)" striped-rows class="w-full">
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
                  <Tag value="Cheque Ready" severity="success" />
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
                    @click="router.get(route('treasurer.budget.show', data.id))" />
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

            <AppEmptyState v-if="!props.applications.data.length" icon="pi pi-check-circle" message="No cheque ready items" />
          </TabPanel>

          <TabPanel header="On Hold">
            <DataTable :value="toRaw(props.applications.data)" striped-rows class="w-full">
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
                  <Tag value="On Hold" severity="warn" />
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
                    @click="router.get(route('treasurer.budget.show', data.id))" />
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

            <AppEmptyState v-if="!props.applications.data.length" icon="pi pi-pause-circle" message="No applications on hold" />
          </TabPanel>
        </TabView>
      </div>
    </div>
  </div>
</template>
