<script setup>
import { Head, usePage, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'

defineOptions({ layout: AppLayout })

defineProps({
  totalApplications: { type: Number, default: 0 },
  pendingApplications: { type: Number, default: 0 },
  approvedThisMonth: { type: Number, default: 0 },
  recentActivity: { type: Array, default: () => [] },
})

const page = usePage()
const user = page.props.auth?.user
const accountUrl = route('account.edit')
</script>

<template>
  <Head title="Dashboard" />

  <div class="grid grid-cols-12 gap-8">
      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Applications</span>
              <div class="text-surface-900 font-medium text-xl">{{ totalApplications }}</div>
            </div>
            <div class="flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-file text-blue-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">total submitted</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Pending</span>
              <div class="text-surface-900 font-medium text-xl">{{ pendingApplications }}</div>
            </div>
            <div class="flex items-center justify-center bg-orange-100 dark:bg-orange-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-clock text-orange-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">awaiting review</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">Approved</span>
              <div class="text-surface-900 font-medium text-xl">{{ approvedThisMonth }}</div>
            </div>
            <div class="flex items-center justify-center bg-green-100 dark:bg-green-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-check-circle text-green-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">— </span>
          <span class="text-muted-color">this month</span>
        </div>
      </div>

      <div class="col-span-12 lg:col-span-6 xl:col-span-3">
        <div class="card mb-0">
          <div class="flex justify-between mb-4">
            <div>
              <span class="block text-muted-color font-medium mb-4">{{ user?.first_name || 'User' }}</span>
              <div class="text-surface-900 font-medium text-xl">{{ user?.role?.replace('_', ' ') || '—' }}</div>
            </div>
            <div class="flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full" style="width: 2.5rem; height: 2.5rem">
              <i class="pi pi-user text-purple-500 text-xl!"></i>
            </div>
          </div>
          <span class="text-primary font-medium">{{ user?.email || '—' }}</span>
          <span v-if="user?.aup_accepted" class="text-muted-color"> &middot; AUP accepted</span>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-4">Recent Activity</div>
          <ul v-if="recentActivity.length" class="p-0 mx-0 mt-0 mb-6 list-none">
            <li v-for="entry in recentActivity" :key="entry.id" class="flex items-center py-2 border-b border-surface">
              <div class="w-12 h-12 flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-full mr-4 shrink-0">
                <i class="pi pi-history text-xl! text-blue-500"></i>
              </div>
              <span class="text-surface-900 leading-normal">
                {{ entry.user_name }}
                <span class="text-surface-700"> &middot; {{ entry.action }} / {{ entry.module }}</span>
              </span>
            </li>
          </ul>
          <div v-else class="flex flex-col items-center justify-center py-8 text-muted-color">
            <i class="pi pi-inbox text-4xl mb-3" style="color: var(--text-color-secondary);"></i>
            <span>No recent activity</span>
          </div>
        </div>

        <div class="card">
          <div class="font-semibold text-xl mb-4">Quick Links</div>
          <div class="flex flex-wrap gap-3">
            <button class="inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium cursor-pointer border-none" style="background-color: var(--p-primary-color); color: var(--p-primary-contrast-color);" @click="router.get(accountUrl)">
              <i class="pi pi-cog"></i>
              <span>Account Settings</span>
            </button>
          </div>
        </div>
      </div>

      <div class="col-span-12 xl:col-span-6">
        <div class="card">
          <div class="font-semibold text-xl mb-6">Account Info</div>

          <span class="block text-muted-color font-medium mb-4">AUTHENTICATION</span>
          <ul class="p-0 mx-0 mt-0 mb-6 list-none">
            <li class="flex items-center py-2 border-b border-surface">
              <div class="w-12 h-12 flex items-center justify-center bg-blue-100 dark:bg-blue-400/10 rounded-full mr-4 shrink-0">
                <i class="pi pi-envelope text-xl! text-blue-500"></i>
              </div>
              <span class="text-surface-900 leading-normal">
                {{ user?.email || '—' }}
                <span class="text-surface-700"> &middot; logged in</span>
              </span>
            </li>
            <li class="flex items-center py-2 border-b border-surface">
              <div class="w-12 h-12 flex items-center justify-center bg-green-100 dark:bg-green-400/10 rounded-full mr-4 shrink-0">
                <i class="pi pi-shield text-xl! text-green-500"></i>
              </div>
              <span class="text-surface-900 leading-normal">
                {{ user?.aup_accepted ? 'AUP Accepted' : 'AUP Pending' }}
                <span class="text-surface-700"> &middot; acceptable use policy</span>
              </span>
            </li>
          </ul>

          <span class="block text-muted-color font-medium mb-4">SESSION</span>
          <ul class="p-0 m-0 list-none">
            <li class="flex items-center py-2 border-b border-surface">
              <div class="w-12 h-12 flex items-center justify-center bg-purple-100 dark:bg-purple-400/10 rounded-full mr-4 shrink-0">
                <i class="pi pi-id-card text-xl! text-purple-500"></i>
              </div>
              <span class="text-surface-900 leading-normal">
                User #{{ user?.id || '—' }}
                <span class="text-surface-700"> &middot; role: {{ user?.role?.replace('_', ' ') || '—' }}</span>
              </span>
            </li>
          </ul>
        </div>
      </div>
    </div>
</template>
