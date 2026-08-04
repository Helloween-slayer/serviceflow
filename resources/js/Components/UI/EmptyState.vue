<template>
    <div class="text-center py-12">
        <div class="text-4xl mb-4">📭</div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">
            <slot name="title">Немає даних</slot>
        </h3>
        <p class="text-sm text-gray-500">
            <slot name="description">Список поки що порожній</slot>
        </p>
        <div v-if="$slots.action" class="mt-4">
            <slot name="action" />
        </div>
    </div>
</template>
