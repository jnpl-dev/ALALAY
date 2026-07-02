<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm, Head, Link } from '@inertiajs/vue3'
import Button from 'primevue/button'
import InputText from 'primevue/inputtext'
import FloatLabel from 'primevue/floatlabel'
import Message from 'primevue/message'

const loginUrl = route('login')

const form = useForm({
  email: '',
})

const submit = () => {
  form.post(route('password.email'), {
    preserveState: false,
  })
}
</script>

<template>
  <Head title="Forgot Password" />
  <AuthLayout>
    <h2 class="text-xl font-semibold text-surface-800 mb-2">Forgot Password</h2>
    <p class="text-sm text-surface-500 mb-6">
      Enter your email address and we'll send you a password reset link.
    </p>

    <Message v-if="form.wasSuccessful" severity="success" variant="simple" class="mb-3">
      Password reset link sent to your email.
    </Message>

    <form @submit.prevent="submit" class="space-y-5">
      <Message v-if="form.errors.email" severity="error" variant="simple" class="mb-2">
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

      <Button
        type="submit"
        label="Send Reset Link"
        class="w-full"
        :loading="form.processing"
        icon="pi pi-envelope"
      />

      <div class="text-center">
        <Link :href="loginUrl" class="text-sm text-primary hover:underline">
          Back to sign in
        </Link>
      </div>
    </form>
  </AuthLayout>
</template>
