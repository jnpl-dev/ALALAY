<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { useForm, Head } from '@inertiajs/vue3'
import Button from 'primevue/button'

const form = useForm({})

const accept = () => {
  form.post(route('aup.accept'))
}
</script>

<template>
  <Head title="Acceptable Use Policy" />
  <AuthLayout>
    <h2 class="text-xl font-semibold text-surface-800 mb-4">Acceptable Use Policy</h2>

    <div class="text-sm text-surface-600 space-y-3 mb-6 leading-relaxed">
      <p>
        As a user of the ALALAY system, you are granted access to sensitive personal
        information of applicants and beneficiaries of the AICS program of the
        Municipality of General Mamerto Natividad, Nueva Ecija.
      </p>
      <p>
        By accepting this policy, you agree to:
      </p>
      <ul class="list-disc pl-5 space-y-1">
        <li>Use the system exclusively for official municipal business.</li>
        <li>Not share your login credentials with any other person.</li>
        <li>Not access, copy, or disclose personal data outside of your official role.</li>
        <li>Report any suspected security incident to the Admin immediately.</li>
        <li>Log out of the system when not in use.</li>
        <li>Comply with all applicable data privacy laws and NPC regulations.</li>
      </ul>
      <p>
        Violation of this policy may result in disciplinary action and/or revocation
        of system access.
      </p>
    </div>

    <form @submit.prevent="accept">
      <Button
        type="submit"
        label="I Acknowledge and Accept"
        class="w-full"
        :loading="form.processing"
        icon="pi pi-check-circle"
      />
    </form>
  </AuthLayout>
</template>
