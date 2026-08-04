<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Заголовок -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">Адмін-панель</h1>
                <p class="text-sm text-gray-600">Вітаємо, {{ user.name }}! Загальна статистика платформи.</p>
            </div>

            <!-- Картки статистики -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-blue-800">Всього заявок</h3>
                    <p class="text-2xl font-bold text-blue-900">{{ stats.totalOrders }}</p>
                </div>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-yellow-800">Активні заявки</h3>
                    <p class="text-2xl font-bold text-yellow-900">{{ stats.activeOrders }}</p>
                </div>
                <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-green-800">Завершені заявки</h3>
                    <p class="text-2xl font-bold text-green-900">{{ stats.completedOrders }}</p>
                </div>
                <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                    <h3 class="text-sm font-medium text-purple-800">Користувачів</h3>
                    <p class="text-2xl font-bold text-purple-900">{{ stats.totalUsers }}</p>
                </div>
            </div>

            <!-- Швидкі посилання -->
            <div class="flex flex-wrap gap-4 mb-6">
                <a :href="route('admin.orders.index')" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded text-sm">
                    📋 Всі заявки
                </a>
                <a :href="route('admin.users.index')" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded text-sm">
                    👥 Користувачі
                </a>
                <a :href="route('admin.tags.index')" class="bg-gray-100 hover:bg-gray-200 text-gray-800 px-4 py-2 rounded text-sm">
                    🏷️ Теги
                </a>
            </div>

            <!-- Останні заявки -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Останні заявки</h2>

                    <div v-if="recentOrders.length > 0" class="space-y-3">
                        <div
                            v-for="order in recentOrders"
                            :key="order.id"
                            class="flex justify-between items-center border-b border-gray-100 pb-2"
                        >
                            <div>
                                <p class="font-medium text-gray-800">{{ order.title }}</p>
                                <p class="text-sm text-gray-500">
                                    Клієнт: {{ order.client?.name || 'Невідомий' }} •
                                    {{ new Date(order.created_at).toLocaleDateString() }}
                                </p>
                            </div>
                            <div class="flex items-center gap-3">
                                <span :class="getStatusBadge(order.status)" class="text-xs px-3 py-1 rounded-full">
                                    {{ getStatusText(order.status) }}
                                </span>
                                <a :href="route('admin.orders.index')" class="text-blue-500 hover:underline text-sm">
                                    Переглянути
                                </a>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-gray-500 text-sm">Немає заявок</p>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 🔔 TELEGRAM ВИДЖЕТ -->
            <!-- ========================================== -->
            <div class="mt-8 bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">🔔 Telegram сповіщення</h3>

                    <!-- Если уже подключен -->
                    <div v-if="$page.props.auth.user.telegram_id" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <span class="text-green-600 font-medium">✅ Підключено</span>
                            <p class="text-sm text-gray-500 mt-1">ID: {{ $page.props.auth.user.telegram_id }}</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-4">
                            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                <input
                                    type="checkbox"
                                    :checked="$page.props.auth.user.telegram_notifications"
                                    @change="toggleNotifications($event)"
                                />
                                Сповіщення
                            </label>
                            <button
                                @click="disconnectTelegram"
                                class="text-sm text-red-500 hover:text-red-700"
                            >
                                Відключити
                            </button>
                        </div>
                    </div>

                    <!-- Если не подключен -->
                    <div v-else>
                        <p class="text-gray-600 mb-3">
                            Отримуйте сповіщення про зміну статусу заявок у Telegram
                        </p>

                        <div class="flex flex-col sm:flex-row gap-3 items-start">
                            <a
                                :href="telegramBotLink"
                                target="_blank"
                                class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
                            >
                                🔗 Написати боту
                            </a>
                            <span class="text-sm text-gray-500 self-center">
                                Напишіть <span class="font-mono">/start</span> та скопіюйте ID
                            </span>
                        </div>

                        <details class="mt-4" open>
                            <summary class="text-sm font-medium text-gray-700 cursor-pointer hover:text-gray-900">
                                Ввести ID вручну
                            </summary>
                            <form @submit.prevent="connectTelegram" class="mt-3 flex flex-col sm:flex-row gap-2">
                                <input
                                    v-model="telegramIdInput"
                                    type="text"
                                    placeholder="Ваш Telegram ID (наприклад, 123456789)"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm flex-1"
                                />
                                <button
                                    type="submit"
                                    :disabled="connecting"
                                    class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition disabled:opacity-50 text-sm"
                                >
                                    {{ connecting ? 'Збереження...' : 'Прив\'язати' }}
                                </button>
                            </form>
                            <p class="text-xs text-gray-400 mt-2">
                                💡 Напишіть
                                <a href="https://t.me/userinfobot" target="_blank" class="text-blue-500 hover:underline">@userinfobot</a>
                                або нашому боту
                                <span class="font-mono">/start</span>, щоб дізнатися свій Telegram ID
                            </p>
                        </details>
                    </div>
                </div>
            </div>
            <!-- ========================================== -->
            <!-- КОНЕЦ TELEGRAM ВИДЖЕТА -->
            <!-- ========================================== -->

        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const { stats, recentOrders } = usePage().props;

const user = computed(() => usePage().props.auth.user);

// Ссылка на бота
const telegramBotLink = 'https://t.me/statusflow123_bot';

// Поле для ручного ввода
const telegramIdInput = ref('');
const connecting = ref(false);

/**
 * Подключить Telegram (ручной ввод)
 */
const connectTelegram = () => {
    if (!telegramIdInput.value) {
        alert('Введіть Telegram ID');
        return;
    }

    connecting.value = true;

    router.post('/telegram/connect', {
        telegram_id: telegramIdInput.value
    }, {
        onSuccess: () => {
            connecting.value = false;
            telegramIdInput.value = '';
            router.reload();
        },
        onError: (errors) => {
            connecting.value = false;
            const message = errors?.telegram_id || errors?.message || 'Не вдалося підключити Telegram';
            alert(message);
        }
    });
};

/**
 * Отключить Telegram
 */
const disconnectTelegram = () => {
    if (!confirm('Ви впевнені, що хочете відключити Telegram?')) {
        return;
    }

    router.delete('/telegram/disconnect', {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            alert(errors?.message || 'Не вдалося відключити Telegram');
        }
    });
};

/**
 * Переключить уведомления
 */
const toggleNotifications = (event) => {
    const checked = event.target.checked;

    router.patch('/telegram/notifications', {
        telegram_notifications: checked
    }, {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            alert(errors?.message || 'Не вдалося змінити налаштування сповіщень');
        }
    });
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

const getStatusBadge = (status) => {
    const map = {
        new: 'bg-blue-100 text-blue-800',
        in_progress: 'bg-yellow-100 text-yellow-800',
        ready: 'bg-purple-100 text-purple-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800',
    };
    return map[status] || 'bg-gray-100 text-gray-800';
};
</script>
