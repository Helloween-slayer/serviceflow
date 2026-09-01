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
                        <span v-if="order.worker">
                            <Link :href="route('worker.profile.show', order.worker.id)" class="text-blue-500 hover:underline">
                                {{ order.worker.name }}
                            </Link>
                        </span>
                        <span v-else>Не призначений</span>
                    </p>
                    <p class="text-gray-600 mt-2">
                        <span class="font-semibold">Створено:</span>
                        {{ new Date(order.created_at).toLocaleDateString() }}
                    </p>
                </div>
            </div>

            <!-- ============================================= -->
            <!-- ✅ ФОТО ЗАЯВКИ -->
            <!-- ============================================= -->
            <div v-if="safePhotos.length > 0" class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-3">📸 Фото заявки:</h3>
                <div class="flex flex-wrap gap-3">
                    <div
                        v-for="(photo, index) in safePhotos"
                        :key="'photo-' + index"
                        class="relative w-32 h-32 rounded-lg overflow-hidden border border-gray-200 hover:shadow-md transition cursor-pointer"
                        @click="openPhotoModal(index)"
                    >
                        <img
                            :src="getPhotoUrl(photo, index)"
                            :alt="'Фото ' + (index + 1)"
                            class="w-full h-full object-cover"
                            @error="handleImageError"
                        />
                        <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs text-center py-0.5 truncate px-1">
                            {{ getFileName(photo) }}
                        </div>
                        <div class="absolute top-1 right-1 bg-black/50 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-black/70 transition">
                            🔍
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================= -->
            <!-- ✅ ФАЙЛЫ ЗАЯВКИ -->
            <!-- ============================================= -->
            <div v-if="safeFiles.length > 0" class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-3">📎 Додаткові файли:</h3>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="(file, index) in safeFiles"
                        :key="'file-' + index"
                        class="flex items-center gap-2 bg-gray-100 px-4 py-2 rounded-lg border border-gray-200 hover:bg-gray-200 transition"
                    >
                        <span class="text-lg">📄</span>
                        <a
                            :href="getFileUrl(file, index)"
                            target="_blank"
                            class="text-sm text-blue-600 hover:underline"
                        >
                            {{ getFileName(file) }}
                        </a>
                        <span class="text-xs text-gray-400">|</span>
                        <a
                            :href="getFileUrl(file, index)"
                            target="_blank"
                            class="text-xs text-blue-500 hover:text-blue-700"
                            title="Завантажити"
                        >
                            ⬇️
                        </a>
                    </div>
                </div>
            </div>

            <!-- Теги -->
            <div v-if="order.tags && order.tags.length" class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-2">🏷️ Теги:</h3>
                <div class="flex gap-2 flex-wrap">
                    <Badge v-for="tag in order.tags" :key="tag.id" variant="gray">
                        {{ tag.name }}
                    </Badge>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- 👇 ЧАТ (только участники и админ) -->
            <!-- ========================================== -->
            <div v-if="canViewChat" class="mt-8">
                <Chat :order-id="order.id" />
            </div>

            <!-- ========================================== -->
            <!-- 👇 КНОПКИ -->
            <!-- ========================================== -->
            <div class="flex flex-wrap gap-3 mt-6 pt-6 border-t border-gray-200">
                <Button
                    v-if="canTakeOrder"
                    variant="success"
                    @click="takeOrder"
                    :loading="takingOrder"
                >
                    📋 Взяти в роботу
                </Button>

                <Button
                    v-if="canCompleteOrder"
                    variant="primary"
                    @click="completeOrder"
                    :loading="completingOrder"
                >
                    ✅ Завершити
                </Button>

                <Button
                    v-if="canCancelOrder"
                    variant="danger"
                    @click="cancelOrder"
                    :loading="cancellingOrder"
                >
                    ❌ Скасувати
                </Button>

                <Link v-if="canEditOrder" :href="route('client.orders.edit', order.id)">
                    <Button variant="secondary">✏️ Редагувати</Button>
                </Link>

                <Button
                    v-if="canDeleteOrder"
                    variant="danger"
                    @click="confirmDelete"
                >
                    🗑️ Видалити
                </Button>

                <Link v-if="canLeaveReview" :href="route('client.reviews.create', order.id)">
                    <Button variant="primary">⭐ Залишити відгук</Button>
                </Link>
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

    <!-- ============================================= -->
    <!-- ✅ МОДАЛКА ДЛЯ ПРОСМОТРА ФОТО -->
    <!-- ============================================= -->
    <Modal :show="photoModal.show" @close="closePhotoModal">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900">
                    Фото {{ photoModal.index + 1 }} з {{ safePhotos.length }}
                </h3>
                <button
                    @click="closePhotoModal"
                    class="text-gray-500 hover:text-gray-700 text-2xl"
                >
                    ✕
                </button>
            </div>
            <div class="flex justify-center">
                <img
                    :src="photoModal.url"
                    alt="Фото заявки"
                    class="max-w-full max-h-[70vh] object-contain rounded-lg"
                    @error="handleImageError"
                />
            </div>
            <div class="flex justify-center gap-4 mt-4">
                <button
                    @click="prevPhoto"
                    :disabled="photoModal.index === 0"
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50"
                >
                    ← Попереднє
                </button>
                <button
                    @click="nextPhoto"
                    :disabled="photoModal.index >= safePhotos.length - 1"
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 disabled:opacity-50"
                >
                    Наступне →
                </button>
            </div>
        </div>
    </Modal>

    <!-- ============================================= -->
    <!-- ✅ МОДАЛКА ПОДТВЕРЖДЕНИЯ УДАЛЕНИЯ -->
    <!-- ============================================= -->
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
import Chat from '@/Components/Chat.vue';

