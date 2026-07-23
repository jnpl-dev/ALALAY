<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import Avatar from 'primevue/avatar'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputText from 'primevue/inputtext'
import IconField from 'primevue/iconfield'
import InputIcon from 'primevue/inputicon'
import Select from 'primevue/select'
import Tag from 'primevue/tag'
import Paginator from 'primevue/paginator'
import Popover from 'primevue/popover'
import Skeleton from 'primevue/skeleton'
import { useToast } from '@/Composables/useToast'
import { useConfirm } from '@/Composables/useConfirm'
import { formatDate } from '@/Utils/formatDate'
import { roleSeverity, statusSeverity } from '@/Utils/severityMappings'
import { ref, toRaw } from 'vue'

defineOptions({ layout: AppLayout })

const props = defineProps({
  users: { type: Object, default: () => ({}) },
  filters: { type: Object, default: () => ({}) },
})

const toast = useToast()
const confirm = useConfirm()
const selectedUser = ref(null)
const op = ref(null)

const toggleActions = (event, user) => {
  selectedUser.value = user
  op.value.toggle(event)
}

const goToCreate = () => {
  router.get(window.route('admin.users.create'))
}

const goToEdit = (user) => {
  router.get(window.route('admin.users.edit', user.id))
}

const roleLabel = (role) => ({
  admin: 'Admin',
  aics_staff: 'AICS',
  mswdo: 'MSWDO',
  accountant: 'Accountant',
  treasurer: 'Treasurer',
  mayors_office: "Mayor's Office",
}[role] || role)

const initials = (user) => {
  const first = (user.first_name || '')[0] || ''
  const last = (user.last_name || '')[0] || ''
  return (first + last).toUpperCase()
}

const profilePictureUrl = (user) => {
  if (!user.profile_picture_path) return null
  const base = window.route('admin.users.profile-picture', user.id)
  const v = user.profile_picture_version ?? 0
  return `${base}?v=${v}`
}

const search = ref(props.filters.search || '')
const role = ref(props.filters.role || '')
const status = ref(props.filters.status || '')

const applyFilters = () => {
  router.get(window.route('admin.users.index'), {
    search: search.value,
    role: role.value,
    status: status.value,
  }, { replace: true })
}

const roleOptions = [
  { label: 'All Roles', value: '' },
  { label: 'Admin', value: 'admin' },
  { label: 'AICS Staff', value: 'aics_staff' },
  { label: 'MSWDO', value: 'mswdo' },
  { label: 'Accountant', value: 'accountant' },
  { label: 'Treasurer', value: 'treasurer' },
  { label: "Mayor's Office", value: 'mayors_office' },
]

const statusOptions = [
  { label: 'All Status', value: '' },
  { label: 'Active', value: 'active' },
  { label: 'Inactive', value: 'inactive' },
]

const confirmDelete = (user) => {
  confirm.require({
    message: `Delete user "${user.full_name}"? This cannot be undone.`,
    header: 'Confirm Delete',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancel',
    acceptLabel: 'Delete',
    rejectClass: 'p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(window.route('admin.users.destroy', user.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('User deleted'),
        onError: () => toast.error('Delete failed'),
      })
    },
  })
}

const confirmToggleStatus = (user) => {
  const action = user.status === 'active' ? 'deactivate' : 'activate'
  confirm.require({
    message: `${action.charAt(0).toUpperCase() + action.slice(1)} user "${user.full_name}"?`,
    header: 'Confirm Status Change',
    icon: user.status === 'active' ? 'pi pi-ban' : 'pi pi-check-circle',
    rejectLabel: 'Cancel',
    acceptLabel: action.charAt(0).toUpperCase() + action.slice(1),
    rejectClass: 'p-button-outlined',
    acceptClass: user.status === 'active' ? 'p-button-danger' : 'p-button-success',
    accept: () => {
      router.patch(window.route('admin.users.toggle-status', user.id), {}, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Status updated'),
        onError: () => toast.error('Status update failed'),
      })
    },
  })
}

const confirmRevokeSessions = (user) => {
  confirm.require({
    message: `Revoke all sessions for "${user.full_name}"? They will be logged out of all devices.`,
    header: 'Confirm Revoke Sessions',
    icon: 'pi pi-exclamation-triangle',
    rejectLabel: 'Cancel',
    acceptLabel: 'Revoke',
    rejectClass: 'p-button-outlined',
    acceptClass: 'p-button-danger',
    accept: () => {
      router.delete(window.route('admin.users.revoke-sessions', user.id), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => toast.success('Sessions revoked'),
        onError: () => toast.error('Revoke failed'),
      })
    },
  })
}

const onPage = (event) => {
  router.get(window.route('admin.users.index'), {
    search: search.value,
    role: role.value,
    status: status.value,
    page: event.page + 1,
  }, { preserveState: true, replace: true })
}
</script>

