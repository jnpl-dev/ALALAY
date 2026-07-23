<script setup>
import { Head, router, Deferred } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { useAuth } from '@/Composables/useAuth'
import AppKpiCard from '@/Components/Common/AppKpiCard.vue'
import AppEmptyState from '@/Components/Common/AppEmptyState.vue'
import Button from 'primevue/button'
import Accordion from 'primevue/accordion'
import AccordionPanel from 'primevue/accordionpanel'
import AccordionHeader from 'primevue/accordionheader'
import AccordionContent from 'primevue/accordioncontent'
import DataView from 'primevue/dataview'
import Skeleton from 'primevue/skeleton'

defineOptions({ layout: AppLayout })

defineProps({
  dashboardData: { type: Object, default: () => ({}) },
})

const { user } = useAuth()
const accountUrl = route('account.edit')
</script>

<template>
  <Head title="Dashboard" />

  <Deferred data="dashboardData">
    <Transition appear mode="out-in">
      <div class="grid grid-cols-12 gap-8">
        <div class="col-span-12 lg:col-span-6 xl:col-span-3">
          <AppKpiCard title="Users" :value="dashboardData?.totalUsers ?? 0" icon="pi pi-users" color="info" subtitle="registered accounts" />
        </div>
        <div class="col-span-12 lg:col-span-6 xl:col-span-3">
          <AppKpiCard title="Applications" :value="dashboardData?.totalApplications ?? 0" icon="pi pi-file" color="info" subtitle="total submitted" />
        </div>
        <div class="col-span-12 lg:col-span-6 xl:col-span-3">
          <AppKpiCard title="Pending" :value="dashboardData?.pendingApplications ?? 0" icon="pi pi-clock" color="warn" subtitle="awaiting review" />
        </div>
        <div class="col-span-12 lg:col-span-6 xl:col-span-3">
          <AppKpiCard title="Approved" :value="dashboardData?.approvedThisMonth ?? 0" icon="pi pi-check-circle" color="success" subtitle="this month" />
        </div>

        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <div class="font-semibold text-xl mb-4">Recent Activity</div>
            <DataView :value="dashboardData?.recentActivity ?? []">
              <template #list="{ items }">
                <div v-for="item in items" :key="item.id" class="flex items-center py-2">
                  <div class="w-12 h-12 flex items-center justify-center rounded-full mr-4 shrink-0" :style="{ backgroundColor: 'var(--color-primary-surface)' }">
                    <i class="pi pi-history text-xl!" :style="{ color: 'var(--color-primary)' }"></i>
                  </div>
                  <span class="text-surface-900 leading-normal">
                    {{ item.user_name }}
                    <span class="text-surface-700"> &middot; {{ item.action }} / {{ item.module }}</span>
                  </span>
                </div>
              </template>
              <template #empty>
                <AppEmptyState icon="pi pi-inbox" message="No recent activity" />
              </template>
            </DataView>
          </div>

          <div class="card">
            <div class="font-semibold text-xl mb-4">Quick Links</div>
            <div class="flex flex-wrap gap-3">
              <Button label="Account Settings" icon="pi pi-cog" @click="router.get(accountUrl)" />
            </div>
          </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <div class="font-semibold text-xl mb-6">Account Info</div>

            <Accordion class="w-full">
              <AccordionPanel value="authentication">
                <AccordionHeader>Authentication</AccordionHeader>
                <AccordionContent>
                  <ul class="p-0 mx-0 my-0 list-none">
                    <li class="flex items-center py-2">
                      <div class="w-12 h-12 flex items-center justify-center rounded-full mr-4 shrink-0" :style="{ backgroundColor: 'var(--color-primary-surface)' }">
                        <i class="pi pi-envelope text-xl!" :style="{ color: 'var(--color-primary)' }"></i>
                      </div>
                      <span class="text-surface-900 leading-normal">
                        {{ user?.email || '—' }}
                        <span class="text-surface-700"> &middot; logged in</span>
                      </span>
                    </li>
                    <li class="flex items-center py-2">
                      <div class="w-12 h-12 flex items-center justify-center rounded-full mr-4 shrink-0" :style="{ backgroundColor: 'var(--color-success-surface)' }">
                        <i class="pi pi-shield text-xl!" :style="{ color: 'var(--color-success)' }"></i>
                      </div>
                      <span class="text-surface-900 leading-normal">
                        {{ user?.aup_accepted ? 'AUP Accepted' : 'AUP Pending' }}
                        <span class="text-surface-700"> &middot; acceptable use policy</span>
                      </span>
                    </li>
                  </ul>
                </AccordionContent>
              </AccordionPanel>
              <AccordionPanel value="session">
                <AccordionHeader>Session</AccordionHeader>
                <AccordionContent>
                  <ul class="p-0 m-0 list-none">
                    <li class="flex items-center py-2">
                      <div class="w-12 h-12 flex items-center justify-center rounded-full mr-4 shrink-0" :style="{ backgroundColor: 'var(--color-primary-surface)' }">
                        <i class="pi pi-id-card text-xl!" :style="{ color: 'var(--color-primary)' }"></i>
                      </div>
                      <span class="text-surface-900 leading-normal">
                        User #{{ user?.id || '—' }}
                        <span class="text-surface-700"> &middot; role: {{ user?.role?.replace('_', ' ') || '—' }}</span>
                      </span>
                    </li>
                  </ul>
                </AccordionContent>
              </AccordionPanel>
            </Accordion>
          </div>
        </div>
      </div>
    </Transition>

    <template #fallback>
      <div class="grid grid-cols-12 gap-8">
        <div v-for="i in 4" :key="i" class="col-span-12 lg:col-span-6 xl:col-span-3">
          <div class="card">
            <div class="flex items-center gap-3">
              <Skeleton shape="circle" size="3rem" />
              <div class="flex-1 space-y-2">
                <Skeleton width="60%" height="1rem" />
                <Skeleton width="40%" height="0.75rem" />
              </div>
            </div>
          </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <Skeleton width="40%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 4" :key="i" width="100%" height="3rem" />
            </div>
          </div>
        </div>

        <div class="col-span-12 xl:col-span-6">
          <div class="card">
            <Skeleton width="40%" height="1.5rem" class="mb-4" />
            <div class="space-y-3">
              <Skeleton v-for="i in 3" :key="i" width="100%" height="3rem" />
            </div>
          </div>
        </div>
      </div>
    </template>
  </Deferred>
</template>

<style scoped>
.page-enter-active {
  transition: opacity 0.2s ease;
  transition-timing-function: cubic-bezier(0.16, 1, 0.3, 1);
}
.page-enter-from {
  opacity: 0;
}
</style>