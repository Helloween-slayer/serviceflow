<template>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
            <tr>
                <th v-for="column in columns" :key="column" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    {{ column }}
                </th>
            </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(row, index) in data" :key="index">
                <td v-for="column in columns" :key="column" class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                    <slot :name="`column-${column}`" :row="row">
                        {{ row[column] }}
                    </slot>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>

<script setup>
defineProps({
    columns: { type: Array, required: true },
    data: { type: Array, required: true },
});
</script>
