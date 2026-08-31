<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">✏️ Редагувати профіль</h1>
                <p class="text-gray-600 mt-1">Заповніть інформацію про себе</p>
            </div>

            <!-- Flash-сообщения -->
            <div v-if="$page.props.flash?.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error" class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                {{ $page.props.flash.error }}
            </div>

            <form @submit.prevent="submit" enctype="multipart/form-data" class="space-y-6">
                <!-- ============================================= -->
                <!-- ✅ АВАТАР (С ЗАГРУЗКОЙ) -->
                <!-- ============================================= -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Аватар</label>
                    <div class="flex items-center gap-6">
                        <!-- Превью аватара -->
                        <div class="relative">
                            <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-200 flex items-center justify-center border-2 border-gray-300">
                                <img
                                    v-if="form.avatarPreview"
                                    :src="form.avatarPreview"
                                    class="w-full h-full object-cover"
                                />
                                <img
                                    v-else-if="profile.avatar_url"
                                    :src="profile.avatar_url"
                                    class="w-full h-full object-cover"
                                />
                                <span v-else class="text-4xl text-gray-400">
                                    {{ user.name.charAt(0).toUpperCase() }}
                                </span>
                            </div>
                            <!-- Индикатор загрузки -->
                            <div v-if="uploadingAvatar" class="absolute inset-0 bg-black/50 rounded-full flex items-center justify-center">
                                <Loader />
                            </div>
                        </div>

                        <!-- Кнопка загрузки -->
                        <div>
                            <label class="cursor-pointer inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition text-sm">
                                📤 Завантажити аватар
                                <input
                                    type="file"
                                    @change="handleAvatarUpload"
                                    accept="image/*"
                                    class="hidden"
                                />
                            </label>
                            <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP (макс. 2MB)</p>
                            <p v-if="form.errors.avatar" class="text-red-500 text-sm mt-1">
                                {{ form.errors.avatar }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ============================================= -->
                <!-- ✅ ПОРТФОЛИО (МНОЖЕСТВЕННАЯ ЗАГРУЗКА) -->
                <!-- ============================================= -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Портфоліо</label>

                    <!-- Кнопка загрузки -->
                    <label class="cursor-pointer inline-block bg-gray-200 text-gray-700 px-4 py-2 rounded hover:bg-gray-300 transition text-sm">
                        📎 Додати файли
                        <input
                            type="file"
                            @change="handlePortfolioUpload"
                            multiple
                            accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip"
                            class="hidden"
                        />
                    </label>
                    <p class="text-xs text-gray-400 mt-1">PDF, DOC, JPG, PNG, ZIP (макс. 10MB каждый, до 5 файлов)</p>

                    <!-- Список новых файлов (еще не загруженных) -->
                    <div v-if="form.portfolio.length > 0" class="mt-3 space-y-2">
                        <div
                            v-for="(file, index) in form.portfolio"
                            :key="'new-' + index"
                            class="flex items-center justify-between bg-gray-50 px-3 py-2 rounded border border-gray-200"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-sm">📎 {{ file.name }}</span>
                                <span class="text-xs text-gray-400">({{ formatFileSize(file.size) }})</span>
                            </div>
                            <button
                                type="button"
                                @click="removePortfolio(index)"
                                class="text-red-500 hover:text-red-700 text-sm"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Список уже загруженных файлов -->
                    <div v-if="profile.portfolio && profile.portfolio.length > 0" class="mt-3 space-y-2">
                        <div class="text-sm text-gray-500 mb-1">Завантажені файли:</div>
                        <div
                            v-for="(file, index) in profile.portfolio"
                            :key="'old-' + index"
                            class="flex items-center justify-between bg-green-50 px-3 py-2 rounded border border-green-200"
                        >
                            <a
                                :href="profile.portfolio_urls[index]"
                                target="_blank"
                                class="text-sm text-blue-600 hover:underline flex items-center gap-2"
                            >
                                📎 {{ getFileName(file) }}
                            </a>
                            <span class="text-xs text-green-600">✅ Завантажено</span>
                        </div>
                    </div>

                    <p v-if="form.errors.portfolio" class="text-red-500 text-sm mt-1">
                        {{ form.errors.portfolio }}
                    </p>
                </div>

                <!-- ============================================= -->
                <!-- ОСТАЛЬНЫЕ ПОЛЯ (БЕЗ ИЗМЕНЕНИЙ) -->
                <!-- ============================================= -->

                <!-- Bio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Про себе</label>
                    <textarea
                        v-model="form.bio"
                        rows="4"
                        placeholder="Розкажіть про себе, ваш досвід та навички..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <!-- Навыки -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Навички</label>
                    <Input
                        v-model="form.skills"
                        placeholder="Наприклад: Photoshop, Figma, HTML, CSS..."
                    />
                    <p class="text-xs text-gray-400 mt-1">Перелічіть ваші основні навички через кому</p>
                </div>

                <!-- Опыт -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Досвід роботи</label>
                    <textarea
                        v-model="form.experience"
                        rows="3"
                        placeholder="Опишіть ваш досвід, проекти..."
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    ></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Компания -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Компанія</label>
                        <Input v-model="form.company" placeholder="Назва компанії" />
                    </div>

                    <!-- Телефон -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Телефон</label>
                        <Input v-model="form.phone" placeholder="+380 99 999 99 99" />
                    </div>
                </div>

                <!-- Локация -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Місто</label>
                    <Input v-model="form.location" placeholder="Київ, Львів, Одеса..." />
                </div>

                <!-- Кнопки -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <Link :href="route('worker.dashboard')" class="text-sm text-gray-600 hover:text-gray-900">
                        Скасувати
                    </Link>
                    <Button type="submit" :loading="form.processing" variant="primary">
                        Зберегти
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
import Loader from '@/Components/UI/Loader.vue';
import { ref } from 'vue';

const { auth } = usePage().props;
const user = auth.user;

const props = defineProps({
    profile: Object,
});

const uploadingAvatar = ref(false);

const form = useForm({
    bio: props.profile?.bio || '',
    skills: props.profile?.skills || '',
    experience: props.profile?.experience || '',
    company: props.profile?.company || '',
    phone: props.profile?.phone || '',
    location: props.profile?.location || '',
    avatar: null,           // ✅ Файл аватара
    avatarPreview: null,    // ✅ Превью для отображения
    portfolio: [],          // ✅ Массив новых файлов портфолио
});

// =============================================
// ✅ ЗАГРУЗКА АВАТАРА
// =============================================
const handleAvatarUpload = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Проверка размера (2MB)
    if (file.size > 2 * 1024 * 1024) {
        alert('Файл занадто великий. Максимум 2MB.');
        return;
    }

    // Проверка типа
    if (!file.type.startsWith('image/')) {
        alert('Будь ласка, оберіть зображення (JPG, PNG, WEBP)');
        return;
    }

    form.avatar = file;
    form.avatarPreview = URL.createObjectURL(file);
    form.clearErrors('avatar');
};

