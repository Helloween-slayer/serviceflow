<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="[
            'px-4 py-2 rounded font-medium transition',
            variant === 'primary' && 'bg-blue-500 text-white hover:bg-blue-600',
            variant === 'secondary' && 'bg-gray-200 text-gray-800 hover:bg-gray-300',
            variant === 'danger' && 'bg-red-500 text-white hover:bg-red-600',
            variant === 'success' && 'bg-green-500 text-white hover:bg-green-600',
            variant === 'ghost' && 'bg-transparent text-gray-600 hover:bg-gray-100',
            disabled || loading ? 'opacity-50 cursor-not-allowed' : ''
        ]"
        @click="$emit('click')"
    >
        <span v-if="loading">⏳</span>
        <slot />
    </button>
</template>

<script setup>
defineProps({
    type: { type: String, default: 'button' },
    variant: { type: String, default: 'primary' },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
});

defineEmits(['click']);
</script>
