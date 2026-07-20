<script setup>
import { ref, computed, watch } from 'vue'
import { Head, usePage, useForm, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import FileUpload from 'primevue/fileupload'
import Avatar from 'primevue/avatar'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import { useFieldValidation } from '@/Composables/useFieldValidation'

defineOptions({ layout: AppLayout })

const props = defineProps({
  userData: { type: Object, default: () => ({}) },
})

watch(() => props.userData, (data) => {
  if (data?.first_name) {
    form.first_name = data.first_name
    form.last_name = data.last_name
    form.middle_name = data.middle_name ?? ''
    form.name_extension = data.name_extension ?? ''
    form.email = data.email
  }
})

const authUser = computed(() => usePage().props.auth?.user)
const toast = useToast()
const isEditing = ref(false)

const emailValid = useFieldValidation(
  route('validate.email'),
  () => form.email,
  () => ({ exclude_id: authUser.value?.id }),
  { debounceMs: 400 },
)

const form = useForm({
  first_name: '',
  last_name: '',
  middle_name: '',
  name_extension: '',
  email: '',
  current_password: '',
  password: '',
  password_confirmation: '',
  profile_picture: null,
})

const submit = () => {
  form.post(route('account.update'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      form.reset('current_password', 'password', 'password_confirmation', 'profile_picture')
      isEditing.value = false
      toast.add({ severity: 'success', summary: 'Account updated', life: 3000 })
    },
    onError: () => {
      toast.add({ severity: 'error', summary: 'Validation error', life: 3000 })
    },
  })
}

const cancelEdit = () => {
  form.reset()
  form.clearErrors()
  isEditing.value = false
}

const handleButtonClick = () => {
  if (!isEditing.value) {
    isEditing.value = true
  } else {
    submit()
  }
}

const onProfilePictureSelect = (event) => {
  if (isEditing.value) {
    form.profile_picture = event.files[0]
  }
}

const profilePictureUrl = computed(() => {
  if (form.profile_picture instanceof File) {
    return URL.createObjectURL(form.profile_picture)
  }
  return authUser.value?.profile_picture_url || null
})
</script>

