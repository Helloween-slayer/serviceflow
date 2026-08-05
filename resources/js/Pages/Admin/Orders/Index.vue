<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Заголовок -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">📋 Всі заявки</h1>
                    <p class="text-gray-600 mt-1">Управління всіма заявками платформи</p>
                </div>
                <span class="text-sm text-gray-500">
                    Всього: {{ orders.total }}
                </span>
            </div>

            <!-- Фильтр по статусам -->
            <div class="flex flex-wrap gap-2 mb-6">
                <button
                    v-for="status in statuses"
                    :key="status.value"
                    @click="setStatus(status.value)"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        activeStatus === status.value
                            ? 'bg-blue-500 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                >
                    {{ status.label }}
                </button>
            </div>

            <!-- Таблица -->
            <div v-if="orders.data.length" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Назва
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Клієнт
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Виконавець
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Статус
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Дії
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ order.id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ order.title }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ order.client?.name || 'Невідомий' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ order.worker?.name || 'Не призначено' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <Badge :variant="getStatusVariant(order.status)">
                                {{ getStatusText(order.status) }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <Link
                                :href="route('orders.show', order.id)"
                                class="text-blue-500 hover:text-blue-700"
                            >
                                Переглянути
                            </Link>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Пустое состояние -->
            <EmptyState v-else>
                <template #title>Немає заявок</template>
                <template #description>
                    {{ activeStatus === 'all' ? 'На платформі поки немає жодної заявки' : `Немає заявок зі статусом "${getStatusLabel(activeStatus)}"` }}
                </template>
            </EmptyState>

            <!-- Пагинация -->
            <div v-if="orders.data.length" class="mt-6">
                <Pagination :pagination="orders" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    orders: Object,
});

const activeStatus = ref('all');

// Список статусов для фильтра
const statuses = [
    { value: 'all', label: '📋 Всі' },
    { value: 'new', label: '🆕 Нові' },
    { value: 'in_progress', label: '🔄 В роботі' },
    { value: 'ready', label: '✅ Готові' },
    { value: 'completed', label: '✔️ Завершені' },
    { value: 'cancelled', label: '❌ Скасовані' },
];

// Получить текст статуса
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

// Получить цвет статуса
const getStatusVariant = (status) => {
    const map = {
        new: 'blue',
        in_progress: 'yellow',
        ready: 'purple',
        completed: 'green',
        cancelled: 'red',
    };
    return map[status] || 'gray';
};

// Получить лейбл статуса для пустого состояния
const getStatusLabel = (status) => {
    const found = statuses.find(s => s.value === status);
    return found ? found.label.replace(/^[^\s]+\s/, '') : status;
};

// Установить статус фильтра
const setStatus = (status) => {
    activeStatus.value = status;
    router.get(
        '/admin/orders',
        { status: status === 'all' ? '' : status },
        { preserveState: true, replace: true }
    );
};
</script>
