<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Заголовок -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">📊 Адмін-панель</h1>
                <p class="text-gray-600 mt-1">Вітаємо, {{ user.name }}! Загальна статистика платформи.</p>
            </div>

            <!-- Картки статистики -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <StatCard
                    title="Всього заявок"
                    :value="stats.totalOrders"
                    variant="blue"
                />
                <StatCard
                    title="Активні заявки"
                    :value="stats.activeOrders"
                    variant="yellow"
                />
                <StatCard
                    title="Завершені заявки"
                    :value="stats.completedOrders"
                    variant="green"
                />
                <StatCard
                    title="Користувачів"
                    :value="stats.totalUsers"
                    variant="purple"
                />
            </div>

            <!-- Швидкі посилання -->
            <div class="flex flex-wrap gap-3 mb-6">
                <Link :href="route('admin.orders.index')">
                    <Button variant="secondary" size="sm">📋 Всі заявки</Button>
                </Link>
                <Link :href="route('admin.users.index')">
                    <Button variant="secondary" size="sm">👥 Користувачі</Button>
                </Link>
                <Link :href="route('admin.tags.index')">
                    <Button variant="secondary" size="sm">🏷️ Теги</Button>
                </Link>
            </div>

            <!-- Останні заявки -->
            <Card>
                <template #header>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-900">📋 Останні заявки</span>
                        <Link :href="route('admin.orders.index')" class="text-sm text-blue-500 hover:text-blue-700">
                            Всі заявки →
                        </Link>
                    </div>
                </template>

                <div v-if="recentOrders.length > 0" class="space-y-3">
                    <div
                        v-for="order in recentOrders"
                        :key="order.id"
                        class="flex justify-between items-center border-b border-gray-100 pb-3 last:border-0"
                    >
                        <div>
                            <p class="font-medium text-gray-800">{{ order.title }}</p>
                            <p class="text-sm text-gray-500">
                                Клієнт: {{ order.client?.name || 'Невідомий' }} •
                                {{ formatDate(order.created_at) }}
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Badge :variant="getStatusVariant(order.status)">
                                {{ getStatusText(order.status) }}
                            </Badge>
                            <Link
                                :href="route('orders.show', order.id)"
                                class="text-sm text-blue-500 hover:text-blue-700"
                            >
                                Переглянути
                            </Link>
                        </div>
                    </div>
                </div>

                <EmptyState v-else>
                    <template #title>Немає заявок</template>
                    <template #description>На платформі поки немає жодної заявки</template>
                </EmptyState>
            </Card>

            <!-- Telegram виджет -->
            <div class="mt-8">
                <TelegramWidget />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import StatCard from '@/Components/Dashboard/StatCard.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import TelegramWidget from '@/Components/Dashboard/TelegramWidget.vue';

const { stats, recentOrders } = usePage().props;

const user = computed(() => usePage().props.auth.user);

// Форматирование даты
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uk-UA');
};

// Статусы
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
</script>
