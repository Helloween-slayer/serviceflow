<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Заголовок -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">✏️ Редагувати заявку</h1>
                <p class="text-gray-600 mt-1">Внесіть зміни до заявки</p>
            </div>

            <!-- Форма -->
            <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
                <!-- Назва -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Назва <span class="text-red-500">*</span>
                    </label>
                    <Input
                        v-model="form.title"
                        placeholder="Введіть назву заявки"
                        :error="errors.title"
                        required
                    />
                </div>

                <!-- Опис -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Опис
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="4"
                        placeholder="Опишіть вашу задачу"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <!-- Ціна + Дедлайн (ряд) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ціна (₴)
                        </label>
                        <Input
                            v-model="form.price"
                            type="number"
                            step="0.01"
                            placeholder="Введіть ціну"
                            :error="errors.price"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Дедлайн
                        </label>
                        <Input
                            v-model="form.deadline"
                            type="date"
                            :error="errors.deadline"
                        />
                    </div>
                </div>

                <!-- Теги -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Теги
                    </label>
                    <select
                        v-model="form.tags"
                        multiple
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        size="4"
                    >
                        <option v-for="tag in tags" :key="tag.id" :value="tag.id">
                            {{ tag.name }}
                        </option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1">
                        Затисніть Ctrl (Cmd) для вибору декількох тегів
                    </p>
                    <div v-if="errors.tags" class="text-red-500 text-sm mt-1">
                        {{ errors.tags }}
                    </div>
                </div>

                <!-- ============================================= -->
                <!-- ✅ ФОТО -->
                <!-- ============================================= -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Фото (до 5 шт.)
                    </label>

                    <!-- Существующие фото -->
                    <div v-if="order.photos && order.photos.length > 0" class="flex flex-wrap gap-3 mb-3">
                        <div
                            v-for="(photo, index) in order.photos"
                            :key="'old-photo-' + index"
                            class="relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200"
                        >
                            <img
                                :src="order.photos_urls?.[index] || photo"
                                class="w-full h-full object-cover"
                                @error="handleImageError"
                            />
                            <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs text-center py-0.5 truncate px-1">
                                {{ getFileName(photo) }}
                            </div>
                        </div>
                    </div>

                    <!-- Загрузка новых фото -->
                    <input
                        type="file"
                        @change="handlePhotoUpload"
                        accept="image/*"
                        multiple
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP (макс. 10MB каждый)</p>

                    <!-- Превью новых фото -->
                    <div v-if="form.newPhotos.length > 0" class="flex flex-wrap gap-3 mt-3">
                        <div
                            v-for="(photo, index) in form.newPhotos"
                            :key="'new-photo-' + index"
                            class="relative w-24 h-24 rounded-lg overflow-hidden border border-green-400 group"
                        >
                            <img
                                v-if="isFile(photo)"
                                :src="getFileUrl(photo)"
                                class="w-full h-full object-cover"
                            />
                            <div v-else class="w-full h-full bg-red-100 flex items-center justify-center text-red-500 text-xs">
                                Ошибка
                            </div>
                            <button
                                type="button"
                                @click="removeNewPhoto(index)"
                                class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600 transition"
                            >
                                ✕
                            </button>
                            <div class="absolute bottom-0 left-0 right-0 bg-green-600/70 text-white text-xs text-center py-0.5 truncate px-1">
                                Нове
                            </div>
                        </div>
                    </div>
                    <p v-if="errors.photos" class="text-red-500 text-sm mt-1">
                        {{ errors.photos }}
                    </p>
                </div>

                <!-- ============================================= -->
                <!-- ✅ ФАЙЛЫ -->
                <!-- ============================================= -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Додаткові файли (до 5 шт.)
                    </label>

                    <!-- Существующие файлы -->
                    <div v-if="order.files && order.files.length > 0" class="flex flex-wrap gap-2 mb-3">
                        <div
                            v-for="(file, index) in order.files"
                            :key="'old-file-' + index"
                            class="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200"
                        >
                            <a
                                :href="order.files_urls?.[index] || file"
                                target="_blank"
                                class="text-sm text-blue-600 hover:underline"
                            >
                                📎 {{ getFileName(file) }}
                            </a>
                            <button
                                type="button"
                                @click="removeExistingFile(index)"
                                class="text-red-500 hover:text-red-700 text-sm"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Загрузка новых файлов -->
                    <input
                        type="file"
                        @change="handleFileUpload"
                        multiple
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX, JPG, PNG, ZIP (макс. 10MB каждый)</p>

                    <!-- Список новых файлов -->
                    <div v-if="form.newFiles.length > 0" class="flex flex-wrap gap-2 mt-3">
                        <div
                            v-for="(file, index) in form.newFiles"
                            :key="'new-file-' + index"
                            class="flex items-center gap-2 bg-green-50 px-3 py-1.5 rounded-lg border border-green-300"
                        >
                            <span class="text-sm" v-if="isFile(file)">📎 {{ file.name }}</span>
                            <span v-else class="text-sm text-red-500">Ошибка</span>
                            <span class="text-xs text-green-600">Новий</span>
                            <button
                                type="button"
                                @click="removeNewFile(index)"
                                class="text-red-500 hover:text-red-700 text-sm"
                            >
                                ✕
                            </button>
                        </div>
                    </div>
                    <p v-if="errors.files" class="text-red-500 text-sm mt-1">
                        {{ errors.files }}
                    </p>
                </div>

                <!-- Информация о статусе -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-center gap-2 text-sm text-blue-700">
                        <span>ℹ️</span>
                        <span>
                            Статус заявки: <Badge :variant="getStatusVariant(order.status)">
                                {{ getStatusText(order.status) }}
                            </Badge>
                        </span>
                    </div>
                </div>

                <!-- Кнопки -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <Link
                        :href="route('client.orders.index')"
                        class="text-sm text-gray-600 hover:text-gray-900"
                    >
                        Скасувати
                    </Link>
                    <Button
                        type="submit"
                        :loading="form.processing"
                        variant="primary"
                    >
                        Оновити заявку
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';

