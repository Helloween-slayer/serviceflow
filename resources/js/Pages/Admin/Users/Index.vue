<template>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <!-- Заголовок -->
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-2xl font-bold">
                            👥 Управління користувачами
                        </h1>
                        <span class="text-sm text-gray-500">
                            Всього: {{ users.total }}
                        </span>
                    </div>

                    <!-- Фильтры -->
                    <div class="mb-6 flex flex-col md:flex-row gap-4">
                        <!-- Поиск -->
                        <div class="flex-1">
                            <input
                                v-model="filters.search"
                                type="text"
                                placeholder="Пошук за ім'ям або email..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @input="applyFilters"
                            />
                        </div>

                        <!-- Фильтр по роли -->
                        <div class="w-full md:w-48">
                            <select
                                v-model="filters.role"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @change="applyFilters"
                            >
                                <option value="">Всі ролі</option>
                                <option
                                    v-for="role in roles"
                                    :key="role.id"
                                    :value="role.id"
                                >
                                    {{ role.name }}
                                </option>
                            </select>
                        </div>

                        <!-- Сброс фильтров -->
                        <button
                            @click="resetFilters"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition"
                        >
                            Скинути
                        </button>
                    </div>

                    <!-- Таблица пользователей -->
                    <div v-if="users.data.length" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Ім'я
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Роль
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Дата реєстрації
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Дії
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ user.id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ user.name }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ user.email }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getRoleBadgeClass(user.role?.name)" class="px-2 py-1 text-xs rounded-full">
                                            {{ user.role?.name || 'Не вказано' }}
                                        </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ formatDate(user.created_at) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex gap-2">
                                        <!-- Кнопка редактирования -->
                                        <Link
                                            :href="route('admin.users.edit', user.id)"
                                            class="text-blue-600 hover:text-blue-900"
                                        >
                                            Редагувати
                                        </Link>

                                        <!-- Кнопка удаления -->
                                        <button
                                            @click="deleteUser(user)"
                                            :disabled="user.id === $page.props.auth.user.id"
                                            :class="[
                                                    'text-red-600 hover:text-red-900',
                                                    user.id === $page.props.auth.user.id ? 'opacity-50 cursor-not-allowed' : ''
                                                ]"
                                        >
                                            Видалити
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Пустое состояние -->
                    <div v-else class="text-center py-12">
                        <p class="text-gray-500">Користувачів не знайдено</p>
                    </div>

                    <!-- Пагинация -->
                    <div v-if="users.data.length" class="mt-6 flex items-center justify-between">
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Показано
                                    <span class="font-medium">{{ users.from }}</span>
                                    -
                                    <span class="font-medium">{{ users.to }}</span>
                                    з
                                    <span class="font-medium">{{ users.total }}</span>
                                </p>
                            </div>
                            <div>
                                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                                    <Link
                                        v-for="(link, index) in users.links"
                                        :key="index"
                                        :href="link.url || '#'"
                                        :class="[
                                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                                            link.active
                                                ? 'z-10 bg-blue-600 text-white focus:z-20 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600'
                                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 focus:z-20 focus:outline-offset-0',
                                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                                            index === 0 ? 'rounded-l-md' : '',
                                            index === users.links.length - 1 ? 'rounded-r-md' : '',
                                        ]"
                                        v-html="link.label"
                                    />
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

// Состояние фильтров
const filters = ref({
    search: props.filters.search || '',
    role: props.filters.role || '',
});

// Применение фильтров
const applyFilters = () => {
    router.get(
        route('admin.users.index'),
        filters.value,
        { preserveState: true }
    );
};

// Сброс фильтров
const resetFilters = () => {
    filters.value = { search: '', role: '' };
    applyFilters();
};

// Удаление пользователя
const deleteUser = (user) => {
    if (!confirm(`Ви впевнені, що хочете видалити користувача "${user.name}"?`)) {
        return;
    }

    router.delete(route('admin.users.destroy', user.id), {
        onSuccess: () => {
            // Сообщение об успехе будет из контроллера
        },
        onError: (errors) => {
            alert(errors.message || 'Не вдалося видалити користувача');
        },
    });
};

// Форматирование даты
const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uk-UA');
};

// Цвета для ролей
const getRoleBadgeClass = (roleName) => {
    const map = {
        admin: 'bg-red-100 text-red-800',
        worker: 'bg-blue-100 text-blue-800',
        client: 'bg-green-100 text-green-800',
    };
    return map[roleName] || 'bg-gray-100 text-gray-800';
};
</script>
