<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { useProjectStore } from '@/stores/projectStore'
import { projectService } from '@/services/projectService'
import NeoCard from '@/components/ui/NeoCard.vue'
import NeoSelect from '@/components/ui/NeoSelect.vue'

const projectStore = useProjectStore()
const selectedProjectId = ref('')
const stats = ref(null)
const loading = ref(false)

const projectOptions = computed(() =>
  projectStore.projects.map((p) => ({ value: p.id, label: p.name }))
)

onMounted(async () => {
  await projectStore.fetchProjects()
  if (projectStore.projects.length) {
    selectedProjectId.value = projectStore.projects[0].id
  }
})

watch(selectedProjectId, async (id) => {
  if (!id) return
  loading.value = true
  try {
    const { data } = await projectService.statistics(id)
    stats.value = data.data
  } finally {
    loading.value = false
  }
}, { immediate: true })

// Chart palette pulled straight from the design tokens so charts don't
// introduce a second color language.
const palette = {
  todo: '#6B7395',
  in_progress: '#5B6EF5',
  review: '#F2994A',
  completed: '#2FB380',
  low: '#5B6EF5',
  medium: '#F2994A',
  high: '#F2994A',
  urgent: '#EF6461',
}

const statusChart = computed(() => {
  const byStatus = stats.value?.by_status || {}
  const labels = Object.keys(byStatus)
  return {
    series: Object.values(byStatus),
    options: {
      chart: { type: 'donut', fontFamily: 'Plus Jakarta Sans, sans-serif' },
      labels: labels.map((l) => l.replace('_', ' ')),
      colors: labels.map((l) => palette[l] || '#5B6EF5'),
      legend: { position: 'bottom', labels: { colors: '#303757' } },
      dataLabels: { enabled: false },
      stroke: { colors: ['#E9EDF5'] },
    },
  }
})

const priorityChart = computed(() => {
  const byPriority = stats.value?.by_priority || {}
  const labels = Object.keys(byPriority)
  return {
    series: [{ name: 'Tasks', data: Object.values(byPriority) }],
    options: {
      chart: { type: 'bar', toolbar: { show: false }, fontFamily: 'Plus Jakarta Sans, sans-serif' },
      plotOptions: { bar: { borderRadius: 8, columnWidth: '45%', distributed: true } },
      xaxis: {
        categories: labels,
        labels: { style: { colors: '#6B7395' } },
        axisBorder: { show: false },
        axisTicks: { show: false },
      },
      yaxis: { labels: { style: { colors: '#6B7395' } } },
      colors: labels.map((l) => palette[l] || '#5B6EF5'),
      legend: { show: false },
      grid: { borderColor: '#B7C1D6', strokeDashArray: 4 },
    },
  }
})
</script>

<template>
  <div class="max-w-6xl mx-auto flex flex-col gap-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
      <h1 class="text-xl sm:text-2xl font-bold">Reports</h1>
      <div class="w-full sm:w-64">
        <NeoSelect v-model="selectedProjectId" :options="projectOptions" placeholder="Choose a project" />
      </div>
    </div>

    <NeoCard v-if="!projectStore.projects.length">
      <p class="text-sm text-ink-soft">Create a project first to see reports here.</p>
    </NeoCard>

    <template v-else-if="stats">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <NeoCard compact>
          <p class="text-xs font-semibold text-ink-soft mb-2">Total tasks</p>
          <p class="text-2xl font-bold font-display">{{ stats.total_tasks }}</p>
        </NeoCard>
        <NeoCard compact>
          <p class="text-xs font-semibold text-ink-soft mb-2">Completed</p>
          <p class="text-2xl font-bold font-display text-emerald">{{ stats.completed_tasks }}</p>
        </NeoCard>
        <NeoCard compact>
          <p class="text-xs font-semibold text-ink-soft mb-2">Overdue</p>
          <p class="text-2xl font-bold font-display text-coral">{{ stats.overdue_tasks }}</p>
        </NeoCard>
      </div>

      <!-- Charts stack to a single column on mobile so neither one is
           squeezed below a readable width. -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
        <NeoCard>
          <p class="text-sm font-semibold mb-4">Tasks by status</p>
          <apexchart
            v-if="statusChart.series.length"
            type="donut"
            height="280"
            :options="statusChart.options"
            :series="statusChart.series"
          />
          <p v-else class="text-sm text-ink-soft">No tasks yet.</p>
        </NeoCard>

        <NeoCard>
          <p class="text-sm font-semibold mb-4">Tasks by priority</p>
          <apexchart
            v-if="priorityChart.series[0].data.length"
            type="bar"
            height="280"
            :options="priorityChart.options"
            :series="priorityChart.series"
          />
          <p v-else class="text-sm text-ink-soft">No tasks yet.</p>
        </NeoCard>
      </div>
    </template>

    <p v-else-if="loading" class="text-sm text-ink-soft">Loading report…</p>
  </div>
</template>