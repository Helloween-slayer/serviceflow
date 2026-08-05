<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Заголовок -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">⭐ Всі відгуки</h1>
                    <p class="text-gray-600 mt-1">Управління відгуками користувачів</p>
                </div>
                <span class="text-sm text-gray-500">
                    Всього: {{ stats.total }}
                </span>
            </div>

            <!-- Статистика -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 text-center">
                    <h3 class="text-sm font-medium text-blue-800">Всього</h3>
                    <p class="text-2xl font-bold text-blue-900">{{ stats.total }}</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200 text-center">
                    <h3 class="text-sm font-medium text-purple-800">Середній</h3>
                    <p class="text-2xl font-bold text-purple-900">{{ stats.average }} ⭐</p>
                </div>
                <div
                    v-for="i in [5,4,3,2,1]"
                    :key="i"
                    class="bg-gray-50 p-4 rounded-lg border border-gray-200 text-center"
                >
                    <h3 class="text-sm font-medium text-gray-800">{{ i }} ⭐</h3>
                    <p class="text-2xl font-bold text-gray-900">{{ stats.ratings[i] }}</p>
                </div>
            </div>

            <!-- Фильтры -->
            <div class="mb-6 flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <Input
                        v-model="filters.search"
                        placeholder="Пошук за коментарем або ім'ям..."
                        @input="applyFilters"
                    />
                </div>
                <div class="w-full md:w-48">
                    <select
                        v-model="filters.rating"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        @change="applyFilters"
                    >
                        <option value="">Всі оцінки</option>
                        <option v-for="i in 5" :key="i" :value="i">{{ i }} ⭐</option>
                    </select>
                </div>
                <Button variant="secondary" @click="resetFilters">Скинути</Button>
            </div>

            <!-- Список отзывов -->
            <div v-if="reviews.data.length > 0" class="space-y-4">
                <div
                    v-for="review in reviews.data"
                    :key="review.id"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition"
                >
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-gray-900">
                                    {{ review.client?.name || 'Невідомий' }}
                                </span>
                                <span class="text-sm text-gray-500">→</span>
                                <span class="font-semibold text-gray-900">
                                    {{ review.worker?.name || 'Невідомий виконавець' }}
                                </span>
                                <span class="text-yellow-400">
                                    {{ '⭐'.repeat(review.rating) }}
                                </span>
                            </div>
                            <p class="text-gray-600 mt-2 text-sm">
                                {{ review.comment || 'Без коментаря' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                Заявка: {{ review.order?.title || 'Не вказано' }}
                            </p>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span class="text-xs text-gray-400">
                                {{ formatDate(review.created_at) }}
                            </span>
                            <button
                                @click="deleteReview(review.id)"
                                class="text-sm text-red-500 hover:text-red-700"
                            >
                                🗑️ Видалити
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Пустое состояние -->
            <EmptyState v-else>
                <template #title>Немає відгуків</template>
                <template #description>
                    {{ filters.search || filters.rating ? 'Спробуйте змінити параметри пошуку' : 'Поки що ніхто не залишив відгук' }}
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
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    reviews: Object,
    stats: Object,
    filters: Object,
});

const filters = ref({
    search: props.filters.search || '',
    rating: props.filters.rating || '',
});

const applyFilters = () => {
    router.get(
        route('admin.reviews.index'),
        filters.value,
        { preserveState: true }
    );
};

const resetFilters = () => {
    filters.value = { search: '', rating: '' };
    applyFilters();
};

const deleteReview = (reviewId) => {
    if (!confirm('Ви впевнені, що хочете видалити цей відгук?')) {
        return;
    }

    router.delete(route('admin.reviews.destroy', reviewId), {
        onSuccess: () => {
            // Страница обновится автоматически
        },
        onError: (errors) => {
            alert(errors.message || 'Не вдалося видалити відгук');
        },
    });
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uk-UA');
};
</script>
