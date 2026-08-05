<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Кнопка назад -->
            <div class="mb-6">
                <Link :href="route('orders.index')" class="text-blue-500 hover:underline inline-flex items-center gap-1">
                    ← Назад до списку
                </Link>
            </div>

            <!-- Заголовок -->
            <h1 class="text-2xl font-bold text-gray-900 mb-4">{{ order.title }}</h1>

            <!-- Статус -->
            <div class="mb-4">
                <Badge :variant="getStatusVariant(order.status)">
                    {{ getStatusText(order.status) }}
                </Badge>
            </div>

            <!-- Информация о заявке -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 bg-gray-50 rounded-lg p-6">
                <div>
                    <p class="text-gray-600">
                        <span class="font-semibold">Опис:</span>
                        {{ order.description || 'Опис відсутній' }}
                    </p>
                    <p class="text-gray-600 mt-2">
                        <span class="font-semibold">Ціна:</span>
                        {{ order.price ?? 'Договірна' }} ₴
                    </p>
                    <p class="text-gray-600 mt-2">
                        <span class="font-semibold">Дедлайн:</span>
                        {{ order.deadline ? new Date(order.deadline).toLocaleDateString() : 'Не вказано' }}
                    </p>
                </div>
                <div>
                    <p class="text-gray-600">
                        <span class="font-semibold">Клієнт:</span>
                        {{ order.client?.name || 'Невідомий' }}
                    </p>
                    <p class="text-gray-600 mt-2">
                        <span class="font-semibold">Email клієнта:</span>
                        {{ order.client?.email || 'Не вказано' }}
                    </p>
                    <p class="text-gray-600 mt-2">
                        <span class="font-semibold">Виконавець:</span>
                        {{ order.worker?.name || 'Не призначений' }}
                    </p>
                    <p class="text-gray-600 mt-2">
                        <span class="font-semibold">Створено:</span>
                        {{ new Date(order.created_at).toLocaleDateString() }}
                    </p>
                </div>
            </div>

            <!-- Теги -->
            <div v-if="order.tags && order.tags.length" class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-2">Теги:</h3>
                <div class="flex gap-2 flex-wrap">
                    <Badge v-for="tag in order.tags" :key="tag.id" variant="gray">
                        {{ tag.name }}
                    </Badge>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 👇 КНОПКИ С ПРАВИЛЬНЫМИ УСЛОВИЯМИ -->
            <!-- ========================================== -->
            <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-gray-200">
                <!-- Взять в работу (только воркер) -->
                <Button
                    v-if="canTakeOrder"
                    variant="success"
                    @click="takeOrder"
                    :loading="takingOrder"
                >
                    📋 Взяти в роботу
                </Button>

                <!-- Завершить (только воркер, который взял) -->
                <Button
                    v-if="canCompleteOrder"
                    variant="primary"
                    @click="completeOrder"
                    :loading="completingOrder"
                >
                    ✅ Завершити
                </Button>

                <!-- Отменить (только воркер, который взял) -->
                <Button
                    v-if="canCancelOrder"
                    variant="danger"
                    @click="cancelOrder"
                    :loading="cancellingOrder"
                >
                    ❌ Скасувати
                </Button>

                <!-- Редактировать (только клиент-владелец, статус new) -->
                <Link v-if="canEditOrder" :href="route('client.orders.edit', order.id)">
                    <Button variant="secondary">✏️ Редагувати</Button>
                </Link>

                <!-- Удалить (только клиент-владелец, статус new) -->
                <Button
                    v-if="canDeleteOrder"
                    variant="danger"
                    @click="confirmDelete"
                >
                    🗑️ Видалити
                </Button>
            </div>

            <!-- Сообщения -->
            <div v-if="errorMessage" class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ errorMessage }}
            </div>
            <div v-if="successMessage" class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ successMessage }}
            </div>
        </div>
    </AppLayout>

    <!-- Модалка подтверждения удаления -->
    <Modal :show="showDeleteModal" @close="closeDeleteModal">
        <div class="p-6">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-red-100 rounded-full">
                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <h3 class="mt-4 text-lg font-medium text-center text-gray-900">Підтвердження видалення</h3>
            <p class="mt-2 text-sm text-center text-gray-500">
                Ви впевнені, що хочете видалити заявку
                <span class="font-semibold text-gray-700">"{{ order.title }}"</span>?
            </p>
            <p class="mt-1 text-xs text-center text-red-500">⚠️ Цю дію не можна скасувати!</p>
            <div class="mt-6 flex justify-center gap-3">
                <Button variant="secondary" @click="closeDeleteModal">Скасувати</Button>
                <Button variant="danger" :loading="deleting" @click="deleteOrder">
                    Так, видалити
                </Button>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import Button from '@/Components/UI/Button.vue';
