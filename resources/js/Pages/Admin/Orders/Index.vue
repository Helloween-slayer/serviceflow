<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h1 class="text-2xl font-bold mb-4">Всі заявки</h1>

                    <!-- Фільтр за статусами -->
                    <div class="flex gap-4 mb-4">
                        <button
                            v-for="status in statuses"
                            :key="status.value"
                            @click="activeStatus = status.value"
                            class="px-4 py-2 rounded text-sm"
                            :class="activeStatus === status.value ? 'bg-blue-500 text-white' : 'bg-gray-200'"
                        >
                            {{ status.label }}
                        </button>
                    </div>

                    <!-- Таблиця -->
                    <div v-if="orders.data.length">
                        <table class="min-w-full border border-gray-200">
                            <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 border">ID</th>
                                <th class="px-4 py-2 border">Назва</th>
                                <th class="px-4 py-2 border">Клієнт</th>
                                <th class="px-4 py-2 border">Виконавець</th>
                                <th class="px-4 py-2 border">Статус</th>
                                <th class="px-4 py-2 border">Дії</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50">
                                <td class="px-4 py-2 border text-center">{{ order.id }}</td>
                                <td class="px-4 py-2 border">{{ order.title }}</td>
                                <td class="px-4 py-2 border">{{ order.client?.name || 'Невідомий' }}</td>
                                <td class="px-4 py-2 border">{{ order.worker?.name || 'Не призначено' }}</td>
                                <td class="px-4 py-2 border">
                                        <span :class="getStatusBadge(order.status)" class="text-xs px-3 py-1 rounded-full">
                                            {{ getStatusText(order.status) }}
                                        </span>
                                </td>
                                <td class="px-4 py-2 border text-center">
                                    <a :href="route('orders.show', order.id)" class="text-blue-500 hover:underline text-sm">
                                        Переглянути
                                    </a>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <!-- Пагінація -->
                        <div class="mt-4">
                            <p class="text-sm text-gray-600">
                                Показано {{ orders.from }}–{{ orders.to }} з {{ orders.total }} заявок
                            </p>
                        </div>
                    </div>

                    <div v-else class="text-center py-8 text-gray-500">
                        Немає заявок
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    orders: Object,
});

const activeStatus = ref('all');

watch(activeStatus, (newStatus) => {
    router.get('/admin/orders', { status: newStatus === 'all' ? '' : newStatus }, {
        preserveState: true,
        replace: true,
    });
});

const statuses = [
    { value: 'all', label: 'Всі' },
    { value: 'new', label: 'Нові' },
    { value: 'in_progress', label: 'В роботі' },
    { value: 'ready', label: 'Готові' },
    { value: 'completed', label: 'Завершені' },
    { value: 'cancelled', label: 'Скасовані' },
];

const getStatusText = (status) => {
    const map = {
        new: 'Нова',
        in_progress: 'В роботі',
        ready: 'Готова',
        completed: 'Завершена',
        cancelled: 'Скасована',
    };
    return map[status] || status;
};

const getStatusBadge = (status) => {
    const map = {
        new: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        ready: 'bg-purple-100 text-purple-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-800';
};
</script>