const props = defineProps({
    order: Object,
    tags: Array,
});

const { errors } = usePage().props;

const form = useForm({
    title: props.order.title,
    description: props.order.description || '',
    price: props.order.price || '',
    deadline: props.order.deadline || '',
    tags: props.order.tags?.map(tag => tag.id) || [],
    newPhotos: [],
    newFiles: [],
    removedFiles: [],
});

// =============================================
// ✅ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// =============================================
const getFileName = (path) => {
    if (!path) return '';
    return path.split('/').pop();
};

const getFileUrl = (file) => {
    if (!file || !(file instanceof File)) return '';
    return window.URL.createObjectURL(file);
};

const isFile = (value) => {
    return value instanceof File;
};

const handleImageError = (event) => {
    event.target.src = '/images/placeholder-image.png';
    event.target.onerror = null;
};

// =============================================
// ✅ ЗАГРУЗКА НОВЫХ ФОТО
// =============================================
const handlePhotoUpload = (event) => {
    const files = event.target.files;

    if (!files || files.length === 0) return;

    for (let file of files) {
        if (!(file instanceof File)) continue;
        if (form.newPhotos.length >= 5) {
            alert('Максимум 5 фото');
            break;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert(`Файл "${file.name}" занадто великий. Максимум 10MB.`);
            continue;
        }
        if (!file.type.startsWith('image/')) {
            alert(`Файл "${file.name}" не є зображенням.`);
            continue;
        }
        form.newPhotos.push(file);
    }

    form.clearErrors('photos');
    event.target.value = '';
};

const removeNewPhoto = (index) => {
    form.newPhotos.splice(index, 1);
};

// =============================================
// ✅ ЗАГРУЗКА НОВЫХ ФАЙЛОВ
// =============================================
const handleFileUpload = (event) => {
    const files = event.target.files;

    if (!files || files.length === 0) return;

    for (let file of files) {
        if (!(file instanceof File)) continue;
        if (form.newFiles.length >= 5) {
            alert('Максимум 5 файлів');
            break;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert(`Файл "${file.name}" занадто великий. Максимум 10MB.`);
            continue;
        }
        form.newFiles.push(file);
    }

    form.clearErrors('files');
    event.target.value = '';
};

const removeNewFile = (index) => {
    form.newFiles.splice(index, 1);
};

// =============================================
// ✅ УДАЛЕНИЕ СУЩЕСТВУЮЩИХ ФАЙЛОВ
// =============================================
const removeExistingFile = (index) => {
    if (!confirm('Ви впевнені, що хочете видалити цей файл?')) return;

    const filePath = props.order.files[index];
    form.removedFiles.push(filePath);
    props.order.files.splice(index, 1);
    if (props.order.files_urls) {
        props.order.files_urls.splice(index, 1);
    }
};

// =============================================
// ✅ ОТПРАВКА ФОРМЫ
// =============================================
const submit = () => {
    const validPhotos = form.newPhotos.filter(p => p instanceof File);
    const validFiles = form.newFiles.filter(f => f instanceof File);

    const formData = new FormData();

    formData.append('title', form.title);
    formData.append('description', form.description);
    formData.append('price', form.price);
    formData.append('deadline', form.deadline);

    form.tags.forEach(tag => {
        formData.append('tags[]', tag);
    });

    validPhotos.forEach(photo => {
        formData.append('photos[]', photo);
    });

    validFiles.forEach(file => {
        formData.append('files[]', file);
    });

    form.removedFiles.forEach(path => {
        formData.append('removed_files[]', path);
    });

    formData.append('_method', 'PUT');

    router.post(route('client.orders.update', props.order.id), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
        onSuccess: () => {
            form.newPhotos = [];
            form.newFiles = [];
            form.removedFiles = [];
            router.visit(route('client.orders.index'));
        },
        onError: (errors) => {
            console.error('Ошибки:', errors);
        },
    });
};

// Хелперы для статуса
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
</script>
