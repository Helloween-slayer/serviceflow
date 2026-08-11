<template>
    <AppLayout>
        <div class="max-w-7xl mx-auto">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900">📤 Заявки на виведення</h1>
                <p class="text-gray-600 mt-1">Управління запитами виконавців на виведення коштів</p>
            </div>

            <div v-if="withdrawals.data.length > 0" class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Користувач</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Сума</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Спосіб</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Статус</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Дії</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="withdrawal in withdrawals.data" :key="withdrawal.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ withdrawal.id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <p class="font-medium text-gray-900">{{ withdrawal.user.name }}</p>
                                <p class="text-sm text-gray-500">{{ withdrawal.user.email }}</p>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                            {{ withdrawal.amount }} ₴
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            {{ withdrawal.payment_method }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <Badge :variant="getStatusVariant(withdrawal.status)">
                                {{ getStatusText(withdrawal.status) }}
                            </Badge>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div v-if="withdrawal.status === 'pending'" class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="success"
                                    @click="updateStatus(withdrawal.id, 'completed')"
                                >
                                    ✅ Підтвердити
                                </Button>
                                <Button
                                    size="sm"
                                    variant="danger"
                                    @click="updateStatus(withdrawal.id, 'rejected')"
                                >
                                    ❌ Відхилити
                                </Button>
                            </div>
                            <span v-else class="text-sm text-gray-400">Оброблено</span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState v-else>
                <template #title>Немає заявок</template>
                <template #description>Заявки на виведення коштів поки відсутні</template>
            </EmptyState>

            <div v-if="withdrawals.data.length > 0" class="mt-6">
                <Pagination :pagination="withdrawals" />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    withdrawals: Object,
});

const getStatusText = (status) => {
    const map = {
        pending: 'Очікує',
        approved: 'Схвалено',
        completed: 'Виконано',
        rejected: 'Відхилено',
    };
    return map[status] || status;
};

const getStatusVariant = (status) => {
    const map = {
        pending: 'yellow',
        approved: 'blue',
        completed: 'green',
        rejected: 'red',
    };
    return map[status] || 'gray';
};

const updateStatus = (withdrawalId, status) => {
    const confirmMessage = status === 'completed'
        ? 'Підтвердити виведення коштів?'
        : 'Відхилити заявку на виведення?';

    if (!confirm(confirmMessage)) return;

    router.put(route('admin.withdrawals.update', withdrawalId), {
        status: status,
    }, {
        onSuccess: () => {
            router.reload();
        },
        onError: (errors) => {
            alert(errors.message || 'Не вдалося оновити статус');
        },
    });
};
</script>
