<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import NavLink from '@/Components/NavLink.vue';

const { auth } = usePage().props;
const showDropdown = ref(false);

const user = computed(() => auth.user);

const menuItems = computed(() => {
    if (!user.value) {
        return [
            { label: '🏠 Головна', href: route('dashboard'), active: 'dashboard' }
        ];
    }

    const role = user.value.role_id;
    const items = [];

    // =============================================
    // 👇 ГЛАВНЫЕ ВКЛАДКИ ДЛЯ ВСЕХ (ЗАЯВКИ + ВИКОНАВЦІ)
    // =============================================
    items.push(
        { label: '📋 Заявки', href: route('orders.index'), active: 'orders.index' },
        { label: '👥 Виконавці', href: route('workers.index'), active: 'workers.index' }
    );

    // =============================================
    // 👇 ДАЛЕЕ ИДУТ ЛИЧНЫЕ КАБИНЕТЫ ПО РОЛЯМ
    // =============================================
    if (role === 1) { // Админ
        items.push(
            { label: '📊 Адмін-панель', href: route('admin.dashboard'), active: 'admin.dashboard' },
            { label: '👥 Користувачі', href: route('admin.users.index'), active: 'admin.users.index' },
            { label: '🏷️ Теги', href: route('admin.tags.index'), active: 'admin.tags.index' },
            { label: '⭐ Відгуки', href: route('admin.reviews.index'), active: 'admin.reviews.index' },
            { label: '📤 Виведення', href: route('admin.withdrawals.index'), active: 'admin.withdrawals.index' }
        );
    } else if (role === 2) { // Воркер
        items.push(
            { label: '📊 Мій дашборд', href: route('worker.dashboard'), active: 'worker.dashboard' },
            { label: '📋 Мої заявки', href: route('worker.orders.index'), active: 'worker.orders.index' },
            { label: '⭐ Мої відгуки', href: route('worker.reviews.index'), active: 'worker.reviews.index' },
            { label: '💰 Баланс', href: route('worker.balance.index'), active: 'worker.balance.index' }
        );
    } else if (role === 3) { // Клиент
        items.push(
            { label: '📊 Мій дашборд', href: route('client.dashboard'), active: 'client.dashboard' },
            { label: '➕ Створити заявку', href: route('client.orders.create'), active: 'client.orders.create' },
            { label: '📋 Мої заявки', href: route('client.orders.index'), active: 'client.orders.index' },
            { label: '⭐ Мої відгуки', href: route('client.reviews.index'), active: 'client.reviews.index' }
        );
    }

    return items;
});
</script>

<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Шапка -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-6">
                <div class="flex justify-between h-16">
                    <!-- Лого -->
                    <div class="flex items-center">
                        <Link :href="route('dashboard')">
                            <ApplicationLogo class="h-9 w-auto fill-current text-gray-800" />
                        </Link>
                    </div>

                    <!-- Меню -->
                    <div class="flex items-center space-x-6">
                        <template v-for="item in menuItems" :key="item.label">
                            <NavLink
                                :href="item.href"
                                :active="route().current(item.active)"
                            >
                                {{ item.label }}
                            </NavLink>
                        </template>

                        <!-- Профиль -->
                        <div v-if="user" class="relative">
                            <button
                                @click="showDropdown = !showDropdown"
                                class="flex items-center gap-2 text-sm font-medium text-gray-700 hover:text-gray-900"
                            >
                                {{ user.name }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            <div v-if="showDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                <Link :href="route('profile.edit')" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    ⚙️ Налаштування
                                </Link>
                                <Link :href="route('logout')" method="post" as="button" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    🚪 Вийти
                                </Link>
                            </div>
                        </div>

                        <Link v-else :href="route('login')" class="text-sm text-blue-500 hover:text-blue-700">
                            Увійти
                        </Link>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Контент -->
        <main class="max-w-7xl mx-auto px-6 py-8">
            <slot />
        </main>
    </div>
</template>
