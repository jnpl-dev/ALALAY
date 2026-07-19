<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import InputText from 'primevue/inputtext'
import InputTextarea from 'primevue/textarea'
import InputSwitch from 'primevue/inputswitch'
import InputNumber from 'primevue/inputnumber'
import Button from 'primevue/button'
import { useToast } from 'primevue/usetoast'

defineOptions({ layout: AppLayout })

const props = defineProps({
  reference: { type: Object, required: true },
})

const toast = useToast()
const route = window.route

const form = useForm({
  code_type: props.reference.code_type,
  default_amount: props.reference.default_amount,
  description: props.reference.description || '',
  is_active: props.reference.is_active,
})

const submit = () => {
  form.put(route('admin.assistance-code-references.update', props.reference.id), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      toast.add({ severity: 'success', summary: 'Code reference updated', life: 3000 })
      router.get(route('admin.assistance-code-references.index'))
    },
    onError: () => {
      toast.add({ severity: 'error', summary: 'Validation error', life: 3000 })
    },
  })
}
</script>

<template>
  <Head title="Edit Code Reference" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="font-semibold text-xl mb-4">Edit Code Reference</div>

        <form @submit.prevent="submit">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-6 max-w-xl">
            <div>
              <label for="code_type" class="block text-muted-color font-medium mb-2">Code Type <span class="text-red-500">*</span></label>
              <InputText id="code_type" v-model="form.code_type" class="w-full" :invalid="!!form.errors.code_type" />
              <p v-if="form.errors.code_type" class="text-xs text-red-500 mt-1">{{ form.errors.code_type }}</p>
            </div>
            <div>
              <label for="default_amount" class="block text-muted-color font-medium mb-2">Default Amount <span class="text-red-500">*</span></label>
              <InputNumber id="default_amount" v-model="form.default_amount" :min="0" :step="100" placeholder="0.00" inputClass="w-full" class="w-full" :invalid="!!form.errors.default_amount" mode="currency" currency="PHP" locale="en-PH" />
              <p v-if="form.errors.default_amount" class="text-xs text-red-500 mt-1">{{ form.errors.default_amount }}</p>
            </div>
            <div class="sm:col-span-2">
              <label for="description" class="block text-muted-color font-medium mb-2">Description</label>
              <InputTextarea id="description" v-model="form.description" class="w-full" rows="3" :invalid="!!form.errors.description" />
              <p v-if="form.errors.description" class="text-xs text-red-500 mt-1">{{ form.errors.description }}</p>
            </div>
            <div class="flex items-center gap-3">
              <InputSwitch id="is_active" v-model="form.is_active" />
              <label for="is_active" class="font-medium">Active</label>
            </div>
          </div>

          <hr class="border-surface my-6">

          <div class="flex justify-end gap-4">
            <Button type="button" label="Cancel" icon="pi pi-times" severity="secondary" @click="router.get(route('admin.assistance-code-references.index'))" />
            <Button type="submit" label="Update Code Reference" icon="pi pi-check" :loading="form.processing" />
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
