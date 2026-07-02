<script setup>
import { Head, usePage, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'

defineOptions({ layout: AppLayout })

const page = usePage()
const user = page.props.auth?.user
const toast = useToast()

const form = useForm({
  name: user?.name || '',
  email: user?.email || '',
})

const submit = () => {
  form.put(route('account.update'), {
    onSuccess: () => toast.add({ severity: 'success', summary: 'Saved', life: 3000 }),
    onError: () => toast.add({ severity: 'error', summary: 'Error', life: 3000 }),
  })
}
</script>

<template>
  <Head title="Account Settings" />
  <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Account Settings</div>
          <form @submit.prevent="submit" class="flex flex-col gap-4">
            <div>
              <label for="name" class="block font-medium mb-2">Name</label>
              <InputText id="name" v-model="form.name" class="w-full" />
            </div>
            <div>
              <label for="email" class="block font-medium mb-2">Email</label>
              <InputText id="email" v-model="form.email" class="w-full" disabled />
            </div>
            <Button type="submit" label="Save Changes" :loading="form.processing" class="w-fit" />
          </form>
        </div>
      </div>
    </div>
</template>