import Modal from '@/Components/UI/Modal.vue';

const props = defineProps({
    order: Object,
});

const { auth } = usePage().props;
const user = auth.user;

// ==============================================
// 👇 СОСТОЯНИЯ
// ==============================================

const showDeleteModal = ref(false);
const deleting = ref(false);
const takingOrder = ref(false);
const completingOrder = ref(false);
const cancellingOrder = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

// ==============================================
// 👇 ПРОВЕРКА ПРАВ (computed)
// ==============================================

// Может ли воркер взять заявку?
const canTakeOrder = computed(() => {
    return user?.role_id === 2
        && props.order.status === 'new'
        && props.order.worker_id === null;
});

// Может ли воркер завершить заявку?
const canCompleteOrder = computed(() => {
    return user?.role_id === 2
        && props.order.status === 'in_progress'
        && props.order.worker_id === user?.id;
});

// Может ли воркер отменить заявку?
const canCancelOrder = computed(() => {
    return user?.role_id === 2
        && props.order.status === 'in_progress'
        && props.order.worker_id === user?.id;
});

// Может ли клиент редактировать заявку?
const canEditOrder = computed(() => {
    return user?.role_id === 3
        && props.order.client_id === user?.id
        && props.order.status === 'new';
});

// Может ли клиент удалить заявку?
const canDeleteOrder = computed(() => {
    return user?.role_id === 3
        && props.order.client_id === user?.id
        && props.order.status === 'new';
});

// ==============================================
// 👇 ХЕЛПЕРЫ
// ==============================================

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

const getStatusVariant = (status) => {
    const map = {
        new: 'blue',
        in_progress: 'yellow',
        ready: 'purple',
        completed: 'green',
        cancelled: 'red',
    };
    return map[status] || 'gray';
};

// ==============================================
// 👇 МЕТОДЫ
// ==============================================

const takeOrder = () => {
    if (!confirm('Ви впевнені, що хочете взяти цю заявку в роботу?')) {
        return;
    }

    takingOrder.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    router.put(`/worker/orders/${props.order.id}/take`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            takingOrder.value = false;
            successMessage.value = 'Заявку успішно взято в роботу!';
            router.reload();
        },
        onError: (errors) => {
            takingOrder.value = false;
            errorMessage.value = errors?.message || 'Не вдалося взяти заявку. Спробуйте пізніше.';
        },
    });
};

const completeOrder = () => {
    if (!confirm('Ви впевнені, що хочете завершити цю заявку?')) {
        return;
    }

    completingOrder.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    router.put(`/worker/orders/${props.order.id}/complete`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            completingOrder.value = false;
            successMessage.value = 'Заявку успішно завершено!';
            router.reload();
        },
        onError: (errors) => {
            completingOrder.value = false;
            errorMessage.value = errors?.message || 'Не вдалося завершити заявку. Спробуйте пізніше.';
        },
    });
};

const cancelOrder = () => {
    if (!confirm('Ви впевнені, що хочете скасувати виконання цієї заявки?')) {
        return;
    }

    cancellingOrder.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    router.put(`/worker/orders/${props.order.id}/cancel`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            cancellingOrder.value = false;
            successMessage.value = 'Виконання заявки скасовано!';
            router.reload();
        },
        onError: (errors) => {
            cancellingOrder.value = false;
            errorMessage.value = errors?.message || 'Не вдалося скасувати заявку. Спробуйте пізніше.';
        },
    });
};

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
};

const deleteOrder = () => {
    deleting.value = true;
    errorMessage.value = '';
    successMessage.value = '';

    router.delete(route('client.orders.destroy', props.order.id), {
        preserveScroll: true,
        onSuccess: () => {
            deleting.value = false;
            showDeleteModal.value = false;
            router.visit(route('orders.index'));
        },
        onError: (errors) => {
            deleting.value = false;
            errorMessage.value = errors?.message || 'Не вдалося видалити заявку. Спробуйте пізніше.';
        },
    });
};
</script>