// =============================================
// ✅ ЗАГРУЗКА ПОРТФОЛИО
// =============================================
const handlePortfolioUpload = (event) => {
    const files = event.target.files;

    for (let file of files) {
        // Проверка количества (максимум 5)
        if (form.portfolio.length >= 5) {
            alert('Максимум 5 файлів портфоліо');
            break;
        }

        // Проверка размера (10MB)
        if (file.size > 10 * 1024 * 1024) {
            alert(`Файл "${file.name}" занадто великий. Максимум 10MB.`);
            continue;
        }

        form.portfolio.push(file);
    }

    form.clearErrors('portfolio');
    // Сбрасываем input, чтобы можно было загрузить те же файлы снова
    event.target.value = '';
};

// =============================================
// ✅ УДАЛЕНИЕ ФАЙЛА ИЗ ПОРТФОЛИО (локально)
// =============================================
const removePortfolio = (index) => {
    form.portfolio.splice(index, 1);
};

// =============================================
// ✅ ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// =============================================
const formatFileSize = (bytes) => {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
};

const getFileName = (path) => {
    return path.split('/').pop();
};

// =============================================
// ✅ ОТПРАВКА ФОРМЫ
// =============================================
const submit = () => {
    const formData = new FormData();

    // Текстовые поля
    formData.append('bio', form.bio);
    formData.append('skills', form.skills);
    formData.append('experience', form.experience);
    formData.append('company', form.company);
    formData.append('phone', form.phone);
    formData.append('location', form.location);

    // Аватар
    if (form.avatar) {
        formData.append('avatar', form.avatar);
    }

    // Портфолио (массив файлов)
    form.portfolio.forEach(file => {
        formData.append('portfolio[]', file);
    });

    // ✅ Важно для PUT запроса с файлами
    formData.append('_method', 'PUT');

    // Отправляем через Inertia с FormData
    router.post(route('worker.profile.update'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
        },
        onSuccess: () => {
            // Сбрасываем локальные файлы после успешной отправки
            form.portfolio = [];
            form.avatar = null;
            // Не сбрасываем avatarPreview, потому что он обновится после перезагрузки
            router.reload();
        },
        onError: (errors) => {
            console.error('Ошибки:', errors);
        },
    });
};
</script>
