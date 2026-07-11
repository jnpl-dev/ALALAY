<script setup>
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Paginator from 'primevue/paginator'
import { ref, toRaw } from 'vue'
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

const props = defineProps({
  references: { type: Object, required: true },
  filters: { type: Object, default: () => ({}) },
})

const search = ref(props.filters.search || '')
const total = props.references?.total ?? 0

const route = window.route

function applyFilters() {
  router.get(route('admin.assistance-code-references.index'), {
    search: search.value,
  }, { replace: true })
}

function onPage(event) {
  router.get(route('admin.assistance-code-references.index'), {
    search: search.value,
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Code References" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Assistance Code References</div>
          <Button label="New Code Reference" icon="pi pi-plus" @click="router.get(route('admin.assistance-code-references.create'))" />
        </div>

        <div class="flex mb-6">
          <div class="w-72">
            <InputText v-model="search" placeholder="Search code types..." class="w-full" @keyup.enter="applyFilters" />
          </div>
        </div>

        <DataTable :value="toRaw(props.references.data)" striped-rows class="w-full">
          <Column field="code_type" header="Code Type" sortable />
          <Column field="default_amount" header="Default Amount" sortable>
            <template #body="{ data }">
              <span class="font-medium">{{ formatCurrency(data.default_amount) }}</span>
            </template>
          </Column>
          <Column field="description" header="Description">
            <template #body="{ data }">
              <span class="text-sm">{{ data.description || '—' }}</span>
            </template>
          </Column>
          <Column field="is_active" header="Status" sortable>
            <template #body="{ data }">
              <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'danger'" />
            </template>
          </Column>
          <Column header="Actions" style="width: 8rem">
            <template #body="{ data }">
              <div class="flex gap-2">
                <Button icon="pi pi-pencil" severity="info" text rounded size="small"
                  @click="router.get(route('admin.assistance-code-references.edit', data.id))" />
                <Button icon="pi pi-trash" severity="danger" text rounded size="small"
                  @click="router.delete(route('admin.assistance-code-references.destroy', data.id), { preserveState: true, preserveScroll: true })" />
              </div>
            </template>
          </Column>
        </DataTable>

        <Paginator
          v-if="total > props.references.per_page"
          :first="(props.references.current_page - 1) * props.references.per_page"
          :rows="props.references.per_page"
          :total-records="total"
          @page="onPage"
          template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink"
          class="mt-4"
        />
      </div>
    </div>
  </div>
</template>
