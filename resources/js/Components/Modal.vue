<script setup>
defineProps({ title: String })
const emit = defineEmits(['close'])
</script>

<template>
    <div class="fixed inset-0 z-40 bg-black/30 flex items-center justify-center px-4" @click.self="emit('close')">
        <div class="bg-white rounded-lg w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-5">
                <h2 class="font-semibold text-gray-900">{{ title }}</h2>
                <button type="button" @click="emit('close')" class="text-gray-400 hover:text-gray-600 text-xl leading-none">&times;</button>
            </div>
            <slot />
        </div>
    </div>
</template>