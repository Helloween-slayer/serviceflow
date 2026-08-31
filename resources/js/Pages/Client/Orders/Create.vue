<template>
    <AppLayout>
        <div v-if="$page.props.flash?.error" class="max-w-4xl mx-auto mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
            {{ $page.props.flash.error }}
        </div>
        <div v-if="$page.props.flash?.success" class="max-w-4xl mx-auto mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ $page.props.flash.success }}
        </div>

        <div class="max-w-4xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">➕ Створити заявку</h1>
                <p class="text-gray-600 mt-1">Заповніть форму, щоб створити нову заявку</p>
            </div>

            <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Назва <span class="text-red-500">*</span>
                    </label>
                    <Input
                        v-model="formData.title"
                        placeholder="Введіть назву заявки"
                        :error="errors.title"
                        required
                    />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Опис
                    </label>
                    <textarea
                        v-model="formData.description"
                        rows="4"
                        placeholder="Опишіть вашу задачу"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Ціна (₴)
                        </label>
                        <Input
                            v-model="formData.price"
                            type="number"
                            step="0.01"
                            placeholder="Введіть ціну або залиште порожнім"
                            :error="errors.price"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Дедлайн
                        </label>
                        <Input
                            v-model="formData.deadline"
                            type="date"
                            :error="errors.deadline"
                        />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Теги
                    </label>
                    <select
                        v-model="formData.tags"
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

                <!-- ========== ФОТО ========== -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Фото (до 5 шт.)
                    </label>
                    <input
                        type="file"
                        @change="handlePhotoUpload"
                        accept="image/*"
                        multiple
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP (макс. 10MB каждый)</p>

                    <div v-if="photoFiles.length > 0" class="flex flex-wrap gap-3 mt-3">
                        <div
                            v-for="(photo, index) in photoFiles"
                            :key="'photo-' + index"
                            class="relative w-24 h-24 rounded-lg overflow-hidden border border-gray-200 group"
                        >
                            <img :src="getFileUrl(photo)" class="w-full h-full object-cover" />
                            <button
                                type="button"
                                @click="removePhoto(index)"
                                class="absolute -top-1 -right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600 transition"
                            >
                                ✕
                            </button>
                            <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs text-center py-0.5 truncate px-1">
                                {{ photo.name }}
                            </div>
                        </div>
                    </div>
                    <p v-if="errors.photos" class="text-red-500 text-sm mt-1">
                        {{ errors.photos }}
                    </p>
                </div>

                <!-- ========== ФАЙЛЫ ========== -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Додаткові файли (до 5 шт.)
                    </label>
                    <input
                        type="file"
                        @change="handleFileUpload"
                        multiple
                        class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p class="text-xs text-gray-400 mt-1">PDF, DOC, DOCX, JPG, PNG, ZIP (макс. 10MB каждый)</p>

                    <div v-if="fileFiles.length > 0" class="flex flex-wrap gap-2 mt-3">
                        <div
                            v-for="(file, index) in fileFiles"
                            :key="'file-' + index"
                            class="flex items-center gap-2 bg-gray-100 px-3 py-1.5 rounded-lg border border-gray-200"
                        >
                            <span class="text-sm">📎 {{ file.name }}</span>
                            <span class="text-xs text-gray-400">({{ formatFileSize(file.size) }})</span>
                            <button
                                type="button"
                                @click="removeFile(index)"
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

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <Link :href="route('client.orders.index')" class="text-sm text-gray-600 hover:text-gray-900">
                        Скасувати
                    </Link>
                    <Button type="submit" :loading="isSubmitting" variant="primary">
                        Створити заявку
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    tags: Array,
});

const { errors } = usePage().props;

const formData = ref({
    title: '',
    description: '',
    price: '',
    deadline: '',
    tags: [],
});

const photoFiles = ref([]);
const fileFiles = ref([]);
const isSubmitting = ref(false);

// =============================================
// ✅ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// =============================================
const formatFileSize = (bytes) => {
    if (!bytes) return '0 B';
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const getFileUrl = (file) => {
    if (!file) return '';
    return window.URL.createObjectURL(file);
};

// =============================================
// ✅ ФОТО
// =============================================
const handlePhotoUpload = (event) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    for (let file of files) {
        if (photoFiles.value.length >= 5) {
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
        photoFiles.value.push(file);
    }
    event.target.value = '';
};

const removePhoto = (index) => {
    photoFiles.value.splice(index, 1);
};

// =============================================
// ✅ ФАЙЛЫ
// =============================================
const handleFileUpload = (event) => {
    const files = event.target.files;
    if (!files || files.length === 0) return;

    for (let file of files) {
        if (fileFiles.value.length >= 5) {
            alert('Максимум 5 файлів');
            break;
        }
        if (file.size > 10 * 1024 * 1024) {
            alert(`Файл "${file.name}" занадто великий. Максимум 10MB.`);
            continue;
        }
        fileFiles.value.push(file);
    }
    event.target.value = '';
};

const removeFile = (index) => {
    fileFiles.value.splice(index, 1);
};

// =============================================
// ✅ ОТПРАВКА ФОРМЫ (ЧЕРЕЗ AXIOS, А НЕ Inertia!)
// =============================================
const submit = () => {
    const data = new FormData();

    data.append('title', formData.value.title);
    data.append('description', formData.value.description || '');
    data.append('price', formData.value.price || '');
    data.append('deadline', formData.value.deadline || '');

    formData.value.tags.forEach(tag => {
        data.append('tags[]', tag);
    });

    photoFiles.value.forEach(photo => {
        data.append('photos[]', photo);
    });

    fileFiles.value.forEach(file => {
        data.append('files[]', file);
    });

    isSubmitting.value = true;

    // ✅ Отправляем через axios (НЕ через Inertia)
    axios.post(route('client.orders.store'), data, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
    })
        .then(() => {
            photoFiles.value = [];
            fileFiles.value = [];
            formData.value = {
                title: '',
                description: '',
                price: '',
                deadline: '',
                tags: [],
            };
            isSubmitting.value = false;
            router.visit(route('client.orders.index'));
        })
        .catch((error) => {
            console.error('❌ Ошибка:', error);
            isSubmitting.value = false;
            if (error.response?.data?.errors) {
                alert(JSON.stringify(error.response.data.errors));
            }
        });
};
</script>