<template>
  <Head title="Users" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">Users</div>
          <Button label="Add User" icon="pi pi-plus" @click="goToCreate" />
        </div>

        <div class="flex flex-wrap gap-4 mb-6">
          <div class="flex-1 min-w-48">
            <IconField>
              <InputIcon class="pi pi-search" />
              <InputText v-model="search" placeholder="Search name or email..." class="w-full"
                @keyup.enter="applyFilters" />
            </IconField>
          </div>
          <div class="w-48">
            <Select v-model="role" :options="roleOptions" option-label="label" option-value="value" placeholder="All Roles" class="w-full" @change="applyFilters" />
          </div>
          <div class="w-48">
            <Select v-model="status" :options="statusOptions" option-label="label" option-value="value" placeholder="All Status" class="w-full" @change="applyFilters" />
          </div>
        </div>

        <Deferred data="users">
          <DataTable :value="toRaw(users?.data ?? [])" striped-rows class="w-full">
            <Column style="width: 4rem">
              <template #body="{ data }">
                <Avatar v-if="profilePictureUrl(data)" :key="profilePictureUrl(data)" :image="profilePictureUrl(data)" class="font-semibold" size="large" shape="circle" />
                <Avatar v-else :key="data.id" :label="initials(data)" class="font-semibold" size="large" shape="circle" />
              </template>
            </Column>
            <Column field="full_name" header="Name" sortable />
            <Column field="email" header="Email" sortable />
            <Column field="role" header="Role" sortable>
              <template #body="{ data }">
                <Tag :value="roleLabel(data.role)" :severity="roleSeverity(data.role)" />
              </template>
            </Column>
            <Column field="status" header="Status" sortable>
              <template #body="{ data }">
                <Tag :value="data.status" :severity="statusSeverity(data.status)" />
              </template>
            </Column>
            <Column field="created_at" header="Created" sortable>
              <template #body="{ data }">
                {{ formatDate(data.created_at) }}
              </template>
            </Column>
            <Column header="Actions" style="min-width: 8rem">
              <template #body="{ data }">
                <Button icon="pi pi-ellipsis-h" severity="secondary" text rounded v-tooltip="'Actions'"
                  @click="toggleActions($event, data)" />
              </template>
            </Column>
          </DataTable>
          <template #empty>
            <div class="text-center py-8 text-muted-color">No users found</div>
          </template>

          <Paginator v-if="(users?.total ?? 0) > (users?.per_page ?? 10)" :first="((users?.current_page ?? 1) - 1) * (users?.per_page ?? 10)" :rows="users?.per_page ?? 10"
            :total-records="users?.total ?? 0" @page="onPage"
            template="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink" class="mt-4" />

          <template #fallback>
            <div class="space-y-3">
              <Skeleton v-for="i in 5" :key="i" width="100%" height="3.5rem" />
            </div>
          </template>
        </Deferred>

        <Popover ref="op">
          <div class="flex flex-col gap-1 min-w-40">
            <button @click="goToEdit(selectedUser)"
              class="flex items-center gap-3 px-3 py-2 text-sm text-blue-600 w-full text-left hover:bg-blue-50 dark:hover:bg-blue-950 rounded transition-colors cursor-pointer border-none bg-transparent">
              <i class="pi pi-pencil"></i>
              Edit
            </button>
            <button @click="confirmToggleStatus(selectedUser)"
              class="flex items-center gap-3 px-3 py-2 text-sm w-full text-left rounded transition-colors cursor-pointer border-none bg-transparent"
              :class="selectedUser?.status === 'active'
                ? 'text-orange-600 hover:bg-orange-50 dark:hover:bg-orange-950'
                : 'text-green-600 hover:bg-green-50 dark:hover:bg-green-950'">
              <i :class="selectedUser?.status === 'active' ? 'pi pi-ban' : 'pi pi-check'"></i>
              {{ selectedUser?.status === 'active' ? 'Deactivate' : 'Activate' }}
            </button>
            <button @click="confirmDelete(selectedUser)"
              class="flex items-center gap-3 px-3 py-2 text-sm text-red-600 w-full text-left hover:bg-red-50 dark:hover:bg-red-950 rounded transition-colors cursor-pointer border-none bg-transparent">
              <i class="pi pi-trash"></i>
              Delete
            </button>
            <button @click="confirmRevokeSessions(selectedUser)"
              class="flex items-center gap-3 px-3 py-2 text-sm text-color w-full text-left hover:bg-surface-100 dark:hover:bg-surface-700 rounded transition-colors cursor-pointer border-none bg-transparent">
              <i class="pi pi-sign-out"></i>
              Revoke Sessions
            </button>
          </div>
        </Popover>
      </div>
    </div>
  </div>
</template>
