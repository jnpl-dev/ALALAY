<script setup>
import { ref, watch } from 'vue'
import { Head, useForm, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import InputSwitch from 'primevue/inputswitch'
import Skeleton from 'primevue/skeleton'

defineOptions({ layout: AppLayout })

const route = window.route
const isEditing = ref(false)

const props = defineProps({
  groups: { type: Array, default: () => [] },
})

const form = useForm({ settings: {} })
let initialValues = {}

watch(() => props.groups, (newGroups) => {
  if (newGroups.length) {
    const values = {}
    for (const group of newGroups) {
      for (const setting of group.settings) {
        values[setting.key] = setting.value ?? ''
      }
    }
    initialValues = { ...values }
    form.settings = values
  }
})

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

        <Deferred data="groups">
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

          <template #fallback>
            <div class="space-y-6">
              <div v-for="i in 3" :key="i">
                <Skeleton width="50%" height="1.5rem" class="mb-4" />
                <div class="space-y-3">
                  <div v-for="j in 3" :key="j" class="flex items-center justify-between py-2">
                    <Skeleton width="30%" height="1rem" />
                    <Skeleton width="12rem" height="2.5rem" />
                  </div>
                </div>
              </div>
            </div>
          </template>
        </Deferred>
      </div>
    </div>
  </div>
</template>
