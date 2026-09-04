<script setup>
import { ref, computed, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectStore } from '@/stores/projectStore'
import { taskService } from '@/services/taskService'
import NotificationsDropdown from '@/components/layout/NotificationsDropdown.vue'

defineEmits(['toggle-sidebar'])

const router = useRouter()
const projectStore = useProjectStore()

const query = ref('')
const results = ref({ projects: [], tasks: [] })
const searching = ref(false)
const showResults = ref(false)
let debounceTimer = null

async function runSearch() {
  const q = query.value.trim()
  if (q.length < 2) {
    results.value = { projects: [], tasks: [] }
    showResults.value = false
    return
  }

  showResults.value = true
  searching.value = true
  try {
    if (!projectStore.projects.length) await projectStore.fetchProjects()

    const matchedProjects = projectStore.projects.filter((p) =>
      p.name.toLowerCase().includes(q.toLowerCase())
    )

    const taskResults = await Promise.all(
      projectStore.projects.map((p) =>
        taskService.listForProject(p.id, { search: q }).then((res) => res.data.data.map((t) => ({ ...t, project: p })))
      )
    )

    results.value = {
      projects: matchedProjects.slice(0, 5),
      tasks: taskResults.flat().slice(0, 8),
    }
  } finally {
    searching.value = false
  }
}

function handleInput() {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(runSearch, 300)
}

function goToProject(id) {
  showResults.value = false
  query.value = ''
  router.push({ name: 'project-details', params: { id } })
}

const hasResults = computed(() => results.value.projects.length > 0 || results.value.tasks.length > 0)

onBeforeUnmount(() => clearTimeout(debounceTimer))
</script>

<template>
  <header class="sticky top-0 z-20 px-4 sm:px-6 pt-4">
    <div class="neo-raised-compact flex items-center gap-3 px-4 min-h-touch relative">
      <button
        class="md:hidden min-h-touch min-w-touch flex items-center justify-center flex-shrink-0 text-ink-soft"
        aria-label="Open menu"
        @click="$emit('toggle-sidebar')"
      >
        ☰
      </button>

      <div class="flex-1 min-w-0 relative">
        <input
          v-model="query"
          type="search"
          placeholder="Search tasks and projects"
          class="neo-field text-sm py-2"
          @input="handleInput"
          @focus="query.trim().length >= 2 && (showResults = true)"
        />

        <div
          v-if="showResults"
          class="neo-raised absolute left-0 right-0 top-[calc(100%+8px)] max-h-80 overflow-y-auto z-50 p-2"
        >
          <p v-if="searching" class="text-sm text-ink-soft px-3 py-3">Searching…</p>
          <p v-else-if="!hasResults" class="text-sm text-ink-soft px-3 py-3">No matches for "{{ query }}".</p>

          <template v-else>
            <p v-if="results.projects.length" class="text-[11px] font-bold text-ink-soft uppercase px-3 pt-2 pb-1">Projects</p>
            <button
              v-for="p in results.projects"
              :key="p.id"
              class="w-full text-left px-3 py-2 rounded-lg text-sm flex items-center gap-2 hover:neo-inset-sm"
              @click="goToProject(p.id)"
            >
              <span class="w-2 h-2 rounded-full flex-shrink-0" :style="{ backgroundColor: p.color }" />
              <span class="truncate">{{ p.name }}</span>
            </button>

            <p v-if="results.tasks.length" class="text-[11px] font-bold text-ink-soft uppercase px-3 pt-3 pb-1">Tasks</p>
            <button
              v-for="t in results.tasks"
              :key="t.id"
              class="w-full text-left px-3 py-2 rounded-lg text-sm flex flex-col hover:neo-inset-sm"
              @click="goToProject(t.project.id)"
            >
              <span class="truncate font-medium">{{ t.title }}</span>
              <span class="text-xs text-ink-soft truncate">in {{ t.project.name }}</span>
            </button>
          </template>
        </div>
      </div>

      <NotificationsDropdown />
    </div>
  </header>
</template>