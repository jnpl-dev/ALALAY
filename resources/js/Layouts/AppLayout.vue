<script setup>
import { useLayout } from './composables/layout'
import { computed, watch } from 'vue'

import { usePage } from '@inertiajs/vue3'
import AppFooter from './AppFooter.vue'
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'
import Toast from 'primevue/toast'
import ConfirmDialog from 'primevue/confirmdialog'
import { useToast } from '@/Composables/useToast'
import Breadcrumb from 'primevue/breadcrumb'
import { useBreadcrumbProvider } from '@/Composables/useBreadcrumb'

const { layoutConfig, layoutState, hideMobileMenu, applyPanelDarkMode } = useLayout()

applyPanelDarkMode()
const toast = useToast()
const page = usePage()

const breadcrumbItems = useBreadcrumbProvider()

const breadcrumbKey = computed(() => JSON.stringify(breadcrumbItems.value.map(i => i.label)))

watch(() => usePage().component, () => {
  breadcrumbItems.value = []
})

watch(() => page.props.flash, (flash) => {
  if (flash?.success) toast.success('Success', flash.success)
  if (flash?.error) toast.error('Error', flash.error)
}, { immediate: true })

const containerClass = computed(() => {
  return {
    'layout-overlay': layoutConfig.menuMode === 'overlay',
    'layout-static': layoutConfig.menuMode === 'static',
    'layout-overlay-active': layoutState.overlayMenuActive,
    'layout-mobile-active': layoutState.mobileMenuActive,
    'layout-static-inactive': layoutState.staticMenuInactive,
  }
})
</script>

<template>
  <div class="layout-wrapper" :class="containerClass">
    <AppTopbar />
    <AppSidebar />
    <div class="layout-main-container">
      <Breadcrumb :key="breadcrumbKey" :model="breadcrumbItems" class="px-6 pt-4 mb-2" />
      <div class="layout-main">
        <slot />
      </div>
      <AppFooter />
    </div>
    <div class="layout-mask animate-fadein" @click="hideMobileMenu" />
  </div>
  <Toast />
  <ConfirmDialog />
</template>
