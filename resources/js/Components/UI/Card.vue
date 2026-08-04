<template>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div v-if="$slots.header" class="px-6 py-4 border-b border-gray-200 font-semibold">
            <slot name="header" />
        </div>
        <div class="p-6">
            <slot />
        </div>
    </div>
</template>
