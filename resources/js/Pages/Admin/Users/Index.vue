<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto">
            <!-- Заголовок -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">👥 Управління користувачами</h1>
                    <p class="text-gray-600 mt-1">Перегляд та управління всіма користувачами платформи</p>
                </div>
                <span class="text-sm text-gray-500">
                    Всього: {{ users.total }}
                </span>
            </div>

            <!-- Фильтры -->
            <div class="mb-6 flex flex-col md:flex-row gap-3">
                <div class="flex-1">
                    <Input
                        v-model="filters.search"
                        placeholder="Пошук за ім'ям або email..."
                        @input="applyFilters"
                    />
                </div>
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
                <Button variant="secondary" @click="resetFilters">
                    Скинути
                </Button>
            </div>

            <!-- Таблица пользователей -->
            <div v-if="users.data.length" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
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
                    <tr v-for="user in users.data" :key="user.id" class="hover:bg-gray-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ user.id }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center text-white font-semibold text-sm">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                </div>
                                <div class="ml-3">
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
                            <Badge :variant="getRoleVariant(user.role?.name)">
                                {{ user.role?.name || 'Не вказано' }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ formatDate(user.created_at) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex gap-3">
                                <Link
                                    :href="route('admin.users.edit', user.id)"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    Редагувати
                                </Link>
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
            <EmptyState v-else>
                <template #title>
                    {{ filters.search || filters.role ? 'Користувачів не знайдено' : 'Користувачів поки немає' }}
                </template>
                <template #description>
                    {{ filters.search || filters.role ? 'Спробуйте змінити параметри пошуку' : 'Зареєструйте першого користувача' }}
                </template>
            </EmptyState>

            <!-- Пагинация -->
            <div v-if="users.data.length" class="mt-6">
                <Pagination :pagination="users" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    users: Object,
    roles: Array,
    filters: Object,
});

const { auth } = usePage().props;

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
        onSuccess: () => {},
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
const getRoleVariant = (roleName) => {
    const map = {
        admin: 'red',
        worker: 'blue',
        client: 'green',
    };
    return map[roleName] || 'gray';
};
</script>
