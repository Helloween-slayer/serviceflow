<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <!-- Заголовок -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">📋 Мої заявки</h1>
                    <p class="text-gray-600 mt-1">Заявки, які ви взяли в роботу</p>
                </div>
                <span class="text-sm text-gray-500">
                    Всього: {{ orders.total }}
                </span>
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
            <div v-if="filteredOrders.length > 0" class="space-y-4">
                <div
                    v-for="order in filteredOrders"
                    :key="order.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <!-- Назва -->
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ order.title }}
                            </h3>

                            <!-- Опис -->
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
                        </div>

                        <!-- Інформація справа -->
                        <div class="text-right ml-4 flex-shrink-0">
                            <p class="text-sm font-semibold text-gray-900">
                                {{ order.price ?? 'Договірна' }} ₴
                            </p>
                            <p class="text-sm text-gray-500 mt-1">
                                📅 {{ order.deadline ? formatDate(order.deadline) : 'Невідомий' }}
                            </p>
                            <p class="text-sm text-gray-500">
                                📅 {{ formatDate(order.created_at) }}
                            </p>
                            <!-- Статус -->
                            <div class="mt-2">
                                <Badge :variant="getStatusVariant(order.status)">
                                    {{ getStatusText(order.status) }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопки дій -->
                    <div class="mt-4 pt-4 border-t border-gray-100 flex gap-2">
                        <Link
                            :href="route('orders.show', order.id)"
                            class="text-sm text-blue-500 hover:text-blue-700"
                        >
                            Переглянути
                        </Link>

                        <button
                            v-if="order.status === 'in_progress'"
                            @click="completeOrder(order.id)"
                            class="text-sm text-green-500 hover:text-green-700"
                        >
                            Завершити
                        </button>

                        <button
                            v-if="order.status === 'in_progress'"
                            @click="cancelOrder(order.id)"
                            class="text-sm text-orange-500 hover:text-orange-700"
                        >
                            Скасувати
                        </button>
                    </div>
                </div>
            </div>

            <!-- Пустий стан -->
            <EmptyState v-else>
                <template #title>
                    {{ activeTab === 'active' ? 'У вас немає активних заявок' : 'У вас немає завершених заявок' }}
                </template>
                <template #description>
                    {{ activeTab === 'active' ? 'Ви ще не взяли жодної заявки в роботу' : 'Тут будуть відображатися завершені заявки' }}
                </template>
                <template #action v-if="activeTab === 'active'">
                    <Link :href="route('orders.index')">
                        <Button variant="primary">📋 Доступні заявки</Button>
                    </Link>
                </template>
            </EmptyState>

            <!-- Пагінація -->
            <div v-if="filteredOrders.length > 0" class="mt-6">
                <p class="text-sm text-gray-500">
                    Показано {{ filteredOrders.length }} з {{ orders.total }} заявок
                </p>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    orders: Object,
    activeTab: String,
});

const activeTab = ref(props.activeTab || 'active');

// Фільтрація заявок
const filteredOrders = computed(() => {
    const orders = props.orders.data || [];
    if (activeTab.value === 'active') {
        return orders.filter(order =>
            order.status === 'in_progress' || order.status === 'new'
        );
    } else {
        return orders.filter(order =>
            order.status === 'completed' || order.status === 'ready'
        );
    }
});

// Переключение вкладок
const setTab = (tab) => {
    activeTab.value = tab;
    router.get(
        '/worker/orders',
        { status: tab === 'active' ? 'active' : 'completed' },
        { preserveState: true, replace: true }
    );
};

// Форматирование даты
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uk-UA');
};

// Текст статусу
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

// Кольори статусів
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

// Завершити заявку
const completeOrder = (orderId) => {
    if (!confirm('Ви впевнені, що хочете завершити цю заявку?')) {
        return;
    }

    router.put(route('worker.orders.complete', orderId), {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            alert(errors?.message || 'Не вдалося завершити заявку');
        },
    });
};

// Скасувати заявку
const cancelOrder = (orderId) => {
    if (!confirm('Ви впевнені, що хочете скасувати виконання цієї заявки?')) {
        return;
    }

    router.put(route('worker.orders.cancel', orderId), {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            alert(errors?.message || 'Не вдалося скасувати заявку');
        },
    });
};
</script>
