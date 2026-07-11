<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import InputTextarea from 'primevue/textarea'
import InputSwitch from 'primevue/inputswitch'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'

defineOptions({ layout: AppLayout })

const props = defineProps({
  document: { type: Object, required: true },
  categories: { type: Array, default: () => [] },
})

const toast = useToast()
const route = window.route

const categoryOptions = props.categories.map(c => ({ label: c.category_name, value: c.id }))

const form = useForm({
  category_id: props.document.category_id,
  doc_name: props.document.doc_name,
  doc_description: props.document.doc_description || '',
  is_mandatory: props.document.is_mandatory,
  is_active: props.document.is_active,
})

const submit = () => {
  form.put(route('admin.required-documents.update', props.document.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Document updated', life: 3000 })
      router.get(route('admin.required-documents.index'))
    },
    onError: () => {
      toast.add({ severity: 'error', summary: 'Validation error', life: 3000 })
    },
  })
}
</script>

<template>
  <Head title="Edit Document" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="font-semibold text-xl mb-4">Edit Required Document</div>

        <form @submit.prevent="submit">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6 max-w-xl">
            <div>
              <label for="doc_name" class="block text-muted-color font-medium mb-2">Document Name <span class="text-red-500">*</span></label>
              <InputText id="doc_name" v-model="form.doc_name" class="w-full" :invalid="!!form.errors.doc_name" />
              <p v-if="form.errors.doc_name" class="text-xs text-red-500 mt-1">{{ form.errors.doc_name }}</p>
            </div>
            <div>
              <label for="category_id" class="block text-muted-color font-medium mb-2">Category <span class="text-red-500">*</span></label>
              <Select id="category_id" v-model="form.category_id" :options="categoryOptions" option-label="label" option-value="value" placeholder="Select category" class="w-full" :invalid="!!form.errors.category_id" />
              <p v-if="form.errors.category_id" class="text-xs text-red-500 mt-1">{{ form.errors.category_id }}</p>
            </div>
            <div class="sm:col-span-2">
              <label for="doc_description" class="block text-muted-color font-medium mb-2">Description</label>
              <InputTextarea id="doc_description" v-model="form.doc_description" class="w-full" rows="3" :invalid="!!form.errors.doc_description" />
              <p v-if="form.errors.doc_description" class="text-xs text-red-500 mt-1">{{ form.errors.doc_description }}</p>
            </div>
            <div class="flex items-center gap-3">
              <InputSwitch id="is_mandatory" v-model="form.is_mandatory" />
              <label for="is_mandatory" class="font-medium">Mandatory</label>
            </div>
            <div class="flex items-center gap-3">
              <InputSwitch id="is_active" v-model="form.is_active" />
              <label for="is_active" class="font-medium">Active</label>
            </div>
          </div>

          <hr class="border-surface my-6">

          <div class="flex justify-end gap-4">
            <Button type="button" label="Cancel" icon="pi pi-times" severity="secondary" @click="router.get(route('admin.required-documents.index'))" />
            <Button type="submit" label="Update Document" icon="pi pi-check" :loading="form.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
