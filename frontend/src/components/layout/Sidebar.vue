<script setup>
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/authStore'

defineProps({
  open: { type: Boolean, default: false },
})
const emit = defineEmits(['close'])

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const navItems = [
  { name: 'dashboard', label: 'Today', icon: '⚡' },
  { name: 'study', label: 'Study lab', icon: '🧠' },
  { name: 'projects', label: 'Projects', icon: '📁' },
  { name: 'calendar', label: 'Calendar', icon: '🗓️' },
  { name: 'reports', label: 'Progress', icon: '📈' },
]

function go(name) {
  router.push({ name })
  emit('close')
}

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <!--
    Mobile: fixed off-canvas drawer, slides in/out via translate.
    Desktop (md+): sticky in normal flow, always visible — md:translate-x-0
    unconditionally overrides the mobile transform at that breakpoint.
  -->
  <aside
    class="neo-raised fixed md:sticky top-0 md:top-4 left-0 z-40 h-screen md:h-[calc(100vh-2rem)]
      w-72 md:w-60 flex-shrink-0 m-0 md:ml-4 flex flex-col p-5 gap-1
      transition-transform duration-200 ease-in-out md:translate-x-0"
    :class="open ? 'translate-x-0' : '-translate-x-full'"
  >
    <div class="flex items-center justify-between mb-6 px-1">
      <span class="font-display text-lg font-bold text-ink">StudyDeck</span>
      <button
        class="md:hidden min-h-touch min-w-touch flex items-center justify-center rounded-lg text-ink-soft"
        aria-label="Close menu"
        @click="emit('close')"
      >
        ✕
      </button>
    </div>

    <nav class="flex flex-col gap-1 flex-1 overflow-y-auto">
      <button
        v-for="item in navItems"
        :key="item.name"
        class="flex items-center gap-3 rounded-xl px-4 min-h-touch text-sm font-medium text-left transition-shadow"
        :class="route.name === item.name ? 'neo-inset-sm text-indigo' : 'text-ink-soft'"
        @click="go(item.name)"
      >
        <span aria-hidden="true">{{ item.icon }}</span>
        <span class="truncate">{{ item.label }}</span>
      </button>
    </nav>

    <div class="border-t border-shadowdark/40 pt-3 mt-3">
      <div class="flex items-center gap-3 px-2 mb-3 min-w-0">
        <div class="neo-raised-compact w-9 h-9 rounded-full flex items-center justify-center text-sm font-semibold text-indigo flex-shrink-0">
          {{ (auth.user?.name || '?').charAt(0).toUpperCase() }}
        </div>
        <div class="min-w-0">
          <p class="text-sm font-semibold truncate">{{ auth.user?.name }}</p>
          <p class="text-xs text-ink-soft truncate">{{ auth.user?.email }}</p>
        </div>
      </div>
      <button class="neo-btn w-full text-sm" @click="handleLogout">Log out</button>
    </div>
  </aside>
</template>