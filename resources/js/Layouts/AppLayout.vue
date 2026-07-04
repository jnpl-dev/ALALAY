<script setup>
import { useLayout } from './composables/layout'
import { computed, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AppFooter from './AppFooter.vue'
import AppSidebar from './AppSidebar.vue'
import AppTopbar from './AppTopbar.vue'
import Toast from 'primevue/toast'
import { useToast } from '@/Composables/useToast'

const { layoutConfig, layoutState, hideMobileMenu } = useLayout()
const toast = useToast()
const page = usePage()

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
      <div class="layout-main">
        <slot />
      </div>
      <AppFooter />
    </div>
    <div class="layout-mask animate-fadein" @click="hideMobileMenu" />
  </div>
  <Toast />
</template>
