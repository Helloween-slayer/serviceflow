<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Заголовок -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">✏️ Редагувати заявку</h1>
                <p class="text-gray-600 mt-1">Внесіть зміни до заявки</p>
            </div>

            <!-- Форма -->
            <form @submit.prevent="submit" class="space-y-6">
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
    tags: props.order.tags.map(tag => tag.id),
});

const submit = () => {
    form.put(route('client.orders.update', props.order.id), {
        onSuccess: () => {
            router.visit(route('client.orders.index'));
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
