<script setup>
import { ref, watch, computed } from 'vue'
import { useTaskStore } from '@/stores/taskStore'
import { useAuthStore } from '@/stores/authStore'
import { commentService } from '@/services/commentService'
import { attachmentService } from '@/services/attachmentService'
import NeoModal from '@/components/ui/NeoModal.vue'
import NeoInput from '@/components/ui/NeoInput.vue'
import NeoSelect from '@/components/ui/NeoSelect.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  projectId: { type: String, required: true },
  members: { type: Array, default: () => [] }, // [{ id, name }]
  task: { type: Object, default: null }, // present when editing
})
const emit = defineEmits(['close'])

const taskStore = useTaskStore()
const auth = useAuthStore()

const priorityOptions = [
  { value: 'low', label: 'Low' },
  { value: 'medium', label: 'Medium' },
  { value: 'high', label: 'High' },
  { value: 'urgent', label: 'Urgent' },
]
const statusOptions = [
  { value: 'todo', label: 'To do' },
  { value: 'in_progress', label: 'In progress' },
  { value: 'review', label: 'Review' },
  { value: 'completed', label: 'Completed' },
]
const memberOptions = computed(() => props.members.map((m) => ({ value: m.id, label: m.name })))

const isEditing = computed(() => !!props.task)
const form = ref(blankForm())
const submitting = ref(false)
const errorMsg = ref('')

const tabs = computed(() => (isEditing.value ? ['Details', 'Comments', 'Files'] : ['Details']))
const activeTab = ref('Details')

function blankForm() {
  return { title: '', description: '', priority: 'medium', status: 'todo', due_date: '', assigned_to: '' }
}

watch(
  () => [props.open, props.task],
  ([isOpen, task]) => {
    if (!isOpen) return
    errorMsg.value = ''
    activeTab.value = 'Details'
    form.value = task
      ? {
          title: task.title,
          description: task.description || '',
          priority: task.priority,
          status: task.status,
          due_date: task.due_date || '',
          assigned_to: task.assignee?.id || '',
        }
      : blankForm()
    if (task) {
      loadComments()
      loadAttachments()
    }
  },
  { immediate: true }
)

async function handleSubmit() {
  submitting.value = true
  errorMsg.value = ''
  try {
    const payload = { ...form.value, assigned_to: form.value.assigned_to || null }
    if (isEditing.value) {
      await taskStore.updateTask(props.task.id, payload)
    } else {
      await taskStore.createTask({ ...payload, project_id: props.projectId })
    }
    emit('close')
  } catch {
    errorMsg.value = "Couldn't save the task. Check the form and try again."
  } finally {
    submitting.value = false
  }
}

async function handleDelete() {
  if (!props.task) return
  submitting.value = true
  try {
    await taskStore.deleteTask(props.task.id)
    emit('close')
  } finally {
    submitting.value = false
  }
}

// --- Comments ---
const comments = ref([])
const commentsLoading = ref(false)
const newComment = ref('')
const postingComment = ref(false)

async function loadComments() {
  commentsLoading.value = true
  try {
    const { data } = await commentService.list(props.task.id)
    comments.value = data.data
  } finally {
    commentsLoading.value = false
  }
}

async function postComment() {
  if (!newComment.value.trim()) return
  postingComment.value = true
  try {
    const { data } = await commentService.create(props.task.id, { comment: newComment.value.trim() })
    comments.value.unshift(data.data)
    newComment.value = ''
  } finally {
    postingComment.value = false
  }
}

async function deleteComment(id) {
  await commentService.delete(id)
  comments.value = comments.value.filter((c) => c.id !== id)
}

function timeAgo(dateStr) {
  const mins = Math.floor((Date.now() - new Date(dateStr).getTime()) / 60000)
  if (mins < 1) return 'just now'
  if (mins < 60) return `${mins}m ago`
  const hours = Math.floor(mins / 60)
  if (hours < 24) return `${hours}h ago`
  return `${Math.floor(hours / 24)}d ago`
}

// --- Attachments ---
const attachments = ref([])
const attachmentsLoading = ref(false)
const uploading = ref(false)
const uploadError = ref('')

async function loadAttachments() {
  attachmentsLoading.value = true
  try {
    const { data } = await attachmentService.list(props.task.id)
    attachments.value = data.data
  } finally {
    attachmentsLoading.value = false
  }
}

async function handleFileChange(e) {
  const file = e.target.files[0]
  if (!file) return
  uploading.value = true
  uploadError.value = ''
  try {
    const { data } = await attachmentService.upload(props.task.id, file)
    attachments.value.unshift(data.data)
  } catch {
    uploadError.value = "Couldn't upload that file — check the size and try again."
  } finally {
    uploading.value = false
    e.target.value = ''
  }
}

