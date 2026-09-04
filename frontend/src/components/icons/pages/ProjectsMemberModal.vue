<script setup>
import { ref, watch } from 'vue'
import { useProjectStore } from '@/stores/projectStore'
import { userService } from '@/services/userService'
import NeoModal from '@/components/ui/NeoModal.vue'
import NeoInput from '@/components/ui/NeoInput.vue'
import NeoSelect from '@/components/ui/NeoSelect.vue'
import NeoButton from '@/components/ui/NeoButton.vue'

const props = defineProps({
  open: { type: Boolean, default: false },
  project: { type: Object, default: null },
})
const emit = defineEmits(['close'])

const projectStore = useProjectStore()

const query = ref('')
const searchResults = ref([])
const searching = ref(false)
const selectedUser = ref(null)
const role = ref('member')
const errorMsg = ref('')
let debounceTimer = null

const roleOptions = [
  { value: 'admin', label: 'Admin' },
  { value: 'member', label: 'Member' },
  { value: 'viewer', label: 'Viewer' },
]

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      query.value = ''
      searchResults.value = []
      selectedUser.value = null
      role.value = 'member'
      errorMsg.value = ''
    }
  }
)

function handleSearchInput() {
  clearTimeout(debounceTimer)
  selectedUser.value = null
  debounceTimer = setTimeout(async () => {
    if (query.value.trim().length < 2) {
      searchResults.value = []
      return
    }
    searching.value = true
    try {
      const { data } = await userService.search(query.value.trim())
      searchResults.value = data.data
    } finally {
      searching.value = false
    }
  }, 300)
}

function pickUser(user) {
  selectedUser.value = user
  query.value = user.name
  searchResults.value = []
}

async function addMember() {
  if (!selectedUser.value) return
  errorMsg.value = ''
  try {
    await projectStore.addMember(props.project.id, { user_id: selectedUser.value.id, role: role.value })
    query.value = ''
    selectedUser.value = null
  } catch {
    errorMsg.value = "Couldn't add that member — they may already be on the project."
  }
}

async function removeMember(userId) {
  await projectStore.removeMember(props.project.id, userId)
}
</script>

<template>
  <NeoModal :open="open" title="Project members" @close="emit('close')">
    <div class="flex flex-col gap-5">
      <div>
        <span class="block text-xs font-semibold text-ink-soft mb-2">Add a member</span>
        <div class="relative">
          <NeoInput v-model="query" placeholder="Search by name or email" @input="handleSearchInput" />
          <div
            v-if="searchResults.length"
            class="neo-raised absolute left-0 right-0 top-[calc(100%+6px)] max-h-48 overflow-y-auto z-10 p-1.5"
          >
            <button
              v-for="user in searchResults"
              :key="user.id"
              class="w-full text-left px-3 py-2 rounded-lg text-sm hover:neo-inset-sm"
              @click="pickUser(user)"
            >
              <span class="font-medium">{{ user.name }}</span>
              <span class="text-ink-soft text-xs block">{{ user.email }}</span>
            </button>
          </div>
        </div>

        <div v-if="selectedUser" class="flex items-end gap-2 mt-3">
          <div class="flex-1">
            <NeoSelect v-model="role" label="Role" :options="roleOptions" />
          </div>
          <NeoButton variant="primary" @click="addMember">Add</NeoButton>
        </div>

        <p v-if="errorMsg" class="text-sm text-coral mt-2">{{ errorMsg }}</p>
      </div>

      <div>
        <span class="block text-xs font-semibold text-ink-soft mb-2">Current members</span>
        <div class="flex flex-col gap-2">
          <div
            v-for="member in project?.members || []"
            :key="member.id"
            class="flex items-center justify-between gap-2 neo-inset-sm px-3 py-2 rounded-xl"
          >
            <div class="min-w-0">
              <p class="text-sm font-medium truncate">{{ member.name }}</p>
              <p class="text-xs text-ink-soft truncate">{{ member.email }}</p>
            </div>
            <button
              v-if="member.id !== project.owner?.id"
              class="text-xs font-semibold text-coral flex-shrink-0 min-h-touch px-2"
              @click="removeMember(member.id)"
            >
              Remove
            </button>
            <span v-else class="text-xs text-ink-soft flex-shrink-0">Owner</span>
          </div>
        </div>
      </div>
    </div>
  </NeoModal>
</template>