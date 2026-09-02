<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">✏️ Редагувати профіль</h1>
                <p class="text-gray-600 mt-1">Заповніть інформацію про себе</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Аватар -->
                <div class="flex items-center gap-6">
                    <div class="relative">
                        <div
                            class="w-24 h-24 rounded-full overflow-hidden bg-gray-100 border-4 border-gray-200 flex items-center justify-center"
                            :class="{ 'opacity-50': uploadingAvatar }"
                        >
                            <img
                                v-if="avatarPreview"
                                :src="avatarPreview"
                                alt="Аватар"
                                class="w-full h-full object-cover"
                            />
                            <div v-else-if="profile.avatar_url" class="w-full h-full">
                                <img
                                    :src="profile.avatar_url"
                                    alt="Аватар"
                                    class="w-full h-full object-cover"
                                    @error="handleAvatarError"
                                />
                            </div>
                            <div v-else class="text-4xl text-gray-300">
                                {{ getUserInitials() }}
                            </div>
                        </div>

                        <div
                            class="absolute inset-0 rounded-full bg-black/40 opacity-0 hover:opacity-100 transition flex items-center justify-center cursor-pointer"
                            @click="triggerFileInput"
                        >
                            <div class="text-white text-center">
                                <div class="text-2xl">📷</div>
                                <div class="text-xs font-medium">Змінити</div>
                            </div>
                        </div>

                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*"
                            class="hidden"
                            @change="handleFileSelect"
                        />
                    </div>

                    <div>
                        <p class="font-medium">{{ auth.user?.name || 'Користувач' }}</p>
                        <p class="text-sm text-gray-500">{{ auth.user?.email || '' }}</p>
                        <p class="text-xs text-gray-400 mt-1">Натисніть на аватар для завантаження</p>
                    </div>
                </div>

                <!-- Прогрес завантаження -->
                <div v-if="uploadingAvatar" class="w-full bg-gray-200 rounded-full h-2.5">
                    <div
                        class="bg-blue-600 h-2.5 rounded-full transition-all duration-300"
                        :style="{ width: uploadProgress + '%' }"
                    ></div>
                    <p class="text-xs text-gray-500 mt-1">Завантаження... {{ uploadProgress }}%</p>
                </div>

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
                    <Button type="submit" :loading="form.processing || uploadingAvatar" variant="primary">
                        Зберегти
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, usePage, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';

const { auth } = usePage().props;

const props = defineProps({
    profile: {
        type: Object,
        default: () => ({}),
    },
});

// Форма
const form = useForm({
    bio: props.profile?.bio || '',
    skills: props.profile?.skills || '',
    experience: props.profile?.experience || '',
    company: props.profile?.company || '',
    phone: props.profile?.phone || '',
    location: props.profile?.location || '',
    avatar: null,
});

// Стани для аватара
const fileInput = ref(null);
const avatarPreview = ref(null);
const uploadingAvatar = ref(false);
const uploadProgress = ref(0);

// Метод для отримання ініціалів
const getUserInitials = () => {
    const name = auth.user?.name || '';
    if (!name) return '?';
    return name
        .split(' ')
        .map(word => word[0])
        .join('')
        .toUpperCase()
        .slice(0, 2);
};

// Тригер для вибору файлу
const triggerFileInput = () => {
    fileInput.value?.click();
};

// Обробка вибору файлу
const handleFileSelect = (event) => {
    const file = event.target.files[0];
    if (!file) return;

    // Валідація
    const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        alert('Будь ласка, оберіть файл зображення (JPEG, PNG, GIF, WEBP)');
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        alert('Файл занадто великий. Максимальний розмір - 2MB');
        return;
    }

    // Створюємо прев'ю
    const reader = new FileReader();
    reader.onload = (e) => {
        avatarPreview.value = e.target.result;
    };
    reader.readAsDataURL(file);

    // Зберігаємо файл у формі
    form.avatar = file;

    // Очищаємо input для можливості повторного вибору того ж файлу
    event.target.value = '';
};

// Обробка помилки завантаження аватара
const handleAvatarError = (event) => {
    event.target.style.display = 'none';
};

// Submit форми
const submit = () => {
    const options = {
        onSuccess: () => {
            if (uploadingAvatar.value) {
                uploadProgress.value = 100;
                setTimeout(() => {
                    uploadingAvatar.value = false;
                    router.visit(route('worker.profile.show', auth.user.id));
                }, 500);
            } else {
                router.visit(route('worker.profile.show', auth.user.id));
            }
        },
        onError: (errors) => {
            uploadingAvatar.value = false;
            uploadProgress.value = 0;
            console.error('Помилка збереження:', errors);
            alert('Помилка при збереженні. Спробуйте ще раз.');
        },
    };

    if (form.avatar) {
        uploadingAvatar.value = true;
        uploadProgress.value = 0;

        const interval = setInterval(() => {
            if (uploadProgress.value < 90) {
                uploadProgress.value += 10;
            }
        }, 200);

        form.put(route('worker.profile.update'), {
            ...options,
            forceFormData: true,
            onProgress: (progress) => {
                if (progress.percentage) {
                    uploadProgress.value = Math.round(progress.percentage);
                }
            },
            onSuccess: () => {
                clearInterval(interval);
                options.onSuccess();
            },
            onError: (errors) => {
                clearInterval(interval);
                options.onError(errors);
            },
        });
    } else {
        form.put(route('worker.profile.update'), options);
    }
};
</script>
