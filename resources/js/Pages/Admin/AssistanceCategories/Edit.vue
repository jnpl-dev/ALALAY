<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import InputTextarea from 'primevue/textarea'
import ToggleSwitch from 'primevue/toggleswitch'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([
  { label: 'Admin' },
  { label: 'Settings' },
  { label: 'Assistance Categories' },
  { label: 'Edit Category' },
])

const props = defineProps({
  category: { type: Object, required: true },
})

const toast = useToast()
const route = window.route

const form = useForm({
  category_name: props.category.category_name,
  category_description: props.category.category_description || '',
  is_active: props.category.is_active,
})

const submit = () => {
  form.put(route('admin.assistance-categories.update', props.category.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Category updated', life: 3000 })
      router.get(route('admin.assistance-categories.index'))
    },
    onError: () => {
      toast.add({ severity: 'error', summary: 'Validation error', life: 3000 })
    },
  })
}
</script>

<template>
  <Head title="Edit Category" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="font-semibold text-xl mb-4">Edit Assistance Category</div>

        <form @submit.prevent="submit">
          <div class="grid grid-cols-1 gap-6 mb-6 max-w-xl">
            <div>
              <label for="category_name" class="block text-muted-color font-medium mb-2">Category Name <span class="text-red-500">*</span></label>
              <InputText id="category_name" v-model="form.category_name" class="w-full" :invalid="!!form.errors.category_name" />
              <p v-if="form.errors.category_name" class="text-xs text-red-500 mt-1">{{ form.errors.category_name }}</p>
            </div>
            <div>
              <label for="category_description" class="block text-muted-color font-medium mb-2">Description</label>
              <InputTextarea id="category_description" v-model="form.category_description" class="w-full" rows="3" :invalid="!!form.errors.category_description" />
              <p v-if="form.errors.category_description" class="text-xs text-red-500 mt-1">{{ form.errors.category_description }}</p>
            </div>
            <div class="flex items-center gap-3">
              <ToggleSwitch id="is_active" v-model="form.is_active" />
              <label for="is_active" class="font-medium">Active</label>
            </div>
          </div>

          <hr class="border-surface my-6">

          <div class="flex justify-end gap-4">
            <Button type="button" label="Cancel" icon="pi pi-times" severity="secondary" @click="router.get(route('admin.assistance-categories.index'))" />
            <Button type="submit" label="Update Category" icon="pi pi-check" :loading="form.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
