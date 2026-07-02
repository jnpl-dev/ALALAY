<script setup>
import { computed } from 'vue'
import { Head, usePage, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Divider from 'primevue/divider'
import FileUpload from 'primevue/fileupload'
import Avatar from 'primevue/avatar'
import { useToast } from 'primevue/usetoast'

defineOptions({ layout: AppLayout })

const user = computed(() => usePage().props.auth?.user)
const toast = useToast()

const form = useForm({
  first_name: user.value?.first_name || '',
  last_name: user.value?.last_name || '',
  middle_name: user.value?.middle_name || '',
  name_extension: user.value?.name_extension || '',
  email: user.value?.email || '',
  current_password: '',
  password: '',
  password_confirmation: '',
  profile_picture: null,
})

const submit = () => {
  form.post(route('account.update'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset('current_password', 'password', 'password_confirmation', 'profile_picture')
      toast.add({ severity: 'success', summary: 'Account updated', life: 3000 })
    },
    onError: () => {
      toast.add({ severity: 'error', summary: 'Validation error', life: 3000 })
    },
  })
}

const onProfilePictureSelect = (event) => {
  form.profile_picture = event.files[0]
}

const profilePictureUrl = computed(() => {
  if (form.profile_picture instanceof File) {
    return URL.createObjectURL(form.profile_picture)
  }
  return user.value?.profile_picture_url || null
})
</script>

<template>
  <Head title="Account Settings" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12 lg:col-span-8 xl:col-span-6">
      <div class="card p-6">
        <div class="font-semibold text-xl mb-6">Account Settings</div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
          <div class="flex items-center gap-6 mb-2">
            <Avatar :image="profilePictureUrl" :label="user?.first_name?.charAt(0)" size="xlarge" shape="circle" class="shrink-0" />
            <div>
              <FileUpload
                mode="basic"
                accept="image/jpeg,image/png"
                :maxFileSize="2048000"
                chooseLabel="Change Photo"
                class="p-button-sm"
                @select="onProfilePictureSelect"
              />
              <p class="text-xs text-gray-500 mt-1">JPEG or PNG. Max 2MB.</p>
              <p v-if="form.errors.profile_picture" class="text-xs text-red-500 mt-1">{{ form.errors.profile_picture }}</p>
            </div>
          </div>

          <Divider />

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label for="first_name" class="block font-medium mb-2">First Name <span class="text-red-500">*</span></label>
              <InputText id="first_name" v-model="form.first_name" class="w-full" :invalid="!!form.errors.first_name" />
              <p v-if="form.errors.first_name" class="text-xs text-red-500 mt-1">{{ form.errors.first_name }}</p>
            </div>
            <div>
              <label for="last_name" class="block font-medium mb-2">Last Name <span class="text-red-500">*</span></label>
              <InputText id="last_name" v-model="form.last_name" class="w-full" :invalid="!!form.errors.last_name" />
              <p v-if="form.errors.last_name" class="text-xs text-red-500 mt-1">{{ form.errors.last_name }}</p>
            </div>
            <div>
              <label for="middle_name" class="block font-medium mb-2">Middle Name</label>
              <InputText id="middle_name" v-model="form.middle_name" class="w-full" />
            </div>
            <div>
              <label for="name_extension" class="block font-medium mb-2">Name Extension</label>
              <InputText id="name_extension" v-model="form.name_extension" class="w-full" placeholder="e.g. Jr., III" />
            </div>
          </div>

          <div>
            <label for="email" class="block font-medium mb-2">Email <span class="text-red-500">*</span></label>
            <InputText id="email" v-model="form.email" type="email" class="w-full" :invalid="!!form.errors.email" />
            <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
          </div>

          <Divider />

          <div>
            <p class="font-medium mb-4">Change Password <span class="text-sm font-normal text-gray-500">(optional)</span></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label for="current_password" class="block font-medium mb-2">Current Password</label>
                <InputText id="current_password" v-model="form.current_password" type="password" class="w-full" :invalid="!!form.errors.current_password" />
                <p v-if="form.errors.current_password" class="text-xs text-red-500 mt-1">{{ form.errors.current_password }}</p>
              </div>
              <div></div>
              <div>
                <label for="password" class="block font-medium mb-2">New Password</label>
                <InputText id="password" v-model="form.password" type="password" class="w-full" :invalid="!!form.errors.password" />
                <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
              </div>
              <div>
                <label for="password_confirmation" class="block font-medium mb-2">Confirm New Password</label>
                <InputText id="password_confirmation" v-model="form.password_confirmation" type="password" class="w-full" :invalid="!!form.errors.password_confirmation" />
                <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 mt-1">{{ form.errors.password_confirmation }}</p>
              </div>
            </div>
          </div>

          <Divider />

          <div class="flex justify-end">
            <Button type="submit" label="Save Changes" :loading="form.processing" icon="pi pi-check" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
