<script setup>
import { ref, computed } from 'vue'
import { useForm, Head } from '@inertiajs/vue3'

const form = useForm({})
const accepted = ref(false)

const canSubmit = computed(() => accepted.value && !form.processing)

const accept = () => {
  if (!accepted.value) return
  form.post(route('aup.accept'), { preserveState: true, preserveScroll: true })
}
</script>

<template>
  <Head title="Acceptable Use Policy" />

  <div class="min-h-screen bg-gradient-to-br from-emerald-50 via-white to-emerald-50 flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-2xl">
      <div class="text-center mb-8">
        <div class="w-14 h-14 bg-emerald-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-sm">
          <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
          </svg>
        </div>
        <h1 class="text-2xl font-bold text-emerald-900">Acceptable Use Policy</h1>
        <p class="text-emerald-600 text-sm mt-1">Municipality of General Mamerto Natividad, Nueva Ecija</p>
      </div>

      <div class="bg-white rounded-2xl shadow-lg border border-emerald-100 p-8 sm:p-10">
        <div class="prose prose-sm max-w-none text-emerald-800 space-y-4 leading-relaxed">
          <h2 class="text-lg font-bold text-emerald-900">1. Purpose and Scope</h2>
          <p>
            As a user of the <strong>ALALAY</strong> — AICS Digital Management &amp; Notification System, you are
            granted access to sensitive personal information (SPI) and privileged data of applicants and
            beneficiaries of the Assistance to Individuals in Crisis Situation (AICS) program of the
            Municipality of General Mamerto Natividad, Nueva Ecija.
          </p>
          <p>
            This Acceptable Use Policy ("Policy") defines the terms and conditions under which authorized
            personnel may access, use, disclose, and protect the information contained within the ALALAY system.
            This Policy applies to all employees, officers, contractors, and authorized representatives of the
            LGU who have been granted system access.
          </p>

          <h2 class="text-lg font-bold text-emerald-900">2. Authorized Use</h2>
          <p>
            By accessing the ALALAY system, you agree to:
          </p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Use the system exclusively for official municipal business related to the AICS program.</li>
            <li>Only access data that is necessary for the performance of your official duties and role.</li>
            <li>Maintain the confidentiality and integrity of all information accessed through the system.</li>
            <li>Ensure that all data entries, modifications, and approvals are accurate and complete.</li>
            <li>Report any system errors, data discrepancies, or suspected security incidents immediately to the System Administrator.</li>
          </ul>

          <h2 class="text-lg font-bold text-emerald-900">3. Prohibited Conduct</h2>
          <p>
            The following activities are strictly prohibited:
          </p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Sharing, lending, or disclosing your login credentials to any other person, including colleagues.</li>
            <li>Accessing, copying, modifying, or disclosing personal data outside of your official role and authorization.</li>
            <li>Using the system for personal purposes, unauthorized research, or any non-official activity.</li>
            <li>Downloading, printing, or exporting sensitive data unless explicitly required for official business.</li>
            <li>Attempting to bypass security controls, access restricted areas, or modify system configurations.</li>
            <li>Introducing malicious software, unauthorized scripts, or connecting unauthorized devices to the system.</li>
          </ul>

          <h2 class="text-lg font-bold text-emerald-900">4. Data Privacy and Security</h2>
          <p>
            You are responsible for complying with the following data privacy and security requirements:
          </p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Comply with all applicable provisions of the <strong>Data Privacy Act of 2012 (RA 10173)</strong> and
              its implementing rules and regulations, as well as all National Privacy Commission (NPC) advisories.</li>
            <li>Log out of the system and lock your workstation when not in use, especially in shared or public areas.</li>
            <li>Use strong passwords and not reuse passwords across different systems.</li>
            <li>Immediately report any suspected data breach, unauthorized access, or security incident to the
              Admin and the Data Protection Officer (DPO).</li>
            <li>Participate in mandatory data privacy and security awareness training as required.</li>
          </ul>

          <h2 class="text-lg font-bold text-emerald-900">5. Audit and Monitoring</h2>
          <p>
            All system activities, including logins, data access, modifications, approvals, and file downloads,
            are logged and audited. The LGU reserves the right to:
          </p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Monitor user activity within the ALALAY system for compliance with this Policy.</li>
            <li>Investigate any suspected violation of this Policy, with or without prior notice.</li>
            <li>Disclose audit logs and user activity records to authorized oversight bodies as required by law.</li>
          </ul>

          <h2 class="text-lg font-bold text-emerald-900">6. Consequences of Violation</h2>
          <p>
            Violation of any provision of this Policy may result in:
          </p>
          <ul class="list-disc pl-5 space-y-2">
            <li>Immediate suspension or revocation of system access privileges.</li>
            <li>Administrative disciplinary action in accordance with LGU rules and regulations.</li>
            <li>Civil and/or criminal liability under applicable laws, including the Data Privacy Act of 2012.</li>
          </ul>

          <h2 class="text-lg font-bold text-emerald-900">7. Acknowledgement</h2>
          <p>
            By accepting this Policy, you acknowledge that you have read, understood, and agree to be bound
            by all terms and conditions set forth herein. You further acknowledge that this Policy may be
            updated from time to time, and continued use of the system constitutes acceptance of any revisions.
          </p>
        </div>

        <form @submit.prevent="accept" class="mt-8 pt-6 border-t border-emerald-100">
          <label class="flex items-start gap-3 cursor-pointer group mb-6">
            <input
              type="checkbox"
              v-model="accepted"
              class="mt-0.5 w-5 h-5 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500 cursor-pointer shrink-0"
            />
            <span class="text-sm text-emerald-700 group-hover:text-emerald-900 transition-colors leading-relaxed">
              I have read, understood, and agree to the terms and conditions of this
              Acceptable Use Policy. I acknowledge that I am responsible for complying with
              all provisions stated herein.
            </span>
          </label>

          <button
            type="submit"
            :disabled="!canSubmit"
            class="w-full flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl font-semibold text-sm transition-all"
            :class="canSubmit
              ? 'bg-emerald-600 text-white hover:bg-emerald-700 active:bg-emerald-800 shadow-lg shadow-emerald-200 cursor-pointer'
              : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>I Acknowledge and Accept</span>
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
