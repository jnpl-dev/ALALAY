<script setup>
import { ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputSwitch from 'primevue/inputswitch'

defineOptions({ layout: AppLayout })

const route = window.route
const isEditing = ref(false)

const props = defineProps({
  groups: { type: Array, default: () => [] },
})

const initialValues = {}
for (const group of props.groups) {
  for (const setting of group.settings) {
    initialValues[setting.key] = setting.value ?? ''
  }
}

const form = useForm({ settings: { ...initialValues } })

function updateSetting(key, value) {
  form.settings[key] = value
}

function submit() {
  form.put(route('admin.settings.update'), {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      isEditing.value = false
    },
  })
}

function cancelEdit() {
  form.settings = { ...initialValues }
  form.clearErrors()
  isEditing.value = false
}

function handleButtonClick() {
  if (!isEditing.value) {
    isEditing.value = true
  } else {
    submit()
  }
}
</script>

<template>
  <Head title="System Settings" />

  <div class="grid grid-cols-12 gap-8">
    <div class="col-span-12">
      <div class="card">
        <div class="flex items-center justify-between mb-6">
          <div class="font-semibold text-xl">System Settings</div>
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
              :label="isEditing ? 'Save Settings' : 'Edit Settings'"
              :icon="isEditing ? 'pi pi-check' : 'pi pi-pencil'"
              :loading="form.processing"
              @click="handleButtonClick"
            />
          </div>
        </div>

        <form @submit.prevent="handleButtonClick">
          <div v-if="groups.length" class="space-y-8">
            <div v-for="group in groups" :key="group.group" class="space-y-4">
              <h3 class="text-lg font-semibold text-surface-900 capitalize border-b border-surface pb-2">{{ group.group.replace(/_/g, ' ') }} Settings</h3>

              <div v-for="setting in group.settings" :key="setting.key" class="flex items-center justify-between py-2">
                <label class="text-sm font-medium text-surface-700">{{ setting.label }}</label>
                <div class="w-72">
                  <InputSwitch
                    v-if="['1', '0', 'true', 'false'].includes(setting.value)"
                    :model-value="form.settings[setting.key] === '1' || form.settings[setting.key] === 'true'"
                    @update:model-value="updateSetting(setting.key, $event ? '1' : '0')"
                    :disabled="!isEditing"
                  />
                  <InputText
                    v-else
                    :model-value="form.settings[setting.key]"
                    @update:model-value="updateSetting(setting.key, $event)"
                    class="w-full"
                    :disabled="!isEditing"
                  />
                </div>
              </div>
            </div>
          </div>

          <div v-else class="py-8 text-center text-muted-color">
            <i class="pi pi-cog text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <p>No settings configured yet. Add settings in the database.</p>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
