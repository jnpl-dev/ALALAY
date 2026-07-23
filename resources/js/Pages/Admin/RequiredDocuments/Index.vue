<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Paginator from 'primevue/paginator'
import Skeleton from 'primevue/skeleton'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, toRaw } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  documents: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
  categories: { type: Array, default: () => [] },
})

const toast = useToast()
const confirm = useConfirm()

const search = ref(props.filters.search || '')
const category_id = ref(props.filters.category_id || '')
const categoryOptions = [{ label: 'All Categories', value: '' }, ...props.categories.map(c => ({ label: c.category_name, value: c.id }))]

const route = window.route

function applyFilters() {
  router.get(route('admin.required-documents.index'), {
    search: search.value,
    category_id: category_id.value,
  }, { replace: true })
}

function onPage(event) {
  router.get(route('admin.required-documents.index'), {
    search: search.value,
    category_id: category_id.value,
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}

function confirmDelete(data) {
  confirm.destroy('Confirm Delete', `Delete document "${data.doc_name}"? This cannot be undone.`, () => {
    router.delete(route('admin.required-documents.destroy', data.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Document deleted'),
      onError: () => toast.error('Delete failed'),
    })
  })
}
</script>

<template>
  <Head title="Required Documents" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Required Documents</div>
          <Button label="New Document" icon="pi pi-plus" @click="router.get(route('admin.required-documents.create'))" />
        </div>

        <div class="flex flex-wrap gap-4 mb-6">
          <div class="w-72">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search documents..." class="w-full" @keyup.enter="applyFilters" />
            </IconField>
          </div>
          <div class="w-56">
            <Select v-model="category_id" :options="categoryOptions" option-label="label" option-value="value" placeholder="All Categories" class="w-full" @change="applyFilters" />
          </div>
        </div>

        <Deferred data="documents">
          <DataTable :value="toRaw(documents?.data ?? [])" striped-rows class="w-full">
            <Column field="doc_name" header="Name" sortable />
            <Column field="category_name" header="Category" sortable />
            <Column field="doc_description" header="Description">
              <template #body="{ data }">
                <span class="text-sm">{{ data.doc_description || '—' }}</span>
              </template>
            </Column>
            <Column field="is_mandatory" header="Mandatory" sortable>
              <template #body="{ data }">
                <Tag :value="data.is_mandatory ? 'Yes' : 'No'" :severity="data.is_mandatory ? 'info' : 'contrast'" />
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
                  <Button icon="pi pi-pencil" severity="info" text rounded size="small" v-tooltip="'Edit'"
                    @click="router.get(route('admin.required-documents.edit', data.id))" />
                  <Button icon="pi pi-trash" severity="danger" text rounded size="small" v-tooltip="'Delete'"
                    @click="confirmDelete(data)" />
                </div>
              </template>
            </Column>
          </DataTable>
          <template #empty>
            <div class="text-center py-8 text-muted-color">No documents found</div>
          </template>

          <Paginator
            v-if="(documents?.total ?? 0) > (documents?.per_page ?? 10)"
            :first="((documents?.current_page ?? 1) - 1) * (documents?.per_page ?? 10)"
            :rows="documents?.per_page ?? 10"
            :total-records="documents?.total ?? 0"
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
