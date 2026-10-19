<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { ChevronDown } from 'lucide-vue-next'

const props = defineProps({
    modelValue: [String, Number],
    options: { type: Array, default: () => [] }, // [{ value, label }]
    label: String,
    placeholder: { type: String, default: 'Pilih...' },
    error: String,
    required: Boolean,
    disabled: Boolean,
})
const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const query = ref('')
const root = ref(null)
const search = ref(null)

const selected = computed(() => props.options.find((o) => o.value === props.modelValue))
const filtered = computed(() => {
    const q = query.value.trim().toLowerCase()
    return q ? props.options.filter((o) => o.label.toLowerCase().includes(q)) : props.options
})

const toggle = () => {
    if (props.disabled) return
    open.value = !open.value
    query.value = ''
    if (open.value) nextTick(() => search.value?.focus())
}
const pick = (o) => {
    emit('update:modelValue', o.value)
    open.value = false
}

const onDocClick = (e) => {
    if (root.value && !root.value.contains(e.target)) open.value = false
}
onMounted(() => document.addEventListener('click', onDocClick))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick))
</script>

<template>
    <div ref="root" class="relative">
        <label class="block text-sm text-gray-600 mb-1">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>

        <button type="button" @click="toggle" :disabled="disabled"
            class="w-full flex items-center justify-between border border-gray-300 rounded-md px-3 py-2 text-sm bg-white text-left focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600 disabled:bg-gray-50 disabled:cursor-not-allowed">
            <span :class="selected ? 'text-gray-900' : 'text-gray-400'">{{ selected ? selected.label : placeholder }}</span>
            <ChevronDown class="w-4 h-4 text-gray-400" />
        </button>

        <div v-if="open" class="absolute z-10 mt-1 w-full bg-white border border-gray-200 rounded-md">
            <div class="p-2 border-b border-gray-100">
                <input ref="search" v-model="query" placeholder="Cari..."
                    class="w-full border border-gray-300 rounded px-2 py-1.5 text-sm focus:outline-none focus:border-blue-600" />
            </div>
            <ul class="max-h-48 overflow-y-auto py-1 text-sm">
                <li v-for="o in filtered" :key="o.value">
                    <button type="button" @click="pick(o)" class="w-full text-left px-3 py-2 hover:bg-gray-50"
                        :class="o.value === modelValue ? 'text-blue-700 font-medium' : ''">
                        {{ o.label }}
                    </button>
                </li>
                <li v-if="!filtered.length" class="px-3 py-2 text-gray-400">Tidak ditemukan.</li>
            </ul>
        </div>

        <p v-if="error" class="text-xs text-red-600 mt-1">{{ error }}</p>
    </div>
</template>