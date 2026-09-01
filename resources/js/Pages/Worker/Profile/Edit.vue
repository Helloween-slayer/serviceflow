<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Заголовок -->
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">✏️ Редагування профілю</h1>
                <p class="text-gray-600 mt-1">Оновіть інформацію про себе</p>
            </div>

            <!-- Форма -->
            <form @submit.prevent="submit" class="space-y-6">
                <!-- Аватар -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-blue-600">
                            <span class="text-xl">🖼️</span>
                            <span class="font-semibold">Аватар</span>
                        </div>
                    </template>
                    <div class="flex items-center gap-6">
                        <div class="flex-shrink-0">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-100 border-2 border-gray-200">
                                <img
                                    v-if="avatarPreview || profile.avatar_url"
                                    :src="avatarPreview || profile.avatar_url"
                                    alt="Аватар"
                                    class="w-full h-full object-cover"
                                    @error="handleAvatarError"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center text-4xl text-gray-400 bg-gray-50">
                                    {{ getInitials(user.name) }}
                                </div>
                            </div>
                        </div>
                        <div class="flex-1">
                            <input
                                type="file"
                                ref="avatarInput"
                                @change="handleAvatarUpload"
                                accept="image/jpeg,image/png,image/webp"
                                class="hidden"
                            />
                            <Button
                                type="button"
                                variant="secondary"
                                @click="avatarInput.click()"
                            >
                                📤 Завантажити аватар
                            </Button>
                            <p class="text-xs text-gray-400 mt-1">Максимум 2MB. JPG, PNG, WEBP</p>
                            <button
                                v-if="profile.avatar || avatarPreview"
                                type="button"
                                @click="removeAvatar"
                                class="text-sm text-red-500 hover:text-red-700 mt-1"
                            >
                                🗑️ Видалити аватар
                            </button>
                        </div>
                    </div>
                </Card>

                <!-- Основная информация -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-green-600">
                            <span class="text-xl">📝</span>
                            <span class="font-semibold">Основна інформація</span>
                        </div>
                    </template>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Біографія
                            </label>
                            <textarea
                                v-model="form.bio"
                                rows="4"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Розкажіть про себе..."
                            ></textarea>
                            <p class="text-xs text-gray-400 mt-1">Максимум 5000 символів</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Навички
                            </label>
                            <textarea
                                v-model="form.skills"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Наприклад: PHP, Laravel, Vue.js, React..."
                            ></textarea>
                            <p class="text-xs text-gray-400 mt-1">Максимум 1000 символів</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Досвід роботи
                            </label>
                            <textarea
                                v-model="form.experience"
                                rows="3"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Розкажіть про ваш досвід..."
                            ></textarea>
                            <p class="text-xs text-gray-400 mt-1">Максимум 5000 символів</p>
                        </div>
                    </div>
                </Card>

                <!-- Контактная информация -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-purple-600">
                            <span class="text-xl">📞</span>
                            <span class="font-semibold">Контактна інформація</span>
                        </div>
                    </template>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Компанія
                            </label>
                            <input
                                v-model="form.company"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Назва компанії"
                            />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Телефон
                            </label>
                            <input
                                v-model="form.phone"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="+380 XX XXX XX XX"
                            />
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                Розташування
                            </label>
                            <input
                                v-model="form.location"
                                type="text"
                                class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Київ, Україна"
                            />
                        </div>
                    </div>
                </Card>

                <!-- Портфолио -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-yellow-600">
                            <span class="text-xl">💼</span>
                            <span class="font-semibold">Портфоліо</span>
                            <span class="text-sm text-gray-400">({{ portfolioFiles.length }} файлів)</span>
                        </div>
                    </template>

                    <!-- Существующие файлы -->
                    <div v-if="portfolioFiles.length > 0" class="grid grid-cols-3 gap-4 mb-4">
                        <div
                            v-for="(file, index) in portfolioFiles"
                            :key="index"
                            class="relative group"
                        >
                            <div class="aspect-square rounded-lg overflow-hidden border border-gray-200 bg-gray-50">
                                <img
                                    v-if="isImage(file)"
                                    :src="file.url"
                                    :alt="'Портфоліо ' + (index + 1)"
                                    class="w-full h-full object-cover"
                                />
                                <div v-else class="w-full h-full flex items-center justify-center">
                                    <span class="text-3xl">📄</span>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="removePortfolioFile(index)"
                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm hover:bg-red-600 transition opacity-0 group-hover:opacity-100"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Загрузка новых файлов -->
                    <div>
                        <input
                            type="file"
                            ref="portfolioInput"
                            @change="handlePortfolioUpload"
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
                            multiple
                            class="hidden"
                        />
                        <Button
                            type="button"
                            variant="secondary"
                            @click="portfolioInput.click()"
                            :disabled="portfolioFiles.length >= 5"
                        >
                            📤 Додати файли портфоліо
                        </Button>
                        <p class="text-xs text-gray-400 mt-1">
                            Максимум 5 файлів, по 10MB кожен. PDF, DOC, DOCX, JPG, PNG, ZIP
                        </p>
                    </div>
                </Card>

                <!-- Кнопки -->
                <div class="flex gap-3">
                    <Button type="submit" :loading="saving">
                        💾 Зберегти зміни
                    </Button>
                    <Link :href="route('worker.profile.show', user.id)">
                        <Button variant="secondary" type="button">← Назад до профілю</Button>
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    profile: {
        type: Object,
        required: true,
    },
});

