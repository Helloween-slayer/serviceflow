<template>
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <!-- Заголовок чата -->
        <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">💬 Чат</h3>
            <p class="text-sm text-gray-500">Обговорення заявки</p>
        </div>

        <!-- Сообщения -->
        <div class="h-80 overflow-y-auto p-4 space-y-3" ref="messagesContainer">
            <div v-if="messages.length === 0" class="text-center text-gray-400 py-8">
                <p>Немає повідомлень</p>
                <p class="text-sm">Напишіть перше повідомлення</p>
            </div>

            <div
                v-for="message in messages"
                :key="message.id"
                class="flex"
                :class="message.user_id === userId ? 'justify-end' : 'justify-start'"
            >
                <div
                    class="max-w-[70%] rounded-lg px-4 py-2"
                    :class="message.user_id === userId
                        ? 'bg-blue-500 text-white'
                        : 'bg-gray-100 text-gray-800'"
                >
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-xs font-medium" :class="message.user_id === userId ? 'text-blue-100' : 'text-gray-500'">
                            {{ message.user_name }}
                        </span>
                        <span class="text-xs" :class="message.user_id === userId ? 'text-blue-200' : 'text-gray-400'">
                            {{ message.created_at }}
                        </span>
                    </div>
                    <p class="text-sm">{{ message.message }}</p>
                </div>
            </div>
        </div>

        <!-- Форма отправки -->
        <div class="border-t border-gray-200 p-3 bg-white">
            <form @submit.prevent="sendMessage" class="flex gap-2">
                <input
                    v-model="newMessage"
                    type="text"
                    placeholder="Напишіть повідомлення..."
                    class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    required
                />
                <Button type="submit" :loading="sending" variant="primary" size="sm">
                    📤 Відправити
                </Button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, nextTick, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    orderId: {
        type: Number,
        required: true,
    },
});

const userId = ref(null);
const messages = ref([]);
const newMessage = ref('');
const sending = ref(false);
const messagesContainer = ref(null);
const isListening = ref(false);

// Загрузить историю сообщений
const loadMessages = async () => {
    try {
        const response = await axios.get(`/orders/${props.orderId}/messages`);
        messages.value = response.data.data || [];
        await scrollToBottom();
    } catch (error) {
        console.error('Не вдалося завантажити повідомлення:', error);
    }
};

// Отправить сообщение
const sendMessage = async () => {
    if (!newMessage.value.trim()) return;

    sending.value = true;

    try {
        const response = await axios.post(`/orders/${props.orderId}/messages`, {
            message: newMessage.value,
        });

        newMessage.value = '';
        await loadMessages();
    } catch (error) {
        console.error('Не вдалося відправити повідомлення:', error);
        alert('Не вдалося відправити повідомлення');
    } finally {
        sending.value = false;
    }
};

// Прокрутка вниз
const scrollToBottom = async () => {
    await nextTick();
    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

// Получить текущего пользователя
const getCurrentUser = () => {
    const user = window._page?.props?.auth?.user;
    if (user) {
        userId.value = user.id;
    }
};

// Подключиться к вебсокетам
const listenForMessages = () => {
    if (isListening.value) return;

    try {
        const channel = window.echo?.channel(`order.${props.orderId}`);

        if (channel) {
            channel.listen('NewMessageEvent', (data) => {
                // Добавляем новое сообщение в список
                messages.value.push(data);
                scrollToBottom();
            });

            isListening.value = true;
            console.log('🔊 Слушаем канал order.' + props.orderId);
        } else {
            console.warn('⚠️ Echo не инициализирован');
        }
    } catch (error) {
        console.error('❌ Ошибка подключения к вебсокетам:', error);
    }
};

// Инициализация
onMounted(async () => {
    getCurrentUser();
    await loadMessages();
    listenForMessages();
});

// Следим за изменениями orderId
watch(() => props.orderId, async (newOrderId) => {
    if (newOrderId) {
        // Отписываемся от старого канала
        if (isListening.value) {
            window.echo?.leaveChannel(`order.${props.orderId}`);
            isListening.value = false;
        }
        await loadMessages();
        listenForMessages();
    }
});
</script>