async function deleteAttachment(id) {
  await attachmentService.delete(id)
  attachments.value = attachments.value.filter((a) => a.id !== id)
}
</script>

<template>
  <NeoModal :open="open" :title="isEditing ? 'Edit task' : 'New task'" @close="$emit('close')">
    <!-- Tabs only appear once a task exists — comments/files need a task id. -->
    <div v-if="isEditing" class="flex gap-1 mb-5 neo-inset-sm p-1 rounded-xl">
      <button
        v-for="tab in tabs"
        :key="tab"
        type="button"
        class="flex-1 text-xs font-semibold py-2 rounded-lg transition-shadow"
        :class="activeTab === tab ? 'neo-raised-compact text-indigo' : 'text-ink-soft'"
        @click="activeTab = tab"
      >
        {{ tab }}
      </button>
    </div>

    <!-- Details tab -->
    <form v-if="activeTab === 'Details'" class="flex flex-col gap-4" @submit.prevent="handleSubmit">
      <NeoInput v-model="form.title" label="Title" placeholder="e.g. Draft literature review" />
      <NeoInput v-model="form.description" label="Description (optional)" placeholder="Any extra detail" />

      <div class="grid grid-cols-2 gap-3">
        <NeoSelect v-model="form.priority" label="Priority" :options="priorityOptions" />
        <NeoSelect v-model="form.status" label="Status" :options="statusOptions" />
      </div>

      <NeoInput v-model="form.due_date" type="date" label="Due date (optional)" />

      <NeoSelect
        v-if="memberOptions.length"
        v-model="form.assigned_to"
        label="Assign to (optional)"
        placeholder="Unassigned"
        :options="memberOptions"
      />

      <p v-if="errorMsg" class="text-sm text-coral">{{ errorMsg }}</p>

      <div class="flex gap-3">
        <NeoButton v-if="isEditing" type="button" class="text-coral" :disabled="submitting" @click="handleDelete">
          Delete
        </NeoButton>
        <NeoButton type="submit" variant="primary" block :disabled="submitting || !form.title">
          {{ submitting ? 'Saving…' : isEditing ? 'Save changes' : 'Create task' }}
        </NeoButton>
      </div>
    </form>

    <!-- Comments tab -->
    <div v-else-if="activeTab === 'Comments'" class="flex flex-col gap-4">
      <div class="flex gap-2">
        <input
          v-model="newComment"
          type="text"
          placeholder="Add a comment…"
          class="neo-field text-sm py-2"
          @keyup.enter="postComment"
        />
        <NeoButton variant="primary" :disabled="postingComment || !newComment.trim()" @click="postComment">
          Post
        </NeoButton>
      </div>

      <p v-if="commentsLoading" class="text-sm text-ink-soft">Loading comments…</p>
      <p v-else-if="comments.length === 0" class="text-sm text-ink-soft">No comments yet.</p>

      <div v-else class="flex flex-col gap-3 max-h-72 overflow-y-auto">
        <div v-for="c in comments" :key="c.id" class="neo-inset-sm rounded-xl p-3">
          <div class="flex items-center justify-between gap-2 mb-1">
            <span class="text-xs font-semibold">{{ c.user?.name }}</span>
            <span class="text-[11px] text-ink-faint font-mono flex-shrink-0">{{ timeAgo(c.created_at) }}</span>
          </div>
          <p class="text-sm">{{ c.comment }}</p>
          <button
            v-if="c.user?.id === auth.user?.id"
            class="text-[11px] font-semibold text-coral mt-1"
            @click="deleteComment(c.id)"
          >
            Delete
          </button>
        </div>
      </div>
    </div>

    <!-- Files tab -->
    <div v-else-if="activeTab === 'Files'" class="flex flex-col gap-4">
      <label class="neo-btn neo-btn-primary cursor-pointer w-full">
        {{ uploading ? 'Uploading…' : '+ Upload file' }}
        <input type="file" class="hidden" :disabled="uploading" @change="handleFileChange" />
      </label>
      <p v-if="uploadError" class="text-sm text-coral">{{ uploadError }}</p>

      <p v-if="attachmentsLoading" class="text-sm text-ink-soft">Loading files…</p>
      <p v-else-if="attachments.length === 0" class="text-sm text-ink-soft">No files attached yet.</p>

      <div v-else class="flex flex-col gap-2 max-h-72 overflow-y-auto">
        <div v-for="file in attachments" :key="file.id" class="neo-inset-sm rounded-xl p-3 flex items-center justify-between gap-2">
          <a :href="file.file_url" target="_blank" rel="noopener" class="text-sm font-medium truncate text-indigo">
            {{ file.filename }}
          </a>
          <button class="text-xs font-semibold text-coral flex-shrink-0" @click="deleteAttachment(file.id)">
            Delete
          </button>
        </div>
      </div>
    </div>
  </NeoModal>
</template>