const { auth } = usePage().props;
const user = auth.user;

// Состояния
const saving = ref(false);
const avatarInput = ref(null);
const portfolioInput = ref(null);
const avatarPreview = ref(null);
const removeAvatarFlag = ref(false);

// Форма
const form = ref({
    bio: props.profile.bio || '',
    skills: props.profile.skills || '',
    experience: props.profile.experience || '',
    company: props.profile.company || '',
    phone: props.profile.phone || '',
    location: props.profile.location || '',
    avatar: null,
    portfolio: [],
    removed_portfolio: [],
});

// Портфолио с URL
const portfolioFiles = computed(() => {
    if (!props.profile.portfolio) return [];
    if (Array.isArray(props.profile.portfolio)) {
        return props.profile.portfolio.map((path, index) => ({
            path: path,
            url: getFileUrl(path, index),
            isNew: false,
        }));
    }
    return [];
});

// Получение URL для файла
const getFileUrl = (path, index) => {
    if (!path) return '';
    if (props.profile.portfolio_urls && props.profile.portfolio_urls[index]) {
        return props.profile.portfolio_urls[index];
    }
    return path;
};

// Инициалы
const getInitials = (name) => {
    if (!name) return '?';
    return name.split(' ').map(word => word[0]).join('').toUpperCase().slice(0, 2);
};

// Проверка, является ли файл изображением
const isImage = (file) => {
    const ext = file.path?.split('.').pop()?.toLowerCase() || '';
    return ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(ext);
};

// Обработка ошибки загрузки аватара
const handleAvatarError = (event) => {
    event.target.src = '';
    event.target.onerror = null;
};

// Загрузка аватара
const handleAvatarUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.avatar = file;
        avatarPreview.value = URL.createObjectURL(file);
        removeAvatarFlag.value = false;
    }
};

// Удаление аватара
const removeAvatar = () => {
    form.value.avatar = null;
    avatarPreview.value = null;
    removeAvatarFlag.value = true;
    if (avatarInput.value) {
        avatarInput.value.value = '';
    }
};

// Загрузка портфолио
const handlePortfolioUpload = (event) => {
    const files = event.target.files;
    if (files.length > 0) {
        const newFiles = Array.from(files);
        const totalFiles = portfolioFiles.value.length + newFiles.length;
        if (totalFiles > 5) {
            alert('Максимум 5 файлів у портфоліо');
            return;
        }
        form.value.portfolio = [...form.value.portfolio, ...newFiles];
        // Обновляем список
        // Это нужно для отображения превью
        event.target.value = '';
    }
};

// Удаление файла из портфолио
const removePortfolioFile = (index) => {
    const file = portfolioFiles.value[index];
    if (file && !file.isNew) {
        form.value.removed_portfolio.push(file.path);
    }
    // Удаляем из формы
    if (form.value.portfolio.length > 0) {
        form.value.portfolio.splice(index, 1);
    }
};

// Отправка формы
const submit = () => {
    saving.value = true;

    const data = new FormData();
    data.append('bio', form.value.bio || '');
    data.append('skills', form.value.skills || '');
    data.append('experience', form.value.experience || '');
    data.append('company', form.value.company || '');
    data.append('phone', form.value.phone || '');
    data.append('location', form.value.location || '');

    if (form.value.avatar) {
        data.append('avatar', form.value.avatar);
    }

    if (removeAvatarFlag.value) {
        data.append('remove_avatar', 'true');
    }

    // Портфолио
    if (form.value.portfolio.length > 0) {
        form.value.portfolio.forEach(file => {
            data.append('portfolio[]', file);
        });
    }

    form.value.removed_portfolio.forEach(path => {
        data.append('removed_portfolio[]', path);
    });

    router.post(route('worker.profile.update'), data, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
        onSuccess: () => {
            saving.value = false;
        },
        onError: (errors) => {
            saving.value = false;
            console.error('Ошибки:', errors);
        },
    });
};
</script>
