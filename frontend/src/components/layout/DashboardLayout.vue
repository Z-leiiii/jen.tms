<script setup>
import { ref } from 'vue'
import Sidebar from '@/components/layout/Sidebar.vue'
import Navbar from '@/components/layout/Navbar.vue'

const sidebarOpen = ref(false)
</script>

<template>
  <div class="min-h-screen w-full flex bg-surface">
    <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <!-- Backdrop: only rendered on mobile while the drawer is open, so it
         never affects desktop layout or intercepts clicks there. -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-ink/30 z-30 md:hidden"
      @click="sidebarOpen = false"
    />

    <!-- min-w-0 is required here: without it, a flex child with wide
         content (long task titles, wide tables) will refuse to shrink
         and silently push the page into horizontal overflow on mobile. -->
    <div class="flex-1 min-w-0 flex flex-col">
      <Navbar @toggle-sidebar="sidebarOpen = !sidebarOpen" />

      <main class="flex-1 w-full max-w-full px-4 sm:px-6 py-6 overflow-x-hidden">
        <slot />
      </main>
    </div>
  </div>
</template>