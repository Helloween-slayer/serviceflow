<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto">
            <!-- Приветствие -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">
                    👋 Вітаємо, {{ $page.props.auth.user.name }}!
                </h1>
                <p class="text-gray-600 mt-1">Це ваша панель виконавця</p>
            </div>

            <!-- Карточки -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Доступні заявки -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-green-600">
                            <span class="text-xl">📋</span>
                            <span class="font-semibold">Доступні заявки</span>
                        </div>
                    </template>
                    <p class="text-sm text-gray-600">Заявки, які можна взяти в роботу</p>
                    <Link
                        :href="route('orders.index')"
                        class="inline-block mt-3 text-sm text-green-600 font-medium hover:text-green-800 hover:underline"
                    >
                        Переглянути →
                    </Link>
                </Card>

                <!-- Мої заявки -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-blue-600">
                            <span class="text-xl">📋</span>
                            <span class="font-semibold">Мої заявки</span>
                        </div>
                    </template>
                    <p class="text-sm text-gray-600">Заявки, які ви взяли в роботу</p>
                    <Link
                        :href="route('worker.orders.index')"
                        class="inline-block mt-3 text-sm text-blue-600 font-medium hover:text-blue-800 hover:underline"
                    >
                        Переглянути →
                    </Link>
                </Card>

                <!-- Мої відгуки -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-purple-600">
                            <span class="text-xl">⭐</span>
                            <span class="font-semibold">Мої відгуки</span>
                        </div>
                    </template>
                    <p class="text-sm text-gray-600">
                        Відгуки, які ви отримали
                        <span v-if="reviewsCount > 0" class="font-medium text-purple-600">
                            ({{ reviewsCount }})
                        </span>
                    </p>
                    <Link
                        :href="route('worker.reviews.index')"
                        class="inline-block mt-3 text-sm text-purple-600 font-medium hover:text-purple-800 hover:underline"
                    >
                        Переглянути →
                    </Link>
                </Card>
            </div>

            <!-- 👇 ССЫЛКА НА ПРОФИЛЬ - ВЫНЕСЕНА ОТДЕЛЬНО -->
            <div class="mt-6 flex justify-end">
                <Link
                    :href="route('worker.profile.show', user.id)"
                    class="inline-flex items-center gap-2 text-sm text-blue-600 font-medium hover:text-blue-800 hover:underline bg-blue-50 px-4 py-2 rounded-lg transition"
                >
                    👤 Мій профіль
                    <span class="text-xs">→</span>
                </Link>
            </div>

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
import TelegramWidget from '@/Components/Dashboard/TelegramWidget.vue';

const { auth, reviewsCount } = usePage().props;
const user = auth.user;
</script>
