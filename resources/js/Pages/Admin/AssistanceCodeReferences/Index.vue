<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
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
import { formatCurrency } from '@/Utils/formatCurrency'

defineOptions({ layout: AppLayout })

const props = defineProps({
  references: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

const toast = useToast()
const confirm = useConfirm()

const search = ref(props.filters.search || '')

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

function confirmDelete(data) {
  confirm.destroy('Confirm Delete', `Delete code reference "${data.code_type}"? This cannot be undone.`, () => {
    router.delete(route('admin.assistance-code-references.destroy', data.id), {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => toast.success('Code reference deleted'),
      onError: () => toast.error('Delete failed'),
    })
  })
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
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search code types..." class="w-full" @keyup.enter="applyFilters" />
            </IconField>
          </div>
        </div>

        <Deferred data="references">
          <DataTable :value="toRaw(references?.data ?? [])" striped-rows class="w-full">
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
                  <Button icon="pi pi-pencil" severity="info" text rounded size="small" v-tooltip="'Edit'"
                    @click="router.get(route('admin.assistance-code-references.edit', data.id))" />
                  <Button icon="pi pi-trash" severity="danger" text rounded size="small" v-tooltip="'Delete'"
                    @click="confirmDelete(data)" />
                </div>
              </template>
            </Column>
          </DataTable>
          <template #empty>
            <div class="text-center py-8 text-muted-color">No code references found</div>
          </template>

          <Paginator
            v-if="(references?.total ?? 0) > (references?.per_page ?? 10)"
            :first="((references?.current_page ?? 1) - 1) * (references?.per_page ?? 10)"
            :rows="references?.per_page ?? 10"
            :total-records="references?.total ?? 0"
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
