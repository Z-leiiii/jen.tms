<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useNotificationStore } from '@/stores/notificationStore'

const store = useNotificationStore()
const open = ref(false)
const rootEl = ref(null)

function toggle() {
  open.value = !open.value
  if (open.value) store.fetch()
}

function handleClickOutside(e) {
  if (rootEl.value && !rootEl.value.contains(e.target)) open.value = false
}

function timeAgo(dateStr) {
  const diffMs = Date.now() - new Date(dateStr).getTime()
  const mins = Math.floor(diffMs / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}

onMounted(() => {
  store.fetch({ unread_only: true }) // just to populate the badge count on load
  document.addEventListener('click', handleClickOutside)
})
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside))
</script>

<template>
  <div ref="rootEl" class="relative">
    <button
      class="min-h-touch min-w-touch flex items-center justify-center flex-shrink-0 text-ink-soft relative"
      aria-label="Notifications"
      @click="toggle"
    >
      🔔
      <span
        v-if="store.unreadCount > 0"
        class="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-coral"
      />
    </button>

    <!--
      Mobile: fixed full-width sheet under the navbar rather than a narrow
      dropdown that would clip against the right edge of a 375px screen.
      sm+: normal anchored dropdown.
    -->
    <div
      v-if="open"
      class="neo-raised fixed sm:absolute left-3 right-3 sm:left-auto sm:right-0 top-[72px] sm:top-auto sm:mt-2
        sm:w-80 max-h-[70vh] overflow-y-auto z-50 p-2"
    >
      <div class="flex items-center justify-between px-3 py-2">
        <p class="text-sm font-semibold">Notifications</p>
        <button
          v-if="store.unreadCount > 0"
          class="text-xs font-semibold text-indigo"
          @click="store.markAllRead()"
        >
          Mark all read
        </button>
      </div>

      <p v-if="store.loading" class="text-sm text-ink-soft px-3 py-4">Loading…</p>
      <p v-else-if="store.notifications.length === 0" class="text-sm text-ink-soft px-3 py-4">
        You're all caught up.
      </p>

      <button
        v-for="n in store.notifications"
        :key="n.id"
        class="w-full text-left rounded-xl px-3 py-3 flex gap-2 items-start transition-shadow"
        :class="n.is_read ? '' : 'neo-inset-sm'"
        @click="store.markRead(n.id)"
      >
        <span class="w-1.5 h-1.5 rounded-full mt-1.5 flex-shrink-0" :class="n.is_read ? 'bg-transparent' : 'bg-indigo'" />
        <span class="min-w-0">
          <span class="block text-sm font-medium truncate">{{ n.title }}</span>
          <span class="block text-xs text-ink-soft line-clamp-2">{{ n.message }}</span>
          <span class="block text-[11px] text-ink-faint mt-1 font-mono">{{ timeAgo(n.created_at) }}</span>
        </span>
      </button>
    </div>
  </div>
</template>