<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto px-4 py-8">
            <!-- Кнопка назад -->
            <div class="mb-6">
                <Link :href="route('orders.index')" class="text-blue-500 hover:underline inline-flex items-center gap-1">
                    ← Назад до списку
                </Link>
            </div>

            <!-- Шапка профиля -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <!-- Аватар -->
                    <div class="flex-shrink-0">
                        <div class="w-28 h-28 rounded-full overflow-hidden bg-gray-100 border-4 border-gray-100 shadow-inner">
                            <img
                                v-if="profile.avatar_url"
                                :src="profile.avatar_url"
                                :alt="profile.user?.name || 'Користувач'"
                                class="w-full h-full object-cover"
                                @error="handleAvatarError"
                            />
                            <div v-else class="w-full h-full flex items-center justify-center text-5xl text-gray-300 bg-gray-50">
                                {{ getInitials(profile.user?.name || '') }}
                            </div>
                        </div>
                    </div>

                    <!-- Информация -->
                    <div class="flex-1 text-center md:text-left w-full">
                        <h1 class="text-3xl font-bold text-gray-900">{{ profile.user?.name || 'Користувач' }}</h1>

                        <div class="flex flex-wrap justify-center md:justify-start items-center gap-2 mt-2">
                            <Badge variant="success">✅ Активний</Badge>
                            <span v-if="profile.company" class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                                🏢 {{ profile.company }}
                            </span>
                            <span v-if="profile.location" class="text-sm text-gray-600 bg-gray-100 px-3 py-1 rounded-full">
                                📍 {{ profile.location }}
                            </span>
                        </div>

                        <div class="flex flex-wrap justify-center md:justify-start items-center gap-6 mt-4 text-sm">
                            <div class="flex items-center gap-1.5">
                                <span class="text-yellow-500 text-xl">⭐</span>
                                <span class="font-bold text-gray-900 text-lg">{{ averageRating.toFixed(1) }}</span>
                                <span class="text-gray-400">({{ stats.reviews_count || 0 }} відгуків)</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <span class="text-green-500 text-xl">📦</span>
                                <span class="font-bold text-gray-900 text-lg">{{ stats.completed_orders || 0 }}</span>
                                <span class="text-gray-400">завершених</span>
                            </div>
                        </div>
                    </div>

                    <!-- Кнопка редагування -->
                    <div v-if="canEdit" class="flex-shrink-0 md:self-center">
                        <Link :href="route('worker.profile.edit')">
                            <Button variant="primary" class="shadow-md hover:shadow-lg transition">
                                ✏️ Редагувати профіль
                            </Button>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Основная информация -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 items-stretch">
                <!-- О себе -->
                <div class="h-full">
                    <Card class="h-full flex flex-col">
                        <template #header>
                            <div class="flex items-center gap-2 text-blue-600">
                                <span class="text-xl">📝</span>
                                <span class="font-semibold">Про себе</span>
                            </div>
                        </template>
                        <div class="flex-1 space-y-4">
                            <div v-if="profile.bio">
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Біографія</h4>
                                <p class="text-gray-700 whitespace-pre-wrap">{{ profile.bio }}</p>
                            </div>
                            <div v-if="profile.skills">
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Навички</h4>
                                <p class="text-gray-700 whitespace-pre-wrap">{{ profile.skills }}</p>
                            </div>
                            <div v-if="profile.experience">
                                <h4 class="text-sm font-medium text-gray-500 mb-1">Досвід роботи</h4>
                                <p class="text-gray-700 whitespace-pre-wrap">{{ profile.experience }}</p>
                            </div>
                            <div v-if="!profile.bio && !profile.skills && !profile.experience" class="text-gray-400 text-center py-8">
                                Виконавець поки не заповнив інформацію про себе
                            </div>
                        </div>
                    </Card>
                </div>

                <!-- Контактная информация -->
                <div class="h-full">
                    <Card class="h-full flex flex-col">
                        <template #header>
                            <div class="flex items-center gap-2 text-purple-600">
                                <span class="text-xl">📞</span>
                                <span class="font-semibold">Контакти</span>
                            </div>
                        </template>
                        <div class="flex-1 space-y-4">
                            <div v-if="profile.phone" class="flex items-start gap-3">
                                <span class="text-gray-400 mt-0.5">📱</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Телефон</p>
                                    <p class="text-sm text-gray-600">{{ profile.phone }}</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="text-gray-400 mt-0.5">📧</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Email</p>
                                    <p class="text-sm text-gray-600">{{ profile.user?.email || 'Не вказано' }}</p>
                                </div>
                            </div>
                            <div v-if="profile.location" class="flex items-start gap-3">
                                <span class="text-gray-400 mt-0.5">📍</span>
                                <div>
                                    <p class="text-sm font-medium text-gray-700">Розташування</p>
                                    <p class="text-sm text-gray-600">{{ profile.location }}</p>
                                </div>
                            </div>
                            <div v-if="!profile.phone && !profile.location" class="text-gray-400 text-center py-8">
                                Контактна інформація не вказана
                            </div>
                        </div>
                    </Card>
                </div>
            </div>

            <!-- Портфолио -->
            <div v-if="portfolioFiles.length > 0" class="mb-6">
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-green-600">
                            <span class="text-xl">💼</span>
                            <span class="font-semibold">Портфоліо</span>
                            <span class="text-sm text-gray-400">({{ portfolioFiles.length }} файлів)</span>
                        </div>
                    </template>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div v-for="(file, index) in portfolioFiles" :key="index" class="group relative">
                            <div class="aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-50 hover:shadow-md transition">
                                <img
                                    v-if="isImage(file)"
                                    :src="file.url"
                                    :alt="'Портфоліо ' + (index + 1)"
                                    class="w-full h-full object-cover"
                                    @error="handlePortfolioError(index)"
                                />
                                <div v-else class="w-full h-full flex flex-col items-center justify-center p-4">
                                    <span class="text-4xl mb-2">📄</span>
                                    <span class="text-xs text-gray-500 text-center break-all">{{ getFileName(file.path) }}</span>
                                </div>
                                <a
                                    :href="file.url"
                                    target="_blank"
                                    class="absolute inset-0 flex items-center justify-center bg-black/0 group-hover:bg-black/40 transition"
                                >
                                    <span class="text-white opacity-0 group-hover:opacity-100 transition text-sm font-medium bg-black/60 px-3 py-1 rounded">
                                        🔍 Переглянути
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>

            <!-- Отзывы -->
            <div v-if="reviews.length > 0" class="mb-6">
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-yellow-600">
                            <span class="text-xl">⭐</span>
                            <span class="font-semibold">Відгуки</span>
                            <span class="text-sm text-gray-400">({{ reviews.length }})</span>
                        </div>
                    </template>
                    <div class="space-y-4">
                        <div v-for="review in reviews" :key="review.id" class="border-b border-gray-100 last:border-0 pb-4 last:pb-0">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center text-sm font-semibold text-gray-600">
                                    {{ getInitials(review.client?.name || 'Анонім') }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-gray-900">{{ review.client?.name || 'Анонімний клієнт' }}</span>
                                        <span class="text-sm text-yellow-500">{{ '⭐'.repeat(Math.round(review.rating)) }}</span>
                                        <span class="text-xs text-gray-400">{{ new Date(review.created_at).toLocaleDateString() }}</span>
                                    </div>
                                    <p class="text-gray-600 text-sm mt-1">{{ review.comment }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
    reviews: {
        type: Array,
        default: () => [],
    },
    stats: {
        type: Object,
        required: true,
    },
    canEdit: {
        type: Boolean,
        default: false,
    }
});

// Средний рейтинг
const averageRating = computed(() => {
    return props.stats.average_rating || 0;
});

// Портфолио с URL
const portfolioFiles = computed(() => {
    if (!props.profile.portfolio) return [];
    if (Array.isArray(props.profile.portfolio)) {
        return props.profile.portfolio.map((path, index) => ({
            path: path,
            url: props.profile.portfolio_urls?.[index] || path,
        }));
    }
    return [];
});

// Проверка, является ли файл изображением
const isImage = (file) => {
    const ext = file.path?.split('.').pop()?.toLowerCase() || '';
    return ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(ext);
};

// Инициалы
const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2);
};

// Получение имени файла
const getFileName = (path) => {
    if (!path) return '';
    return path.split('/').pop();
};

// Обработка ошибки загрузки аватара
const handleAvatarError = (event) => {
    event.target.style.display = 'none';
};

// Обработка ошибки загрузки портфолио
const handlePortfolioError = (index) => {
    // Можно заменить на плейсхолдер
};
</script>
