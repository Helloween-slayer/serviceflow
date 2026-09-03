<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">👥 Исполнители</h1>
                <p class="text-gray-600 mt-1">Выберите исполнителя для вашей заявки</p>
            </div>

            <!-- Фильтры -->
            <div class="mb-6 flex flex-col md:flex-row gap-3">
                <Input
                    v-model="filters.search"
                    placeholder="Поиск по имени..."
                    class="flex-1"
                    @input="applyFilters"
                />

                <select
                    v-model="filters.tags"
                    class="w-full md:w-48 rounded-md border-gray-300 shadow-sm"
                    @change="applyFilters"
                >
                    <option value="">Все теги</option>
                    <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                        {{ tag.name }}
                    </option>
                </select>

                <select
                    v-model="filters.sort"
                    class="w-full md:w-48 rounded-md border-gray-300 shadow-sm"
                    @change="applyFilters"
                >
                    <option value="rating_desc">По рейтингу ↓</option>
                    <option value="rating_asc">По рейтингу ↑</option>
                    <option value="orders_desc">По количеству заказов</option>
                </select>

                <Button variant="secondary" @click="resetFilters">Сбросить</Button>
            </div>

            <!-- Список -->
            <div v-if="workers.data.length" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <Link
                    v-for="worker in workers.data"
                    :key="worker.id"
                    :href="route('worker.profile.show', worker.user_id)"
                    class="bg-white rounded-lg shadow-sm border border-gray-200 p-5 hover:shadow-md transition hover:border-blue-300"
                >
                    <div class="flex items-center gap-4 mb-3">
                        <!-- ✅ АВАТАР -->
                        <div class="w-16 h-16 rounded-full overflow-hidden bg-blue-500 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0">
                            <img
                                v-if="worker.avatar_url"
                                :src="worker.avatar_url"
                                :alt="worker.user?.name"
                                class="w-full h-full object-cover"
                            />
                            <span v-else>
                                {{ worker.user?.name?.charAt(0)?.toUpperCase() || '?' }}
                            </span>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">
                                {{ worker.user?.name || 'Не указано' }}
                            </h3>
                            <div class="flex items-center gap-1">
                                <span class="text-yellow-500">⭐</span>
                                <span class="text-sm text-gray-600">{{ worker.rating || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 line-clamp-2">{{ worker.bio || 'Нет описания' }}</p>

                    <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100 text-sm text-gray-500">
                        <span>✅ {{ worker.completed_orders || 0 }} заказов</span>
                        <span v-if="worker.is_verified" class="text-green-600">✅ Верифицирован</span>
                    </div>
                </Link>
            </div>

            <EmptyState v-else>
                <template #title>
                    {{ filters.search || filters.tags ? 'Исполнителей не найдено' : 'Исполнителей пока нет' }}
                </template>
                <template #description>
                    {{ filters.search || filters.tags ? 'Попробуйте изменить параметры поиска' : 'Зарегистрируйтесь как исполнитель' }}
                </template>
            </EmptyState>

            <Pagination v-if="workers.data.length" :pagination="workers" class="mt-6" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    workers: Object,
    tags: Array,
    filters: Object,
});

const filters = ref({
    search: props.filters.search || '',
    tags: props.filters.tags || '',
    sort: props.filters.sort || 'rating_desc',
});

const applyFilters = () => {
    router.get(route('workers.index'), filters.value, { preserveState: true });
};

const resetFilters = () => {
    filters.value = { search: '', tags: '', sort: 'rating_desc' };
    applyFilters();
};
</script>
