<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import Tag from 'primevue/tag'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import Paginator from 'primevue/paginator'
import Skeleton from 'primevue/skeleton'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { ref, toRaw } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  categories: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

const toast = useToast()
const confirm = useConfirm()

const search = ref(props.filters.search || '')

const route = window.route

function applyFilters() {
  router.get(route('admin.assistance-categories.index'), {
    search: search.value,
  }, { replace: true })
}

function onPage(event) {
  router.get(route('admin.assistance-categories.index'), {
    search: search.value,
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}

function confirmDelete(data) {
  confirm.destroy('Confirm Delete', `Delete category "${data.category_name}"? This cannot be undone.`, () => {
    router.delete(route('admin.assistance-categories.destroy', data.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Category deleted'),
      onError: () => toast.error('Delete failed'),
    })
  })
}
</script>

<template>
  <Head title="Assistance Categories" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Assistance Categories</div>
          <Button label="New Category" icon="pi pi-plus" @click="router.get(route('admin.assistance-categories.create'))" />
        </div>

        <div class="flex mb-6">
          <div class="w-72">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search categories..." class="w-full" @keyup.enter="applyFilters" />
            </IconField>
          </div>
        </div>

        <Deferred data="categories">
          <DataTable :value="toRaw(categories?.data ?? [])" striped-rows class="w-full">
            <Column field="category_name" header="Name" sortable />
            <Column field="category_description" header="Description">
              <template #body="{ data }">
                <span class="text-sm">{{ data.category_description || '—' }}</span>
              </template>
            </Column>
            <Column field="documents_count" header="Documents" sortable />
            <Column field="is_active" header="Status" sortable>
              <template #body="{ data }">
                <Tag :value="data.is_active ? 'Active' : 'Inactive'" :severity="data.is_active ? 'success' : 'danger'" />
              </template>
            </Column>
            <Column header="Actions" style="width: 8rem">
              <template #body="{ data }">
                <div class="flex gap-2">
                  <Button icon="pi pi-pencil" severity="info" text rounded size="small" v-tooltip="'Edit'"
                    @click="router.get(route('admin.assistance-categories.edit', data.id))" />
                  <Button icon="pi pi-trash" severity="danger" text rounded size="small" v-tooltip="'Delete'"
                    @click="confirmDelete(data)" />
                </div>
              </template>
            </Column>
          </DataTable>
          <template #empty>
            <div class="text-center py-8 text-muted-color">No categories found</div>
          </template>

          <Paginator
            v-if="(categories?.total ?? 0) > (categories?.per_page ?? 10)"
            :first="((categories?.current_page ?? 1) - 1) * (categories?.per_page ?? 10)"
            :rows="categories?.per_page ?? 10"
            :total-records="categories?.total ?? 0"
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
