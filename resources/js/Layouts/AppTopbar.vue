<script setup>
import { computed } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import { useLayout } from './composables/layout'
import Avatar from 'primevue/avatar'

const { toggleMenu, toggleDarkMode, isDarkTheme } = useLayout()
const user = computed(() => usePage().props.auth?.user)
</script>

<template>
  <div class="layout-topbar">
    <div class="layout-topbar-logo-container">
      <button class="layout-menu-button layout-topbar-action" @click="toggleMenu">
        <i class="pi pi-bars"></i>
      </button>
      <Link href="/" class="layout-topbar-logo">
        <img src="/images/logo/alalay-logo.png" alt="ALALAY" class="h-8 w-auto">
        <span class="font-bold text-emerald-900">ALALAY</span>
      </Link>
    </div>

    <div class="layout-topbar-actions">
      <div class="layout-config-menu">
        <button type="button" class="layout-topbar-action" v-tooltip.left="'Toggle theme'" @click="toggleDarkMode">
          <i :class="['pi', { 'pi-moon': isDarkTheme, 'pi-sun': !isDarkTheme }]"></i>
        </button>
      </div>

      <div class="layout-topbar-user">
        <Avatar v-if="user?.profile_picture_url" :key="`${user?.profile_picture_url}?v=${user?.profile_picture_version}`" :image="`${user?.profile_picture_url}?v=${user?.profile_picture_version}`" size="normal" shape="circle" />
        <Avatar v-else :key="user?.id" :label="user?.first_name?.charAt(0)" size="normal" shape="circle" />
        <span class="layout-topbar-user-name">{{ user?.first_name }} {{ user?.last_name }}</span>
      </div>
    </div>
  </div>
</template>
