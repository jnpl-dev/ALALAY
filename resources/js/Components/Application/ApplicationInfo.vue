<script setup>
import { computed } from 'vue'
import { formatDate } from '@/Utils/formatDate'

const props = defineProps({
  application: { type: Object, required: true },
})

const capitalize = (str) => str ? str.charAt(0).toUpperCase() + str.slice(1).toLowerCase() : ''
const claimantSex = computed(() => capitalize(props.application.claimant_sex))
const beneficiarySex = computed(() => capitalize(props.application.beneficiary_sex))
</script>

<template>
  <div class="space-y-6">
    <div>
      <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Claimant</h3>
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div>
          <dt class="text-muted-color">Full Name</dt>
          <dd class="font-medium text-surface-900">{{ application.claimant_first_name }} {{ application.claimant_middle_name }} {{ application.claimant_last_name }} {{ application.claimant_name_extension }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Sex</dt>
          <dd class="font-medium text-surface-900">{{ claimantSex }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Date of Birth</dt>
          <dd class="font-medium text-surface-900">{{ formatDate(application.claimant_dob) }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Phone</dt>
          <dd class="font-medium text-surface-900">{{ application.claimant_phone }}</dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-muted-color">Address</dt>
          <dd class="font-medium text-surface-900">{{ application.claimant_address }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Relationship</dt>
          <dd class="font-medium text-surface-900">{{ application.claimant_relationship_to_beneficiary }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Email</dt>
          <dd class="font-medium text-surface-900">{{ application.claimant_email || '—' }}</dd>
        </div>
      </dl>
    </div>

    <hr class="border-surface" />

    <div>
      <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Beneficiary</h3>
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div>
          <dt class="text-muted-color">Full Name</dt>
          <dd class="font-medium text-surface-900">{{ application.beneficiary_first_name }} {{ application.beneficiary_middle_name }} {{ application.beneficiary_last_name }} {{ application.beneficiary_name_extension }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Sex</dt>
          <dd class="font-medium text-surface-900">{{ beneficiarySex }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Date of Birth</dt>
          <dd class="font-medium text-surface-900">{{ formatDate(application.beneficiary_dob) }}</dd>
        </div>
        <div class="sm:col-span-2">
          <dt class="text-muted-color">Address</dt>
          <dd class="font-medium text-surface-900">{{ application.beneficiary_address }}</dd>
        </div>
      </dl>
    </div>

    <hr class="border-surface" />

    <div>
      <h3 class="font-semibold text-surface-900 mb-3 text-sm uppercase tracking-wide text-muted-color">Application</h3>
      <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        <div>
          <dt class="text-muted-color">Reference Code</dt>
          <dd class="font-mono font-medium text-surface-900">{{ application.reference_code }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Category</dt>
          <dd class="font-medium text-surface-900">{{ application.category_name }}</dd>
        </div>
        <div>
          <dt class="text-muted-color">Submitted</dt>
          <dd class="font-medium text-surface-900">{{ formatDate(application.created_at) }}</dd>
        </div>
      </dl>
    </div>
  </div>
</template>
