<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

defineProps({ canDelete: { type: Boolean, default: true } })
const emit = defineEmits(['edit', 'delete'])

const open = ref(false)
const btn = ref(null)
const pos = ref({ top: 0, right: 0 })

const toggle = () => {
    if (!open.value) {
        const r = btn.value.getBoundingClientRect()
        pos.value = { top: r.bottom + 4, right: window.innerWidth - r.right }
    }
    open.value = !open.value
}
const close = () => (open.value = false)
const pick = (action) => {
    close()
    emit(action)
}

onMounted(() => {
    window.addEventListener('scroll', close, true)
    window.addEventListener('resize', close)
})
onBeforeUnmount(() => {
    window.removeEventListener('scroll', close, true)
    window.removeEventListener('resize', close)
})
</script>

<template>
    <div class="inline-block">
        <button ref="btn" type="button" @click="toggle"
            class="inline-flex items-center gap-1 border border-gray-300 rounded-md px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
            Action <ChevronDown class="w-4 h-4" />
        </button>

        <Teleport to="body">
            <div v-if="open" class="fixed inset-0 z-40" @click="close"></div>
            <div v-if="open" class="fixed z-50 w-36 bg-white border border-gray-200 rounded-md py-1 text-sm"
                :style="{ top: pos.top + 'px', right: pos.right + 'px' }">
                <button type="button" @click="pick('edit')" class="w-full text-left px-3 py-2 hover:bg-gray-50">Edit</button>
                <button v-if="canDelete" type="button" @click="pick('delete')" class="w-full text-left px-3 py-2 text-red-600 hover:bg-red-50">Hapus</button>
            </div>
        </Teleport>
    </div>
</template>