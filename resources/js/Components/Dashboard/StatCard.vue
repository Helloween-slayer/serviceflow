<template>
    <div :class="[
        'rounded-lg p-4 border',
        variant === 'blue' && 'bg-blue-50 border-blue-200',
        variant === 'yellow' && 'bg-yellow-50 border-yellow-200',
        variant === 'green' && 'bg-green-50 border-green-200',
        variant === 'purple' && 'bg-purple-50 border-purple-200',
        variant === 'red' && 'bg-red-50 border-red-200',
        variant === 'gray' && 'bg-gray-50 border-gray-200',
    ]">
        <h3 :class="[
            'text-sm font-medium',
            variant === 'blue' && 'text-blue-800',
            variant === 'yellow' && 'text-yellow-800',
            variant === 'green' && 'text-green-800',
            variant === 'purple' && 'text-purple-800',
            variant === 'red' && 'text-red-800',
            variant === 'gray' && 'text-gray-800',
        ]">
            {{ title }}
        </h3>
        <p :class="[
            'text-2xl font-bold mt-1',
            variant === 'blue' && 'text-blue-900',
            variant === 'yellow' && 'text-yellow-900',
            variant === 'green' && 'text-green-900',
            variant === 'purple' && 'text-purple-900',
            variant === 'red' && 'text-red-900',
            variant === 'gray' && 'text-gray-900',
        ]">
            {{ value }}
        </p>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    value: { type: [Number, String], required: true },
    variant: { type: String, default: 'blue' },
});
</script>
