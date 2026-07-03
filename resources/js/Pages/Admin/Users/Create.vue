<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import Select from 'primevue/select'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'

defineOptions({ layout: AppLayout })

const toast = useToast()

const roleOptions = [
  { label: 'Admin', value: 'admin' },
  { label: 'AICS Staff', value: 'aics_staff' },
  { label: 'MSWDO', value: 'mswdo' },
  { label: 'Accountant', value: 'accountant' },
  { label: 'Treasurer', value: 'treasurer' },
  { label: "Mayor's Office", value: 'mayors_office' },
]

const form = useForm({
  first_name: '',
  last_name: '',
  middle_name: '',
  name_extension: '',
  email: '',
  role: '',
  password: '',
  password_confirmation: '',
})

const goBack = () => {
  router.get(route('admin.users.index'))
}

const submit = () => {
  form.post(route('admin.users.store'), {
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'User created', life: 3000 })
      router.get(route('admin.users.index'))
    },
    onError: () => {
      toast.add({ severity: 'error', summary: 'Validation error', life: 3000 })
    },
  })
}
</script>

<template>
  <Head title="Create User" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="font-semibold text-xl mb-4">Create User</div>

        <form @submit.prevent="submit">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
            <div>
              <label for="first_name" class="block text-muted-color font-medium mb-2">First Name <span class="text-red-500">*</span></label>
              <InputText id="first_name" v-model="form.first_name" class="w-full" :invalid="!!form.errors.first_name" />
              <p v-if="form.errors.first_name" class="text-xs text-red-500 mt-1">{{ form.errors.first_name }}</p>
            </div>
            <div>
              <label for="last_name" class="block text-muted-color font-medium mb-2">Last Name <span class="text-red-500">*</span></label>
              <InputText id="last_name" v-model="form.last_name" class="w-full" :invalid="!!form.errors.last_name" />
              <p v-if="form.errors.last_name" class="text-xs text-red-500 mt-1">{{ form.errors.last_name }}</p>
            </div>
            <div>
              <label for="middle_name" class="block text-muted-color font-medium mb-2">Middle Name</label>
              <InputText id="middle_name" v-model="form.middle_name" class="w-full" />
            </div>
            <div>
              <label for="name_extension" class="block text-muted-color font-medium mb-2">Name Extension</label>
              <InputText id="name_extension" v-model="form.name_extension" class="w-full" placeholder="e.g. Jr., III" />
            </div>
          </div>

          <hr class="border-surface my-6">

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
            <div>
              <label for="email" class="block text-muted-color font-medium mb-2">Email <span class="text-red-500">*</span></label>
              <InputText id="email" v-model="form.email" type="email" class="w-full" :invalid="!!form.errors.email" />
              <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
            </div>
            <div>
              <label for="role" class="block text-muted-color font-medium mb-2">Role <span class="text-red-500">*</span></label>
              <Select id="role" v-model="form.role" :options="roleOptions" option-label="label" option-value="value" placeholder="Select role" class="w-full"
                :invalid="!!form.errors.role" />
              <p v-if="form.errors.role" class="text-xs text-red-500 mt-1">{{ form.errors.role }}</p>
            </div>
          </div>

          <hr class="border-surface my-6">

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6">
            <div>
              <label for="password" class="block text-muted-color font-medium mb-2">Password <span class="text-red-500">*</span></label>
              <InputText id="password" v-model="form.password" type="password" class="w-full" :invalid="!!form.errors.password" />
              <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
            </div>
            <div>
              <label for="password_confirmation" class="block text-muted-color font-medium mb-2">Confirm Password <span class="text-red-500">*</span></label>
              <InputText id="password_confirmation" v-model="form.password_confirmation" type="password" class="w-full" :invalid="!!form.errors.password_confirmation" />
              <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 mt-1">{{ form.errors.password_confirmation }}</p>
            </div>
          </div>

          <hr class="border-surface my-6">

          <div class="flex justify-end gap-4">
            <Button type="button" label="Cancel" icon="pi pi-times" severity="secondary" @click="goBack" />
            <Button type="submit" label="Create User" icon="pi pi-check" :loading="form.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
