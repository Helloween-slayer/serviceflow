<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Профиль -->
            <Card>
                <template #header>
                    <div class="flex items-center gap-4">
                        <!-- Аватар -->
                        <div class="w-20 h-20 rounded-full bg-blue-500 flex items-center justify-center text-white text-3xl font-bold">
                            {{ profile.user.name.charAt(0).toUpperCase() }}
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ profile.user.name }}</h1>
                            <div class="flex items-center gap-3 mt-1">
                                <div class="flex items-center gap-1">
                                    <span v-for="i in 5" :key="i" class="text-lg">
                                        {{ i <= Math.round(stats.average_rating) ? '⭐' : '☆' }}
                                    </span>
                                    <span class="text-sm text-gray-600 ml-1">
                                        {{ stats.average_rating.toFixed(1) }}
                                    </span>
                                </div>
                                <span class="text-sm text-gray-500">
                                    • {{ stats.reviews_count }} відгуків
                                </span>
                                <span v-if="profile.is_verified" class="text-sm text-blue-600">
                                    ✅ Верифіковано
                                </span>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Информация -->
                <div class="space-y-6">
                    <!-- Био -->
                    <div v-if="profile.bio">
                        <h3 class="font-semibold text-gray-700 mb-1">Про себе</h3>
                        <p class="text-gray-600">{{ profile.bio }}</p>
                    </div>

                    <!-- Навыки -->
                    <div v-if="profile.skills">
                        <h3 class="font-semibold text-gray-700 mb-2">Навички</h3>
                        <div class="flex flex-wrap gap-2">
                            <Badge
                                v-for="skill in parseSkills(profile.skills)"
                                :key="skill"
                                variant="blue"
                            >
                                {{ skill }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Контакты -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-if="profile.company">
                            <h3 class="text-sm font-medium text-gray-500">Компанія</h3>
                            <p class="text-gray-800">{{ profile.company }}</p>
                        </div>
                        <div v-if="profile.location">
                            <h3 class="text-sm font-medium text-gray-500">Місто</h3>
                            <p class="text-gray-800">{{ profile.location }}</p>
                        </div>
                        <div v-if="profile.phone">
                            <h3 class="text-sm font-medium text-gray-500">Телефон</h3>
                            <p class="text-gray-800">{{ profile.phone }}</p>
                        </div>
                        <div>
                            <h3 class="text-sm font-medium text-gray-500">Виконано заявок</h3>
                            <p class="text-gray-800">{{ stats.completed_orders }}</p>
                        </div>
                    </div>
                </div>
            </Card>

            <!-- Отзывы -->
            <Card class="mt-6">
                <template #header>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-900">💬 Відгуки</span>
                        <span class="text-sm text-gray-500">{{ reviews.length }}</span>
                    </div>
                </template>

                <div v-if="reviews.length > 0" class="space-y-4">
                    <div
                        v-for="review in reviews"
                        :key="review.id"
                        class="border-b border-gray-100 pb-4 last:border-0"
                    >
                        <div class="flex items-center gap-2">
                            <span class="font-medium text-gray-800">{{ review.client.name }}</span>
                            <span class="text-yellow-500">{{ '⭐'.repeat(review.rating) }}</span>
                        </div>
                        <p class="text-gray-600 text-sm mt-1">{{ review.comment }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ formatDate(review.created_at) }}</p>
                    </div>
                </div>

                <EmptyState v-else>
                    <template #title>Немає відгуків</template>
                    <template #description>У цього виконавця поки немає відгуків</template>
                </EmptyState>
            </Card>

            <!-- Кнопка назад -->
            <div class="mt-6">
                <Link :href="route('orders.index')" class="text-sm text-gray-500 hover:text-gray-700">
                    ← Назад
                </Link>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';

const props = defineProps({
    profile: Object,
    reviews: Array,
    stats: Object,
});

const parseSkills = (skills) => {
    if (!skills) return [];
    if (Array.isArray(skills)) return skills;
    if (typeof skills === 'string') return skills.split(',').map(s => s.trim());
    return [];
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uk-UA');
};
</script>
