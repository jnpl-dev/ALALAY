<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm, Head } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import Password from 'primevue/password'
import FloatLabel from 'primevue/floatlabel'
import Message from 'primevue/message'

const props = defineProps({
  token: String,
  email: String,
})

const form = useForm({
  token: props.token,
  email: props.email,
  password: '',
  password_confirmation: '',
})

const submit = () => {
  form.post(route('password.update'), {
    preserveState: false,
  })
}
</script>

<template>
  <Head title="Reset Password" />
  <AuthLayout>
    <h2 class="text-xl font-semibold text-surface-800 mb-6">Reset Password</h2>

    <form @submit.prevent="submit" class="space-y-5">
      <Message v-if="form.errors.email" severity="error" variant="simple">
        {{ form.errors.email }}
      </Message>

      <div class="space-y-2">
        <FloatLabel>
          <InputText
            id="email"
            v-model="form.email"
            type="email"
            class="w-full"
            :class="{ 'p-invalid': form.errors.email }"
          />
          <label for="email">Email</label>
        </FloatLabel>
      </div>

      <div class="space-y-2">
        <FloatLabel>
          <Password
            id="password"
            v-model="form.password"
            :feedback="true"
            class="w-full"
            input-class="w-full"
            :class="{ 'p-invalid': form.errors.password }"
          />
          <label for="password">New Password</label>
        </FloatLabel>
        <Message v-if="form.errors.password" severity="error" variant="simple">{{ form.errors.password }}</Message>
      </div>

      <div class="space-y-2">
        <FloatLabel>
          <Password
            id="password_confirmation"
            v-model="form.password_confirmation"
            :feedback="false"
            class="w-full"
            input-class="w-full"
            :class="{ 'p-invalid': form.errors.password_confirmation }"
          />
          <label for="password_confirmation">Confirm New Password</label>
        </FloatLabel>
      </div>

      <Button
        type="submit"
        label="Reset Password"
        class="w-full"
        :loading="form.processing"
        icon="pi pi-lock-open"
      />
    </form>
  </AuthLayout>
</template>
