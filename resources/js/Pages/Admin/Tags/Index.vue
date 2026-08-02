<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Заголовок сторінки -->
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold">
                            🏷️ Управління тегами
                        </h1>
                        <span class="text-sm text-gray-500">
                            Всього: {{ tags.total }}
                        </span>
                    </div>

                    <!-- --- ПОЛЕ ПОШУКУ --- -->
                    <div class="mb-6">
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Пошук за назвою тега..."
                            class="w-full max-w-md rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            @input="applyFilters"
                        />
                    </div>

                    <!-- --- ТАБЛИЦЯ ТЕГІВ --- -->
                    <div v-if="tags.data.length" class="overflow-x-auto">
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

                                <!-- Назва (з inline-редагуванням) -->
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <!-- Режим редагування -->
                                    <div v-if="editingTagId === tag.id" class="flex items-center gap-2">
                                        <input
                                            v-model="editForm.name"
                                            type="text"
                                            class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                            :class="{ 'border-red-500': editForm.errors.name }"
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
                                    <span v-else class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm">
                                            {{ tag.name }}
                                        </span>
                                    <!-- Помилка валідації -->
                                    <p v-if="editingTagId === tag.id && editForm.errors.name"
                                       class="text-red-500 text-xs mt-1">
                                        {{ editForm.errors.name }}
                                    </p>
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

                    <!-- --- ПУСТИЙ СТАН --- -->
                    <div v-else class="text-center py-12">
                        <p class="text-gray-500">
                            {{ filters.search ? 'Тегів не знайдено' : 'Тегів поки немає' }}
                        </p>
                    </div>

                    <!-- --- ПАГІНАЦІЯ --- -->
                    <div v-if="tags.data.length" class="mt-6 flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Показано
                                    <span class="font-medium">{{ tags.from }}</span>
                                    -
                                    <span class="font-medium">{{ tags.to }}</span>
                                    з
                                    <span class="font-medium">{{ tags.total }}</span>
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                    <a
                                        v-for="(link, index) in tags.links"
                                        :key="index"
                                        :href="link.url || '#'"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                                            link.active
                                                ? 'z-10 bg-blue-600 text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === tags.links.length - 1 ? 'rounded-r-md' : '',
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>

                    <!-- --- ФОРМА СТВОРЕННЯ НОВОГО ТЕГА --- -->
                    <div class="mt-8 pt-6 border-t border-gray-200">
                        <h2 class="text-lg font-semibold mb-4">➕ Додати новий тег</h2>
                        <form @submit.prevent="createTag" class="flex flex-col md:flex-row gap-4">
                            <div class="flex-1">
                                <input
                                    v-model="createForm.name"
                                    type="text"
                                    placeholder="Введіть назву нового тега..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    :class="{ 'border-red-500': createForm.errors.name }"
                                    autofocus
                                />
                                <p v-if="createForm.errors.name" class="text-red-500 text-sm mt-1">
                                    {{ createForm.errors.name }}
                                </p>
                            </div>
                            <button
                                type="submit"
                                :disabled="createForm.processing"
                                class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 transition disabled:opacity-50 whitespace-nowrap"
                            >
                                {{ createForm.processing ? 'Збереження...' : '➕ Додати' }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

// --- ПРОПСИ (дані з контролера) ---
const props = defineProps({
    tags: Object,      // Список тегів з пагінацією
    filters: Object,   // Поточні фільтри
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
const editingTagId = ref(null);      // ID тега, який редагуємо
const editForm = useForm({
    name: '',
});

// --- МЕТОДИ ---

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
            createForm.reset(); // Очищаємо поле після успішного створення
        },
    });
};

/**
 * Почати редагування тега
 */
const startEdit = (tag) => {
    editingTagId.value = tag.id;
    editForm.name = tag.name;
    editForm.clearErrors(); // Очищаємо попередні помилки
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
    // Підтвердження перед видаленням
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
