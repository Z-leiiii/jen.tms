<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AuthLayout from '@/components/layout/AuthLayout.vue'
import DashboardLayout from '@/components/layout/DashboardLayout.vue'

const route = useRoute()

const layout = computed(() => (route.meta.layout === 'auth' ? AuthLayout : DashboardLayout))
</script>

<template>
  <component :is="layout">
    <router-view />
  </component>
</template>
