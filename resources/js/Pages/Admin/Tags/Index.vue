<template>
    <AppLayout>
        <div class="max-w-6xl mx-auto">
            <!-- Заголовок -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">🏷️ Управління тегами</h1>
                    <p class="text-gray-600 mt-1">Додавайте та редагуйте теги для заявок</p>
                </div>
                <span class="text-sm text-gray-500">
                    Всього: {{ tags.total }}
                </span>
            </div>

            <!-- Поиск -->
            <div class="mb-6">
                <Input
                    v-model="filters.search"
                    placeholder="Пошук за назвою тега..."
                    class="max-w-md"
                    @input="applyFilters"
                />
            </div>

            <!-- Таблица -->
            <div v-if="tags.data.length" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            ID
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Назва
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Заявок
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Дії
                        </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="tag in tags.data" :key="tag.id">
                        <!-- ID -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ tag.id }}
                        </td>

                        <!-- Назва (с inline-редагуванням) -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <!-- Режим редагування -->
                            <div v-if="editingTagId === tag.id" class="flex items-center gap-2">
                                <Input
                                    v-model="editForm.name"
                                    class="w-48"
                                    :error="editForm.errors.name"
                                    @keyup.enter="saveEdit(tag)"
                                    @keyup.esc="cancelEdit"
                                    autofocus
                                />
                                <button
                                    @click="saveEdit(tag)"
                                    :disabled="editForm.processing"
                                    class="text-green-600 hover:text-green-900 text-sm"
                                    title="Зберегти"
                                >
                                    💾
                                </button>
                                <button
                                    @click="cancelEdit"
                                    class="text-gray-500 hover:text-gray-700 text-sm"
                                    title="Скасувати"
                                >
                                    ✖️
                                </button>
                            </div>
                            <!-- Режим перегляду -->
                            <Badge variant="gray" v-else>
                                {{ tag.name }}
                            </Badge>
                        </td>

                        <!-- Кількість заявок -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ tag.orders_count || 0 }}
                        </td>

                        <!-- Дії -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-2">
                                <!-- Кнопка редагування -->
                                <button
                                    v-if="editingTagId !== tag.id"
                                    @click="startEdit(tag)"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    ✏️ Редагувати
                                </button>
                                <!-- Кнопка видалення -->
                                <button
                                    @click="deleteTag(tag)"
                                    :disabled="editingTagId === tag.id"
                                    class="text-red-600 hover:text-red-900 disabled:opacity-50"
                                >
                                    🗑️ Видалити
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <!-- Пустое состояние -->
            <EmptyState v-else>
                <template #title>
                    {{ filters.search ? 'Тегів не знайдено' : 'Тегів поки немає' }}
                </template>
                <template #description>
                    {{ filters.search ? 'Спробуйте змінити пошуковий запит' : 'Додайте перший тег, щоб класифікувати заявки' }}
                </template>
            </EmptyState>

            <!-- Пагинация -->
            <div v-if="tags.data.length" class="mt-6">
                <Pagination :pagination="tags" />
            </div>

            <!-- Форма создания нового тега -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">➕ Додати новий тег</h2>
                <form @submit.prevent="createTag" class="flex flex-col sm:flex-row gap-3">
                    <div class="flex-1">
                        <Input
                            v-model="createForm.name"
                            placeholder="Введіть назву нового тега..."
                            :error="createForm.errors.name"
                            autofocus
                        />
                    </div>
                    <Button
                        type="submit"
                        :loading="createForm.processing"
                        variant="success"
                    >
                        ➕ Додати
                    </Button>
                </form>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

// --- ПРОПСИ (дані з контролера) ---
const props = defineProps({
    tags: Object,
    filters: Object,
});

// --- СТАН ФІЛЬТРІВ ---
const filters = ref({
    search: props.filters.search || '',
});

// --- ФОРМА СТВОРЕННЯ ---
const createForm = useForm({
    name: '',
});

// --- INLINE РЕДАГУВАННЯ ---
const editingTagId = ref(null);
const editForm = useForm({
    name: '',
});

// --- МЕТОДЫ ---

/**
 * Застосувати фільтри (пошук)
 */
const applyFilters = () => {
    router.get(
        route('admin.tags.index'),
        filters.value,
        { preserveState: true }
    );
};

/**
 * Створити новий тег
 */
const createTag = () => {
    createForm.post(route('admin.tags.store'), {
        onSuccess: () => {
            createForm.reset();
        },
    });
};

/**
 * Почати редагування тега
 */
const startEdit = (tag) => {
    editingTagId.value = tag.id;
    editForm.name = tag.name;
    editForm.clearErrors();
};

/**
 * Скасувати редагування
 */
const cancelEdit = () => {
    editingTagId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

/**
 * Зберегти зміни після редагування
 */
const saveEdit = (tag) => {
    editForm.put(route('admin.tags.update', tag.id), {
        onSuccess: () => {
            editingTagId.value = null;
            editForm.reset();
        },
    });
};

/**
 * Видалити тег
 */
const deleteTag = (tag) => {
    if (!confirm(`Ви впевнені, що хочете видалити тег "${tag.name}"?`)) {
        return;
    }

    router.delete(route('admin.tags.destroy', tag.id), {
        onSuccess: () => {},
        onError: (errors) => {
            alert(errors.message || 'Не вдалося видалити тег');
        },
    });
};
</script>
