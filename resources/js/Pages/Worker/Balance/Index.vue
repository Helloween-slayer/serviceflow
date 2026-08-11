<template>
    <AppLayout>
        <div class="max-w-4xl mx-auto">
            <!-- Заголовок -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">💰 Мій баланс</h1>
                <p class="text-gray-600 mt-1">Керування балансом та історія транзакцій</p>
            </div>

            <!-- Баланс -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-500">Доступний баланс</p>
                        <p class="text-3xl font-bold text-green-600">{{ balance }} ₴</p>
                    </div>
                    <button
                        @click="showWithdrawForm = !showWithdrawForm"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition"
                    >
                        {{ showWithdrawForm ? 'Скасувати' : '📤 Вивести кошти' }}
                    </button>
                </div>
            </div>

            <!-- Форма вывода -->
            <div v-if="showWithdrawForm" class="bg-gray-50 rounded-lg border border-gray-200 p-6 mb-6">
                <h3 class="font-semibold text-gray-900 mb-4">📤 Запит на виведення</h3>
                <form @submit.prevent="requestWithdraw" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Сума (₴)</label>
                        <Input
                            v-model="withdrawForm.amount"
                            type="number"
                            step="1"
                            min="1"
                            placeholder="Введіть суму для виведення"
                            :error="withdrawForm.errors.amount"
                        />
                        <p class="text-xs text-gray-400 mt-1">Максимум: {{ balance }} ₴</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Номер картки для виведення</label>
                        <Input
                            v-model="withdrawForm.payment_details"
                            placeholder="Введіть номер картки (наприклад, 4149 4999 9999 9999)"
                            :error="withdrawForm.errors.payment_details"
                        />
                        <p class="text-xs text-gray-400 mt-1">Ми перекажемо кошти на вказану картку</p>
                    </div>

                    <div class="flex gap-3">
                        <Button type="submit" :loading="withdrawForm.processing" variant="primary">
                            Надіслати запит
                        </Button>
                        <Button type="button" variant="secondary" @click="showWithdrawForm = false">
                            Скасувати
                        </Button>
                    </div>
                </form>
            </div>

            <!-- Вкладки -->
            <div class="flex gap-2 mb-6">
                <button
                    @click="activeTab = 'transactions'"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        activeTab === 'transactions'
                            ? 'bg-blue-500 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                >
                    📋 Історія операцій
                </button>
                <button
                    @click="activeTab = 'withdrawals'"
                    :class="[
                        'px-4 py-2 rounded-lg text-sm font-medium transition',
                        activeTab === 'withdrawals'
                            ? 'bg-blue-500 text-white'
                            : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
                    ]"
                >
                    📤 Заявки на виведення
                    <span v-if="pendingWithdrawalsCount > 0" class="ml-1 bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                        {{ pendingWithdrawalsCount }}
                    </span>
                </button>
            </div>

            <!-- Контент: Історія транзакцій -->
            <div v-if="activeTab === 'transactions'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div v-if="transactions.data.length > 0" class="space-y-3">
                    <div
                        v-for="transaction in transactions.data"
                        :key="transaction.id"
                        class="flex justify-between items-center border-b border-gray-100 pb-3 hover:bg-gray-50 transition px-2 rounded"
                    >
                        <div>
                            <p class="font-medium text-gray-800">
                                {{ getTransactionTypeText(transaction.type) }}
                            </p>
                            <p class="text-sm text-gray-500">
                                {{ transaction.description }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ formatDate(transaction.created_at) }}
                            </p>
                        </div>
                        <div>
                            <span
                                :class="[
                                    'font-semibold',
                                    transaction.amount > 0 ? 'text-green-600' : 'text-red-600'
                                ]"
                            >
                                {{ transaction.amount > 0 ? '+' : '' }}{{ transaction.amount }} ₴
                            </span>
                        </div>
                    </div>
                </div>

                <EmptyState v-else>
                    <template #title>Немає транзакцій</template>
                    <template #description>У вас поки немає операцій з балансом</template>
                </EmptyState>

                <!-- Пагинация -->
                <div v-if="transactions.data.length > 0" class="mt-4">
                    <Pagination :pagination="transactions" />
                </div>
            </div>

            <!-- Контент: Заявки на вывод -->
            <div v-if="activeTab === 'withdrawals'" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div v-if="withdrawals.length > 0" class="space-y-3">
                    <div
                        v-for="withdrawal in withdrawals"
                        :key="withdrawal.id"
                        class="flex justify-between items-center border-b border-gray-100 pb-3 hover:bg-gray-50 transition px-2 rounded"
                    >
                        <div>
                            <p class="font-medium text-gray-800">{{ withdrawal.amount }} ₴</p>
                            <p class="text-sm text-gray-500">
                                Картка: {{ withdrawal.payment_details }}
                            </p>
                            <p class="text-xs text-gray-400">
                                {{ formatDate(withdrawal.created_at) }}
                            </p>
                        </div>
                        <div>
                            <Badge :variant="getWithdrawalStatusVariant(withdrawal.status)">
                                {{ getWithdrawalStatusText(withdrawal.status) }}
                            </Badge>
                        </div>
                    </div>
                </div>

                <EmptyState v-else>
                    <template #title>Немає заявок</template>
                    <template #description>Ви ще не подавали заявки на виведення</template>
                </EmptyState>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import Badge from '@/Components/UI/Badge.vue';
import EmptyState from '@/Components/UI/EmptyState.vue';
import Pagination from '@/Components/UI/Pagination.vue';

const props = defineProps({
    balance: Number,
    transactions: Object,
    withdrawals: Array,
});

const activeTab = ref('transactions');

// Количество ожидающих заявок на вывод
const pendingWithdrawalsCount = computed(() => {
    return props.withdrawals.filter(w => w.status === 'pending').length;
});

const showWithdrawForm = ref(false);

const withdrawForm = useForm({
    amount: '',
    payment_details: '',
});

const requestWithdraw = () => {
    withdrawForm.post(route('worker.balance.withdraw'), {
        onSuccess: () => {
            showWithdrawForm.value = false;
            withdrawForm.reset();
            router.reload();
        },
    });
};

const getTransactionTypeText = (type) => {
    const map = {
        deposit: '💰 Поповнення',
        hold: '🔒 Блокування',
        release: '✅ Зачислення',
        withdrawal: '📤 Виведення',
    };
    return map[type] || type;
};

const getWithdrawalStatusText = (status) => {
    const map = {
        pending: '⏳ Очікує',
        approved: '✅ Схвалено',
        completed: '✔️ Виконано',
        rejected: '❌ Відхилено',
    };
    return map[status] || status;
};

const getWithdrawalStatusVariant = (status) => {
    const map = {
        pending: 'yellow',
        approved: 'blue',
        completed: 'green',
        rejected: 'red',
    };
    return map[status] || 'gray';
};

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('uk-UA');
};
</script>