const props = defineProps({
    order: {
        type: Object,
        required: true,
    },
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

const photoModal = ref({
    show: false,
    index: 0,
    url: '',
});

// ==============================================
// 👇 БЕЗОПАСНЫЕ ВЫЧИСЛЯЕМЫЕ СВОЙСТВА ДЛЯ ФАЙЛОВ
// ==============================================

const safePhotos = computed(() => {
    if (!props.order.photos) return [];
    if (Array.isArray(props.order.photos)) return props.order.photos;
    if (typeof props.order.photos === 'string') {
        try {
            const parsed = JSON.parse(props.order.photos);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }
    return [];
});

const safeFiles = computed(() => {
    if (!props.order.files) return [];
    if (Array.isArray(props.order.files)) return props.order.files;
    if (typeof props.order.files === 'string') {
        try {
            const parsed = JSON.parse(props.order.files);
            return Array.isArray(parsed) ? parsed : [];
        } catch {
            return [];
        }
    }
    return [];
});

// ==============================================
// 👇 ПРОВЕРКА ПРАВ
// ==============================================

const canTakeOrder = computed(() => {
    return user?.role_id === 2
        && props.order.status === 'new'
        && props.order.worker_id === null;
});

const canCompleteOrder = computed(() => {
    return user?.role_id === 2
        && props.order.status === 'in_progress'
        && props.order.worker_id === user?.id;
});

const canCancelOrder = computed(() => {
    return user?.role_id === 2
        && props.order.status === 'in_progress'
        && props.order.worker_id === user?.id;
});

const canEditOrder = computed(() => {
    return user?.role_id === 3
        && props.order.client_id === user?.id
        && props.order.status === 'new';
});

const canDeleteOrder = computed(() => {
    return user?.role_id === 3
        && props.order.client_id === user?.id
        && props.order.status === 'new';
});

const canLeaveReview = computed(() => {
    return user?.role_id === 3
        && props.order.client_id === user?.id
        && props.order.status === 'completed'
        && !props.order.review;
});

// ==============================================
// 👇 ЧАТ
// ==============================================

const canViewChat = computed(() => {
    const userId = user?.id;
    const isAdmin = user?.role_id === 1;

    if (props.order.worker_id === null) {
        return false;
    }

    return props.order.client_id === userId
        || props.order.worker_id === userId
        || isAdmin;
});

// ==============================================
// 👇 ХЕЛПЕРЫ ДЛЯ ФАЙЛОВ
// ==============================================

const getFileName = (path) => {
    if (!path) return '';
    return path.split('/').pop();
};

const getPhotoUrl = (path, index) => {
    if (!path) return '';
    if (props.order.photos_urls && props.order.photos_urls[index]) {
        return props.order.photos_urls[index];
    }
    return path;
};

const getFileUrl = (path, index) => {
    if (!path) return '';
    if (props.order.files_urls && props.order.files_urls[index]) {
        return props.order.files_urls[index];
    }
    return path;
};

const handleImageError = (event) => {
    event.target.src = '/images/placeholder-image.png';
    event.target.onerror = null;
};

// ==============================================
// 👇 МОДАЛКА ДЛЯ ФОТО
// ==============================================

const openPhotoModal = (index) => {
    const photos = safePhotos.value;
    const urls = props.order.photos_urls || [];

    if (index >= 0 && index < photos.length) {
        photoModal.value = {
            show: true,
            index: index,
            url: urls[index] || photos[index],
        };
    }
};

const closePhotoModal = () => {
    photoModal.value.show = false;
};

const nextPhoto = () => {
    const photos = safePhotos.value;
    if (photoModal.value.index < photos.length - 1) {
        openPhotoModal(photoModal.value.index + 1);
    }
};

const prevPhoto = () => {
    if (photoModal.value.index > 0) {
        openPhotoModal(photoModal.value.index - 1);
    }
};

// ==============================================
// 👇 СТАТУСЫ
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
