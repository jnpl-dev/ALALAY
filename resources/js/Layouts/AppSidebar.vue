<script setup>
import { useLayout } from './composables/layout'
import { onBeforeUnmount, ref, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AppMenu from './AppMenu.vue'
import { useConfirm } from '@/Composables/useConfirm'

const { layoutState, isDesktop, hasOpenOverlay } = useLayout()
const page = usePage()
const confirm = useConfirm()
const sidebarRef = ref(null)
let outsideClickListener = null

const logout = () => {
  confirm.require({
    message: 'Are you sure you want to logout?',
    header: 'Logout',
    icon: 'pi pi-sign-out',
    rejectProps: { label: 'Cancel', outlined: true },
    acceptProps: { label: 'Logout', severity: 'danger' },
    accept: () => {
      document.documentElement.classList.remove('app-dark')
      router.post(route('logout'), { preserveState: true, preserveScroll: true })
    },
  })
}

watch(
  () => page.url,
  (newUrl) => {
    if (isDesktop()) layoutState.activePath = null
    else layoutState.activePath = newUrl

    layoutState.overlayMenuActive = false
    layoutState.mobileMenuActive = false
    layoutState.menuHoverActive = false
  },
  { immediate: true }
)

watch(hasOpenOverlay, (newVal) => {
  if (isDesktop()) {
    if (newVal) bindOutsideClickListener()
    else unbindOutsideClickListener()
  }
})

const bindOutsideClickListener = () => {
  if (!outsideClickListener) {
    outsideClickListener = (event) => {
      if (isOutsideClicked(event)) {
        layoutState.overlayMenuActive = false
      }
    }
    document.addEventListener('click', outsideClickListener)
  }
}

const unbindOutsideClickListener = () => {
  if (outsideClickListener) {
    document.removeEventListener('click', outsideClickListener)
    outsideClickListener = null
  }
}

const isOutsideClicked = (event) => {
  const topbarButtonEl = document.querySelector('.layout-menu-button')
  return !(sidebarRef.value.isSameNode(event.target) || sidebarRef.value.contains(event.target) || topbarButtonEl?.isSameNode(event.target) || topbarButtonEl?.contains(event.target))
}

onBeforeUnmount(() => {
  unbindOutsideClickListener()
})
</script>

<template>
  <div ref="sidebarRef" class="layout-sidebar">
    <AppMenu />
    <div class="layout-sidebar-footer">
      <button type="button" class="layout-sidebar-logout" @click="logout">
        <i class="pi pi-sign-out"></i>
        <span>Logout</span>
      </button>
    </div>
  </div>
</template>
