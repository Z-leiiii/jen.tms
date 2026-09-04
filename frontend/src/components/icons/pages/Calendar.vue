<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useProjectStore } from '@/stores/projectStore'
import { taskService } from '@/services/taskService'
import NeoCard from '@/components/ui/NeoCard.vue'
import NeoButton from '@/components/ui/NeoButton.vue'
import PriorityPill from '@/components/ui/PriorityPill.vue'

const router = useRouter()
const projectStore = useProjectStore()

const loading = ref(true)
const allTasks = ref([]) // flattened, each tagged with its project

const cursor = ref(new Date()) // first-of-month reference for the grid in view

async function loadAllDeadlines() {
  loading.value = true
  try {
    await projectStore.fetchProjects()
    const results = await Promise.all(
      projectStore.projects.map((project) =>
        taskService
          .listForProject(project.id, {})
          .then(({ data }) => data.data.map((t) => ({ ...t, project })))
      )
    )
    allTasks.value = results.flat().filter((t) => t.due_date)
  } finally {
    loading.value = false
  }
}

onMounted(loadAllDeadlines)

const monthLabel = computed(() =>
  cursor.value.toLocaleDateString(undefined, { month: 'long', year: 'numeric' })
)

// Sunday-start 6-week grid — simplest layout that never needs a variable
// row count, so the grid height (and therefore mobile scroll position)
// stays predictable month to month.
const gridDays = computed(() => {
  const year = cursor.value.getFullYear()
  const month = cursor.value.getMonth()
  const firstOfMonth = new Date(year, month, 1)
  const startOffset = firstOfMonth.getDay()
  const start = new Date(year, month, 1 - startOffset)

  return Array.from({ length: 42 }, (_, i) => {
    const date = new Date(start)
    date.setDate(start.getDate() + i)
    return date
  })
})

function tasksOn(date) {
  const key = date.toDateString()
  return allTasks.value.filter((t) => new Date(t.due_date).toDateString() === key)
}

function isCurrentMonth(date) {
  return date.getMonth() === cursor.value.getMonth()
}

function isToday(date) {
  return date.toDateString() === new Date().toDateString()
}

function shiftMonth(delta) {
  cursor.value = new Date(cursor.value.getFullYear(), cursor.value.getMonth() + delta, 1)
}

// Mobile agenda: only future-or-today deadlines, grouped by date, soonest first.
const agendaGroups = computed(() => {
  const today = new Date(); today.setHours(0, 0, 0, 0)
  const upcoming = allTasks.value
    .filter((t) => new Date(t.due_date) >= today)
    .sort((a, b) => new Date(a.due_date) - new Date(b.due_date))

  const groups = []
  for (const task of upcoming) {
    const key = new Date(task.due_date).toDateString()
    let group = groups.find((g) => g.key === key)
    if (!group) {
      group = { key, date: new Date(task.due_date), tasks: [] }
      groups.push(group)
    }
    group.tasks.push(task)
  }
  return groups
})

function goToTask(task) {
  router.push({ name: 'project-details', params: { id: task.project.id } })
}
</script>

<template>
  <div class="max-w-6xl mx-auto flex flex-col gap-6">
    <div class="flex items-center justify-between">
      <h1 class="text-xl sm:text-2xl font-bold">Calendar</h1>
    </div>

    <p v-if="loading" class="text-sm text-ink-soft">Loading deadlines…</p>

    <template v-else>
      <!-- Mobile: agenda list. A 7-column grid at 375px would force cells
           small enough that dots/pills become unreadable — an agenda
           avoids that distortion entirely rather than trying to shrink it. -->
      <div class="sm:hidden flex flex-col gap-4">
        <NeoCard v-if="agendaGroups.length === 0">
          <p class="text-sm text-ink-soft">No upcoming deadlines. You're all caught up.</p>
        </NeoCard>
        <NeoCard v-for="group in agendaGroups" :key="group.key" compact>
          <p class="text-xs font-bold text-ink-soft uppercase tracking-wide mb-3">
            {{ group.date.toLocaleDateString(undefined, { weekday: 'short', month: 'short', day: 'numeric' }) }}
          </p>
          <div class="flex flex-col gap-2">
            <button
              v-for="task in group.tasks"
              :key="task.id"
              class="flex items-center justify-between gap-2 text-left"
              @click="goToTask(task)"
            >
              <span class="text-sm font-medium truncate">{{ task.title }}</span>
              <PriorityPill :priority="task.priority" />
            </button>
          </div>
        </NeoCard>
      </div>

      <!-- sm+: full month grid -->
      <div class="hidden sm:block">
        <div class="flex items-center justify-between mb-4">
          <NeoButton class="!px-3" @click="shiftMonth(-1)">←</NeoButton>
          <h2 class="font-display font-semibold text-lg">{{ monthLabel }}</h2>
          <NeoButton class="!px-3" @click="shiftMonth(1)">→</NeoButton>
        </div>

        <div class="neo-inset p-3 lg:p-4">
          <div class="grid grid-cols-7 gap-2 mb-2">
            <div v-for="d in ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']" :key="d" class="text-center text-xs font-bold text-ink-soft py-1">
              {{ d }}
            </div>
          </div>
          <div class="grid grid-cols-7 gap-2">
            <div
              v-for="date in gridDays"
              :key="date.toISOString()"
              class="neo-raised-compact min-h-[90px] lg:min-h-[110px] p-2 flex flex-col gap-1"
              :class="!isCurrentMonth(date) ? 'opacity-40' : ''"
            >
              <span
                class="text-xs font-semibold w-5 h-5 flex items-center justify-center rounded-full flex-shrink-0"
                :class="isToday(date) ? 'bg-indigo text-white' : 'text-ink-soft'"
              >
                {{ date.getDate() }}
              </span>
              <button
                v-for="task in tasksOn(date).slice(0, 2)"
                :key="task.id"
                class="text-[11px] font-medium truncate text-left px-1.5 py-0.5 rounded-md"
                :class="{
                  'bg-coral-soft text-coral': task.priority === 'urgent',
                  'bg-amber-soft text-amber': task.priority === 'high' || task.priority === 'medium',
                  'bg-indigo-soft text-indigo': task.priority === 'low',
                }"
                @click="goToTask(task)"
              >
                {{ task.title }}
              </button>
              <span v-if="tasksOn(date).length > 2" class="text-[10px] text-ink-soft px-1.5">
                +{{ tasksOn(date).length - 2 }} more
              </span>
            </div>
          </div>
        </div>
      </div>
    </template>
  </div>
</template>