<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import InputTextarea from 'primevue/textarea'
import InputSwitch from 'primevue/inputswitch'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'

defineOptions({ layout: AppLayout })

const toast = useToast()
const route = window.route

const form = useForm({
  category_name: '',
  category_description: '',
  is_active: true,
})

const submit = () => {
  form.post(route('admin.assistance-categories.store'), {
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Category created', life: 3000 })
      router.get(route('admin.assistance-categories.index'))
    },
    onError: () => {
      toast.add({ severity: 'error', summary: 'Validation error', life: 3000 })
    },
  })
}
</script>

<template>
  <Head title="Create Category" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="font-semibold text-xl mb-4">Create Assistance Category</div>

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
              <InputSwitch id="is_active" v-model="form.is_active" />
              <label for="is_active" class="font-medium">Active</label>
            </div>
          </div>

          <hr class="border-surface my-6">

          <div class="flex justify-end gap-4">
            <Button type="button" label="Cancel" icon="pi pi-times" severity="secondary" @click="router.get(route('admin.assistance-categories.index'))" />
            <Button type="submit" label="Create Category" icon="pi pi-check" :loading="form.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