<template>
  <Head title="Account Settings" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-4">
          <div class="font-semibold text-xl">Account Settings</div>
          <div class="flex gap-2">
            <Button
              v-if="isEditing"
              type="button"
              label="Cancel"
              icon="pi pi-times"
              severity="secondary"
              @click="cancelEdit"
            />
            <Button
              type="submit"
              :label="isEditing ? 'Save Changes' : 'Edit Account'"
              :icon="isEditing ? 'pi pi-check' : 'pi pi-pencil'"
              :loading="form.processing"
              @click="handleButtonClick"
            />
          </div>
        </div>

        <Deferred data="userData">
          <form @submit.prevent="handleButtonClick">
            <div class="flex items-center gap-6 mb-2">
              <Avatar :image="profilePictureUrl" :label="authUser?.first_name?.charAt(0)" size="xlarge" shape="circle" class="shrink-0" />
              <div>
                <FileUpload
                  mode="basic"
                  accept="image/jpeg,image/png"
                  :maxFileSize="2048000"
                  chooseLabel="Change Photo"
                  class="p-button-sm"
                  :disabled="!isEditing"
                  @select="onProfilePictureSelect"
                />
                <p class="text-xs text-muted-color mt-1">JPEG or PNG. Max 2MB.</p>
                <p v-if="form.errors.profile_picture" class="text-xs text-red-500 mt-1">{{ form.errors.profile_picture }}</p>
              </div>
            </div>

            <hr class="border-surface my-6">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
              <div>
                <label for="first_name" class="block text-muted-color font-medium mb-2">First Name <span class="text-red-500">*</span></label>
                <InputText id="first_name" v-model="form.first_name" class="w-full" :invalid="!!form.errors.first_name" :disabled="!isEditing" />
                <p v-if="form.errors.first_name" class="text-xs text-red-500 mt-1">{{ form.errors.first_name }}</p>
              </div>
              <div>
                <label for="last_name" class="block text-muted-color font-medium mb-2">Last Name <span class="text-red-500">*</span></label>
                <InputText id="last_name" v-model="form.last_name" class="w-full" :invalid="!!form.errors.last_name" :disabled="!isEditing" />
                <p v-if="form.errors.last_name" class="text-xs text-red-500 mt-1">{{ form.errors.last_name }}</p>
              </div>
              <div>
                <label for="middle_name" class="block text-muted-color font-medium mb-2">Middle Name</label>
                <InputText id="middle_name" v-model="form.middle_name" class="w-full" :disabled="!isEditing" />
              </div>
              <div>
                <label for="name_extension" class="block text-muted-color font-medium mb-2">Name Extension</label>
                <InputText id="name_extension" v-model="form.name_extension" class="w-full" placeholder="e.g. Jr., III" :disabled="!isEditing" />
              </div>
            </div>

            <hr class="border-surface my-6">

            <div>
              <label for="email" class="block text-muted-color font-medium mb-2">Email <span class="text-red-500">*</span></label>
              <InputText id="email" v-model="form.email" type="email" class="w-full" :invalid="!!form.errors.email" :disabled="!isEditing" />
              <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
              <p v-else-if="emailValid.isChecking.value && form.email" class="text-xs text-gray-400 mt-1">Checking...</p>
              <p v-else-if="emailValid.isValid.value === false" class="text-xs text-amber-600 mt-1">{{ emailValid.message.value }}</p>
            </div>

            <hr class="border-surface my-6">

            <div>
              <p class="font-medium mb-4">Change Password <span class="text-sm font-normal text-muted-color">(optional)</span></p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                  <label for="current_password" class="block text-muted-color font-medium mb-2">Current Password</label>
                  <InputText id="current_password" v-model="form.current_password" type="password" class="w-full" :invalid="!!form.errors.current_password" :disabled="!isEditing" />
                  <p v-if="form.errors.current_password" class="text-xs text-red-500 mt-1">{{ form.errors.current_password }}</p>
                </div>
                <div></div>
                <div>
                  <label for="password" class="block text-muted-color font-medium mb-2">New Password</label>
                  <InputText id="password" v-model="form.password" type="password" class="w-full" :invalid="!!form.errors.password" :disabled="!isEditing" />
                  <p v-if="form.errors.password" class="text-xs text-red-500 mt-1">{{ form.errors.password }}</p>
                </div>
                <div>
                  <label for="password_confirmation" class="block text-muted-color font-medium mb-2">Confirm New Password</label>
                  <InputText id="password_confirmation" v-model="form.password_confirmation" type="password" class="w-full" :invalid="!!form.errors.password_confirmation" :disabled="!isEditing" />
                  <p v-if="form.errors.password_confirmation" class="text-xs text-red-500 mt-1">{{ form.errors.password_confirmation }}</p>
                </div>
              </div>
            </div>

            <hr class="border-surface my-6">
          </form>

          <template #fallback>
            <div class="space-y-6">
              <div class="flex items-center gap-6">
                <Skeleton shape="circle" size="5rem" />
                <Skeleton width="8rem" height="2.5rem" />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div v-for="i in 4" :key="i" class="space-y-2">
                  <Skeleton width="30%" height="0.75rem" />
                  <Skeleton width="100%" height="2.5rem" />
                </div>
              </div>
              <div class="space-y-2">
                <Skeleton width="20%" height="0.75rem" />
                <Skeleton width="100%" height="2.5rem" />
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div v-for="i in 3" :key="i" class="space-y-2">
                  <Skeleton width="40%" height="0.75rem" />
                  <Skeleton width="100%" height="2.5rem" />
                </div>
              </div>
            </div>
          </template>
        </Deferred>
      </div>
    </div>
  </div>
</template>
