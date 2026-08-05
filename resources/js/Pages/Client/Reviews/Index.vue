<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Заголовок -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">⭐ Мої відгуки</h1>
                <p class="text-gray-600 mt-1">Відгуки, які ви залишили виконавцям</p>
            </div>

            <!-- Статистика -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <h3 class="text-sm font-medium text-blue-800">Всього відгуків</h3>
                    <p class="text-2xl font-bold text-blue-900">
                        {{ totalReviews }}
                    </p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <h3 class="text-sm font-medium text-purple-800">Середня оцінка</h3>
                    <p class="text-2xl font-bold text-purple-900">
                        {{ averageRating }} ⭐
                    </p>
                </div>
            </div>

            <!-- Список отзывов -->
            <div v-if="reviews.data.length > 0" class="space-y-4">
                <div
                    v-for="review in reviews.data"
                    :key="review.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-5"
                >
                    <div class="flex justify-between items-start">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900">
                                    {{ review.worker?.name || 'Невідомий виконавець' }}
                                </span>
                                <span class="text-yellow-400 text-lg">
                                    {{ '⭐'.repeat(review.rating) }}
                                </span>
                            </div>
                            <p class="text-gray-600 mt-2 text-sm">
                                {{ review.comment || 'Без коментаря' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-2">
                                Заявка: {{ review.order?.title || 'Не вказано' }}
                            </p>
                        </div>
                        <span class="text-xs text-gray-400">
                            {{ formatDate(review.created_at) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <EmptyState v-else>
                <template #title>У вас немає відгуків</template>
                <template #description>
                    Залиште відгук після завершення заявки
                </template>
                <template #action>
                    <Link :href="route('client.orders.index')">
                        <Button variant="primary">📋 Мої заявки</Button>
                    </Link>
                </template>
            </EmptyState>

            <!-- Пагинация -->
            <div v-if="reviews.data.length > 0" class="mt-6">
                <Pagination :pagination="reviews" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    reviews: Object,
    totalReviews: Number,
});

const averageRating = computed(() => {
    if (props.reviews.data.length === 0) return 0;
    const total = props.reviews.data.reduce((sum, r) => sum + r.rating, 0);
    return (total / props.reviews.data.length).toFixed(1);
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uk-UA');
};
</script>
