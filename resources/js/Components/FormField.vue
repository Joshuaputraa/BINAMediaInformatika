<script setup>
const props = defineProps({
    label: String,
    error: String,
    required: Boolean,
    numeric: Boolean,
    modelValue: [String, Number],
    type: { type: String, default: 'text' },
    placeholder: String,
})
const emit = defineEmits(['update:modelValue'])

const onInput = (e) => {
    let value = e.target.value

    if (props.numeric) {
        value = value.replace(/\D/g, '')
        e.target.value = value
    }

    emit('update:modelValue', value)
}
</script>

<template>
    <div>
        <label class="block text-sm text-gray-600 mb-1">
            {{ label }} <span v-if="required" class="text-red-500">*</span>
        </label>
        <input :type="type" :value="modelValue" :placeholder="placeholder"
            :inputmode="numeric ? 'numeric' : undefined" @input="onInput"
            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:border-blue-600 focus:ring-1 focus:ring-blue-600" />
        <p v-if="error" class="text-xs text-red-600 mt-1">{{ error }}</p>
    </div>
</template>