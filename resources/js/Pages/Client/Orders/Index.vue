<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <!-- Заголовок -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">📋 Мої заявки</h1>
                    <p class="text-gray-600 mt-1">Управління вашими заявками</p>
                </div>
                <Link
                    :href="route('client.orders.create')"
                    class="inline-flex items-center gap-2 bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition"
                >
                    <span>➕</span>
                    Створити заявку
                </Link>
            </div>

            <!-- Вкладки -->
            <div class="flex gap-2 mb-6">
                <button
                    @click="setTab('active')"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        activeTab === 'active'
                            ? 'bg-blue-500 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                >
                    📌 Активні
                </button>
                <button
                    @click="setTab('completed')"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        activeTab === 'completed'
                            ? 'bg-blue-500 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                >
                    ✅ Завершені
                </button>
            </div>

            <!-- Список заявок -->
            <div v-if="orders.data.length" class="space-y-4">
                <div
                    v-for="order in orders.data"
                    :key="order.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <!-- Заголовок -->
                            <h2 class="text-lg font-semibold">
                                <Link
                                    :href="route('orders.show', order.id)"
                                    class="text-blue-600 hover:text-blue-800 hover:underline"
                                >
                                    {{ order.title }}
                                </Link>
                            </h2>

                            <!-- Описание -->
                            <p class="text-gray-600 mt-1 text-sm">
                                {{ order.description || 'Опис відсутній' }}
                            </p>

                            <!-- Теги -->
                            <div v-if="order.tags && order.tags.length" class="flex gap-1 mt-2 flex-wrap">
                                <Badge
                                    v-for="tag in order.tags"
                                    :key="tag.id"
                                    variant="gray"
                                >
                                    {{ tag.name }}
                                </Badge>
                            </div>

                            <!-- Инфо -->
                            <div class="flex gap-4 mt-2 text-sm text-gray-500">
                                <span>💰 {{ order.price ?? 'Договірна' }} ₴</span>
                                <span>👤 {{ order.client?.name || 'Невідомий' }}</span>
                                <span v-if="order.deadline">📅 {{ formatDate(order.deadline) }}</span>
                            </div>
                        </div>

                        <!-- Статус -->
                        <div class="ml-4 flex-shrink-0">
                            <Badge :variant="getStatusVariant(order.status)">
                                {{ getStatusText(order.status) }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Действия -->
                    <div class="mt-3 pt-3 border-t border-gray-100 flex gap-2">
                        <Link
                            :href="route('orders.show', order.id)"
                            class="text-sm text-blue-500 hover:text-blue-700"
                        >
                            Переглянути
                        </Link>

                        <Link
                            v-if="order.status === 'new'"
                            :href="route('client.orders.edit', order.id)"
                            class="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Редагувати
                        </Link>

                        <button
                            v-if="order.status === 'new'"
                            @click="deleteOrder(order.id)"
                            class="text-sm text-red-500 hover:text-red-700"
                        >
                            Видалити
                        </button>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <EmptyState v-else>
                <template #title>
                    {{ activeTab === 'active' ? 'У вас немає активних заявок' : 'У вас немає завершених заявок' }}
                </template>
                <template #description>
                    {{ activeTab === 'active' ? 'Створіть нову заявку, і вона з\'явиться тут' : 'Завершені заявки будуть відображатися тут' }}
                </template>
                <template #action v-if="activeTab === 'active'">
                    <Link :href="route('client.orders.create')">
                        <Button>➕ Створити заявку</Button>
                    </Link>
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
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    orders: Object,
    activeTab: String,
});

const activeTab = ref(props.activeTab || 'active');

// Переключение вкладок
const setTab = (tab) => {
    activeTab.value = tab;
    router.get(
        '/client/orders',
        { status: tab === 'active' ? 'active' : 'completed' },
        { preserveState: true, replace: true }
    );
};

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

// Удаление заявки
const deleteOrder = (orderId) => {
    if (!confirm('Ви впевнені, що хочете видалити цю заявку?')) {
        return;
    }

    router.delete(route('client.orders.destroy', orderId), {
        onSuccess: () => {
            // Страница обновится автоматически
        },
        onError: (errors) => {
            alert(errors.message || 'Не вдалося видалити заявку');
        }
    });
};
</script>
