<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppMenuItem from './AppMenuItem.vue'
import { usePendingCounts } from '@/Composables/usePendingCounts'

const page = usePage()
const user = page.props.auth?.user
const role = user?.role

const { counts } = usePendingCounts()

const badgeLabelMap = {
  Applications: 'applications',
  Vouchers: 'vouchers',
  Cheques: 'cheques',
  Analytics: 'analytics',
}

const model = computed(() => {
  const roleRoutes = {
    admin: [
      { label: 'Analytics', icon: 'pi pi-fw pi-chart-bar', to: route('admin.analytics') },
      { label: 'Users', icon: 'pi pi-fw pi-users', to: route('admin.users.index') },
      { label: 'Audit Logs', icon: 'pi pi-fw pi-history', to: route('admin.audit-logs') },
      {
        label: 'Settings',
        icon: 'pi pi-fw pi-cog',
        path: '/settings',
        items: [
          { label: 'System Settings', icon: 'pi pi-fw pi-cog', to: route('admin.settings') },
          { label: 'Assistance Categories', icon: 'pi pi-fw pi-tags', to: route('admin.assistance-categories.index') },
          { label: 'Required Documents', icon: 'pi pi-fw pi-file', to: route('admin.required-documents.index') },
          { label: 'Code References', icon: 'pi pi-fw pi-qrcode', to: route('admin.assistance-code-references.index') },
        ],
      },
    ],
    aics_staff: [
      { label: 'Analytics', icon: 'pi pi-fw pi-chart-bar', to: route('aics.analytics') },
      { label: 'Applications', icon: 'pi pi-fw pi-file', to: route('aics.applications.index') },
      { label: 'Assistance Codes', icon: 'pi pi-fw pi-qrcode', to: route('aics.assistance-codes.index') },
    ],
    mswdo: [
      { label: 'Analytics', icon: 'pi pi-fw pi-chart-bar', to: route('mswdo.analytics') },
      { label: 'Applications', icon: 'pi pi-fw pi-file', to: route('mswdo.applications.index') },
      { label: 'Vouchers', icon: 'pi pi-fw pi-receipt', to: route('mswdo.vouchers.index') },
    ],
    accountant: [
      { label: 'Analytics', icon: 'pi pi-fw pi-chart-bar', to: route('accountant.analytics') },
      { label: 'Vouchers', icon: 'pi pi-fw pi-receipt', to: route('accountant.vouchers.index') },
    ],
    treasurer: [
      { label: 'Analytics', icon: 'pi pi-fw pi-chart-bar', to: route('treasurer.analytics') },
      { label: 'Cheques', icon: 'pi pi-fw pi-money-bill', to: route('treasurer.cheques.index') },
    ],
    mayors_office: [
      { label: 'Analytics', icon: 'pi pi-fw pi-chart-bar', to: route('mayors-office.analytics') },
    ],
  }

  const roleLabel = role === 'admin' ? 'Admin'
    : role === 'aics_staff' ? 'AICS'
    : role === 'mswdo' ? 'MSWDO'
    : role === 'accountant' ? 'Accountant'
    : role === 'treasurer' ? 'Treasurer'
    : role === 'mayors_office' ? "Mayor's Office"
    : 'Panel'

  const items = [
    {
      label: 'Home',
      items: [
        { label: 'Dashboard', icon: 'pi pi-fw pi-home', to: route('dashboard') },
        { label: 'Account Settings', icon: 'pi pi-fw pi-user-edit', to: route('account.edit') },
      ],
    },
  ]

  if (role && roleRoutes[role]) {
    const roleItems = roleRoutes[role].map(item => {
      const key = badgeLabelMap[item.label]
      if (key && counts.value[key]) {
        return { ...item, badge: counts.value[key] }
      }
      return item
    })

    items.push({
      label: roleLabel,
      items: roleItems,
    })
  }

  return items
})
</script>

<template>
  <ul class="layout-menu">
    <template v-for="(item, i) in model" :key="item.label + '-' + i">
      <app-menu-item v-if="!item.separator" :item="item" :index="i" />
      <li v-if="item.separator" class="menu-separator"></li>
    </template>
  </ul>
</template>
