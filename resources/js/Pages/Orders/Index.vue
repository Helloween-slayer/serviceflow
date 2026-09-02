<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <!-- Заголовок -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">📋 Доступні заявки</h1>
                <p class="text-gray-600 mt-1">Заявки, які чекають на виконавця</p>
            </div>

            <!-- ============================================= -->
            <!-- ✅ ФИЛЬТРЫ -->
            <!-- ============================================= -->
            <div class="mb-6 flex flex-col md:flex-row gap-3">
                <!-- Поиск -->
                <div class="flex-1">
                    <Input
                        v-model="filters.search"
                        placeholder="Пошук за назвою або описом..."
                        @input="applyFilters"
                    />
                </div>

                <!-- Фильтр по тегам -->
                <div class="w-full md:w-48">
                    <select
                        v-model="filters.tag"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">Всі теги</option>
                        <option
                            v-for="tag in tags"
                            :key="tag.id"
                            :value="tag.id"
                        >
                            {{ tag.name }}
                        </option>
                    </select>
                </div>

                <!-- Сортировка -->
                <div class="w-full md:w-48">
                    <select
                        v-model="filters.sort"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="newest">Спочатку нові</option>
                        <option value="price_asc">Ціна: від низької</option>
                        <option value="price_desc">Ціна: від високої</option>
                    </select>
                </div>

                <Button variant="secondary" @click="resetFilters">
                    Скинути
                </Button>
            </div>

            <!-- Счетчик -->
            <div class="flex items-center gap-2 mb-4 text-sm text-gray-500">
                <span>Всього:</span>
                <span class="font-medium text-gray-700">{{ orders.total }}</span>
                <span>заявок</span>
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
                            <div class="flex gap-4 mt-2 text-sm text-gray-500 flex-wrap">
                                <span>💰 {{ order.price ?? 'Договірна' }} ₴</span>
                                <span>👤 {{ order.client?.name || 'Невідомий' }}</span>
                                <span v-if="order.deadline">📅 {{ formatDate(order.deadline) }}</span>
                            </div>
                        </div>

                        <!-- Статус + кнопка -->
                        <div class="ml-4 flex-shrink-0 text-right">
                            <Badge variant="green">
                                {{ getStatusText(order.status) }}
                            </Badge>

                            <Link
                                :href="route('orders.show', order.id)"
                                class="inline-block mt-2 text-sm text-blue-500 hover:text-blue-700"
                            >
                                Переглянути →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <EmptyState v-else>
                <template #title>
                    {{ filters.search || filters.tag ? 'Заявок не знайдено' : 'Заявок поки немає' }}
                </template>
                <template #description>
                    {{ filters.search || filters.tag ? 'Спробуйте змінити параметри пошуку' : 'Загляніть пізніше!' }}
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
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Input from '@/Components/UI/Input.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    orders: Object,
    tags: Array,
    filters: Object,
});

// =============================================
// ✅ СОСТОЯНИЕ ФИЛЬТРОВ
// =============================================
const filters = ref({
    search: props.filters?.search || '',
    tag: props.filters?.tag || '',
    sort: props.filters?.sort || 'newest',
});

// =============================================
// ✅ ПРИМЕНЕНИЕ ФИЛЬТРОВ
// =============================================
const applyFilters = () => {
    router.get(route('orders.index'), filters.value, { preserveState: true });
};

// =============================================
// ✅ СБРОС ФИЛЬТРОВ
// =============================================
const resetFilters = () => {
    filters.value = { search: '', tag: '', sort: 'newest' };
    applyFilters();
};

// =============================================
// ✅ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// =============================================
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uk-UA');
};

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
</script>
