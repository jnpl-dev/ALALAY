<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Head, usePage, useForm, Deferred, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import Dialog from 'primevue/dialog'
import Divider from 'primevue/divider'
import Skeleton from 'primevue/skeleton'
import { useToast } from 'primevue/usetoast'
import { useFieldValidation } from '@/Composables/useFieldValidation'
import { useConfirm } from '@/Composables/useConfirm'
import { useBreadcrumb } from '@/Composables/useBreadcrumb'

defineOptions({ layout: AppLayout })

useBreadcrumb([{ label: 'Home' }, { label: 'Account Settings' }])

const props = defineProps({
  userData: { type: Object, default: () => ({}) },
})

const snapshot = ref({})

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
const confirm = useConfirm()
const isEditing = ref(false)
const fileInput = ref(null)
const previewUrl = ref(null)
const picVersion = computed(() => authUser.value?.profile_picture_version ?? 0)
const photoModalVisible = ref(false)

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

const isDirty = computed(() => {
  if (!isEditing.value) return false
  return form.first_name !== snapshot.value.first_name
    || form.last_name !== snapshot.value.last_name
    || form.middle_name !== (snapshot.value.middle_name ?? '')
    || form.name_extension !== (snapshot.value.name_extension ?? '')
    || form.email !== snapshot.value.email
})

const emailValid = useFieldValidation(
  route('validate.email'),
  () => form.email,
  () => ({ exclude_id: authUser.value?.id }),
  { debounceMs: 400 },
)

function startEditing() {
  snapshot.value = {
    first_name: form.first_name,
    last_name: form.last_name,
    middle_name: form.middle_name,
    name_extension: form.name_extension,
    email: form.email,
  }
  isEditing.value = true
}

const submit = () => {
  form.post(route('account.update'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      previewUrl.value = null
      form.reset('current_password', 'password', 'password_confirmation', 'profile_picture')
      isEditing.value = false
      toast.add({ severity: 'success', summary: 'Account updated', life: 3000 })
    },
    onError: () => {
      toast.add({ severity: 'error', summary: 'Validation error', life: 3000 })
    },
  })
}

function discardAndCancel() {
  previewUrl.value = null
  form.reset()
  form.clearErrors()
  isEditing.value = false
}

function requestCancel() {
  if (isDirty.value) {
    confirm.require({
      message: 'You have unsaved changes. Discard them?',
      header: 'Discard Changes',
      icon: 'pi pi-exclamation-triangle',
      rejectProps: { label: 'Keep Editing', outlined: true },
      acceptProps: { label: 'Discard', severity: 'danger' },
      accept: discardAndCancel,
    })
  } else {
    discardAndCancel()
  }
}

const handleButtonClick = () => {
  if (!isEditing.value) {
    startEditing()
  } else {
    submit()
  }
}

function viewPhoto() {
  if (authUser.value?.profile_picture_url) {
    photoModalVisible.value = true
  }
}

function onFilePicked(event) {
  const file = event.target.files?.[0]
  if (file) {
    form.profile_picture = file
    const reader = new FileReader()
    reader.onload = (e) => { previewUrl.value = e.target.result }
    reader.readAsDataURL(file)
  }
  event.target.value = ''
}

function triggerFilePicker() {
  fileInput.value?.click()
}

const profilePictureUrl = computed(() => {
  if (previewUrl.value) return previewUrl.value
  const url = authUser.value?.profile_picture_url
  if (url) return `${url}?v=${picVersion.value}`
  return null
})

function onBeforeInertiaNavigate(event) {
  if (isDirty.value) {
    event.preventDefault()
    confirm.require({
      message: 'You have unsaved changes. Discard them?',
      header: 'Discard Changes',
      icon: 'pi pi-exclamation-triangle',
      rejectProps: { label: 'Stay', outlined: true },
      acceptProps: { label: 'Discard', severity: 'danger' },
      accept: () => {
        discardAndCancel()
        unsubBefore?.()
        router.visit(event.detail.visit.url, {
          method: event.detail.visit.method,
          data: event.detail.visit.data,
          preserveState: true,
        })
      },
    })
  }
}

function onBeforeUnload(event) {
  if (isDirty.value) {
    event.preventDefault()
    event.returnValue = ''
  }
}

let unsubBefore = null

onMounted(() => {
  unsubBefore = router.on('before', onBeforeInertiaNavigate)
  window.addEventListener('beforeunload', onBeforeUnload)
})

onUnmounted(() => {
  unsubBefore?.()
  window.removeEventListener('beforeunload', onBeforeUnload)
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
              @click="requestCancel"
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
          <Transition appear mode="out-in">
            <form @submit.prevent="handleButtonClick">
            <div class="flex items-center gap-6 mb-2">
              <Avatar v-if="profilePictureUrl" :key="profilePictureUrl" :image="profilePictureUrl" size="xlarge" shape="circle" class="shrink-0" />
              <Avatar v-else :key="authUser?.id" :label="authUser?.first_name?.charAt(0)" size="xlarge" shape="circle" class="shrink-0" />
              <div>
                <Button
                  v-if="!isEditing && authUser?.profile_picture_url"
                  type="button"
                  label="View Photo"
                  icon="pi pi-eye"
                  severity="secondary"
                  size="small"
                  @click="viewPhoto"
                />
                <Button
                  v-if="isEditing"
                  type="button"
                  label="Change Photo"
                  icon="pi pi-camera"
                  severity="secondary"
                  size="small"
                  @click="triggerFilePicker"
                />
                <input ref="fileInput" type="file" accept="image/jpeg,image/png" class="hidden" @change="onFilePicked" />
                <p v-if="form.errors.profile_picture" class="text-xs text-red-500 mt-1">{{ form.errors.profile_picture }}</p>
              </div>
            </div>

            <Divider />

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

            <Divider />

            <div>
              <label for="email" class="block text-muted-color font-medium mb-2">Email <span class="text-red-500">*</span></label>
              <InputText id="email" v-model="form.email" type="email" class="w-full" :invalid="!!form.errors.email" :disabled="!isEditing" />
              <p v-if="form.errors.email" class="text-xs text-red-500 mt-1">{{ form.errors.email }}</p>
              <p v-else-if="emailValid.isChecking.value && form.email" class="text-xs text-muted-color mt-1">Checking...</p>
              <p v-else-if="emailValid.isValid.value === false" class="text-xs text-amber-600 mt-1">{{ emailValid.message.value }}</p>
            </div>

            <Divider />

            <div>
              <p class="font-medium mb-4">Change Password <span class="text-sm font-normal text-muted-color">(optional)</span></p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="sm:col-span-2">
                  <label for="current_password" class="block text-muted-color font-medium mb-2">Current Password</label>
                  <InputText id="current_password" v-model="form.current_password" type="password" class="w-full" :invalid="!!form.errors.current_password" :disabled="!isEditing" />
                  <p v-if="form.errors.current_password" class="text-xs text-red-500 mt-1">{{ form.errors.current_password }}</p>
                </div>
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

            <Divider />
          </form>
          </Transition>

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

  <Dialog v-model:visible="photoModalVisible" :header="authUser?.first_name + '\'s Photo'" modal :style="{ maxWidth: '90vw', maxHeight: '90vh' }" class="photo-dialog">
    <img v-if="authUser?.profile_picture_url" :src="`${authUser.profile_picture_url}?v=${picVersion}`" alt="Profile Photo" class="w-full" />
  </Dialog>
</template>

<style scoped>
.page-enter-active {
  transition: opacity 0.2s ease;
  transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
}
.page-enter-from {
  opacity: 0;
}

.hidden {
  display: none;
}
</style>
