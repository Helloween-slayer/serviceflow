<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Заголовок -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Адмін-панель</h1>
                <p class="text-sm text-gray-600">Вітаємо, {{ user.name }}! Загальна статистика платформи.</p>
            </div>

            <!-- Картки статистики -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-800">Всього заявок</h3>
                    <p class="text-2xl font-bold text-blue-900">{{ stats.totalOrders }}</p>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-yellow-800">Активні заявки</h3>
                    <p class="text-2xl font-bold text-yellow-900">{{ stats.activeOrders }}</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-green-800">Завершені заявки</h3>
                    <p class="text-2xl font-bold text-green-900">{{ stats.completedOrders }}</p>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-purple-800">Користувачів</h3>
                    <p class="text-2xl font-bold text-purple-900">{{ stats.totalUsers }}</p>
                </div>
            </div>

            <!-- Швидкі посилання -->
            <div class="flex flex-wrap gap-4 mb-6">
                <a :href="route('admin.orders.index')" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded text-sm">
                    📋 Всі заявки
                </a>
                <a href="#" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded text-sm">
                    👥 Користувачі
                </a>
                <a href="#" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded text-sm">
                    🏷️ Теги
                </a>
            </div>

            <!-- Останні заявки -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Останні заявки</h2>

                    <div v-if="recentOrders.length > 0" class="space-y-3">
                        <div
                            v-for="order in recentOrders"
                            :key="order.id"
                            class="flex justify-between items-center border-b border-gray-100 pb-2"
                        >
                            <div>
                                <p class="font-medium text-gray-800">{{ order.title }}</p>
                                <p class="text-sm text-gray-500">
                                    Клієнт: {{ order.client?.name || 'Невідомий' }} •
                                    {{ new Date(order.created_at).toLocaleDateString() }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="getStatusBadge(order.status)" class="text-xs px-3 py-1 rounded-full">
                                    {{ getStatusText(order.status) }}
                                </span>
                                <a :href="route('admin.orders.index')" class="text-blue-500 hover:underline text-sm">
                                    Переглянути
                                </a>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-gray-500 text-sm">Немає заявок</p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

const { stats, recentOrders } = usePage().props;

const user = computed(() => usePage().props.auth.user);

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
