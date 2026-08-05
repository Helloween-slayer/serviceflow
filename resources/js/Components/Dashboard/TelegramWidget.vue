<!-- resources/js/Components/Dashboard/TelegramWidget.vue -->
<template>
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">🔔 Telegram сповіщення</h3>

        <!-- Если подключен -->
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
            <p class="text-gray-600 mb-3">Отримуйте сповіщення про зміну статусу заявок у Telegram</p>

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
                <summary class="text-sm font-medium text-gray-700 cursor-pointer hover:text-gray-900">Ввести ID вручну</summary>
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
                    <span class="font-mono">/start</span>
                </p>
            </details>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

const telegramBotLink = 'https://t.me/statusflow123_bot';
const telegramIdInput = ref('');
const connecting = ref(false);

const connectTelegram = () => {
    if (!telegramIdInput.value) {
        alert('Введіть Telegram ID');
        return;
    }
    connecting.value = true;
    router.post('/telegram/connect', { telegram_id: telegramIdInput.value }, {
        onSuccess: () => {
            connecting.value = false;
            telegramIdInput.value = '';
            router.reload();
        },
        onError: (errors) => {
            connecting.value = false;
            alert(errors?.telegram_id || errors?.message || 'Не вдалося підключити Telegram');
        }
    });
};

const disconnectTelegram = () => {
    if (!confirm('Ви впевнені, що хочете відключити Telegram?')) return;
    router.delete('/telegram/disconnect', {
        onSuccess: () => router.reload(),
        onError: (errors) => alert(errors?.message || 'Не вдалося відключити Telegram')
    });
};

const toggleNotifications = (event) => {
    const checked = event.target.checked;
    router.patch('/telegram/notifications', { telegram_notifications: checked }, {
        onSuccess: () => router.reload(),
        onError: (errors) => alert(errors?.message || 'Не вдалося змінити налаштування')
    });
};
</script>
