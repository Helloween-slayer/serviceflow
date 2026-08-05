<template>
    <AppLayout>
        <div class="max-w-3xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">✏️ Редагувати профіль</h1>
                <p class="text-gray-600 mt-1">Заповніть інформацію про себе</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <!-- Аватар (заглушка) -->
                <div class="flex items-center gap-4">
                    <div class="w-20 h-20 rounded-full bg-blue-500 flex items-center justify-center text-white text-3xl font-bold">
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <p class="font-medium">{{ user.name }}</p>
                        <p class="text-sm text-gray-500">{{ user.email }}</p>
                    </div>
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

const { auth } = usePage().props;

const props = defineProps({
    profile: Object,
});

const form = useForm({
    bio: props.profile?.bio || '',
    skills: props.profile?.skills || '',
    experience: props.profile?.experience || '',
    company: props.profile?.company || '',
    phone: props.profile?.phone || '',
    location: props.profile?.location || '',
});

const submit = () => {
    form.put(route('worker.profile.update'), {
        onSuccess: () => {
            router.reload();
        },
    });
};
</script>
