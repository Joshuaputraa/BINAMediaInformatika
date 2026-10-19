<script setup>
import Modal from './Modal.vue'

defineProps({
    title: String,
    processing: Boolean,
    error: String,
    confirmLabel: { type: String, default: 'Hapus' },
    tone: { type: String, default: 'danger' },
})
defineEmits(['close', 'confirm'])
</script>

<template>
    <Modal :title="title" @close="$emit('close')">
        <div class="text-sm text-gray-600"><slot /></div>
        <p v-if="error" class="text-sm text-red-600 mt-3">{{ error }}</p>
        <div class="flex justify-end gap-2 pt-5">
            <button type="button" @click="$emit('close')" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Batal</button>
            <button type="button" :disabled="processing" @click="$emit('confirm')"
                class="text-white text-sm px-4 py-2 rounded-md"
                :class="tone === 'danger' ? 'bg-red-600 hover:bg-red-700' : 'bg-blue-600 hover:bg-blue-700'">
                {{ confirmLabel }}
            </button>
        </div>
    </Modal>
</template>