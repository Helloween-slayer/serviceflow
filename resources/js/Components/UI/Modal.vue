<template>
    <div v-if="show" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75" @click="close"></div>
            <div class="relative bg-white rounded-lg shadow-xl max-w-lg w-full p-6">
                <slot />
                <button class="absolute top-4 right-4 text-gray-400 hover:text-gray-600" @click="close">
                    ✕
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    show: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['close']);

const close = () => {
    emit('close');
};
</script>
