<script setup>
import { STATUS } from '../status'
defineProps({ status: String })
</script>

<template>
    <span class="px-2 py-0.5 rounded text-xs font-medium whitespace-nowrap" :class="STATUS[status].cls">
        {{ STATUS[status].label }}
    </span>
</template>