<script setup>
defineProps({ title: String, subtitle: String })
</script>

<template>
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">{{ title }}</h1>
            <p v-if="subtitle" class="text-sm text-gray-500 mt-1">{{ subtitle }}</p>
        </div>
        <div class="shrink-0"><slot /></div>
    </div>
</template>