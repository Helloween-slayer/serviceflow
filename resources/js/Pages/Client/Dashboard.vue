<template>
    <AppLayout>
        <div class="max-w-5xl mx-auto px-6 py-8">
            <!-- Приветствие -->
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">
                    👋 Вітаємо, {{ $page.props.auth.user.name }}!
                </h1>
                <p class="text-gray-600 mt-1">Це ваша клієнтська панель</p>
            </div>

            <!-- Карточки -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- 1. Мої заявки -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-blue-600">
                            <span class="text-xl">📋</span>
                            <span class="font-semibold">Мої заявки</span>
                        </div>
                    </template>
                    <p class="text-sm text-gray-600">Переглянути всі свої заявки</p>
                    <Link
                        :href="route('client.orders.index')"
                        class="inline-block mt-3 text-sm text-blue-600 font-medium hover:text-blue-800 hover:underline"
                    >
                        Перейти →
                    </Link>
                </Card>

                <!-- 2. Створити заявку -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-green-600">
                            <span class="text-xl">➕</span>
                            <span class="font-semibold">Створити заявку</span>
                        </div>
                    </template>
                    <p class="text-sm text-gray-600">Створити нову заявку</p>
                    <Link
                        :href="route('client.orders.create')"
                        class="inline-block mt-3 text-sm text-green-600 font-medium hover:text-green-800 hover:underline"
                    >
                        Створити →
                    </Link>
                </Card>

                <!-- 3. Доступні заявки -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-purple-600">
                            <span class="text-xl">📋</span>
                            <span class="font-semibold">Доступні заявки</span>
                        </div>
                    </template>
                    <p class="text-sm text-gray-600">Переглянути всі доступні заявки</p>
                    <Link
                        :href="route('orders.index')"
                        class="inline-block mt-3 text-sm text-purple-600 font-medium hover:text-purple-800 hover:underline"
                    >
                        Переглянути →
                    </Link>
                </Card>

                <!-- 4. Мої відгуки -->
                <Card>
                    <template #header>
                        <div class="flex items-center gap-2 text-purple-600">
                            <span class="text-xl">⭐</span>
                            <span class="font-semibold">Мої відгуки</span>
                        </div>
                    </template>
                    <p class="text-sm text-gray-600">Відгуки, які ви залишили</p>
                    <Link
                        :href="route('client.reviews.index')"
                        class="inline-block mt-3 text-sm text-purple-600 font-medium hover:text-purple-800 hover:underline"
                    >
                        Переглянути →
                    </Link>
                </Card>
            </div>

            <!-- 💰 ПОПОВНЕННЯ БАЛАНСУ -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">💰 Баланс</h3>

                <div class="flex items-center gap-6 mb-4">
                    <span class="text-2xl font-bold text-green-600">
                        {{ $page.props.auth.user.balance }} ₴
                    </span>
                </div>

                <form @submit.prevent="deposit" class="flex flex-col sm:flex-row gap-3">
                    <div>
                        <Input
                            v-model="depositAmount"
                            type="number"
                            step="1"
                            min="1"
                            placeholder="Сума поповнення"
                            class="w-48"
                            required
                        />
                    </div>
                    <Button
                        type="submit"
                        :loading="depositing"
                        variant="success"
                    >
                        💳 Поповнити баланс
                    </Button>
                </form>

                <p class="text-xs text-gray-400 mt-2">
                    Поповнення через LiqPay (ПриватБанк). Мінімальна сума — 1 грн.
                </p>
            </div>

            <!-- Telegram виджет -->
            <div class="mt-8">
                <TelegramWidget />
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import TelegramWidget from '@/Components/Dashboard/TelegramWidget.vue';
import Input from '@/Components/UI/Input.vue';
import Button from '@/Components/UI/Button.vue';
import { Link } from '@inertiajs/vue3';

const depositAmount = ref('');
const depositing = ref(false);

const deposit = () => {
    if (!depositAmount.value || depositAmount.value < 1) {
        alert('Введіть коректну суму');
        return;
    }

    depositing.value = true;

    axios.post('/payment/deposit', {
        amount: depositAmount.value
    })
        .then(response => {
            depositing.value = false;
            depositAmount.value = '';

            // ✅ Перенаправляем на LiqPay
            if (response.data && response.data.redirect_url) {
                window.location.href = response.data.redirect_url;
            } else {
                alert('Перенаправлення на сторінку оплати...');
            }
        })
        .catch(error => {
            depositing.value = false;
            const message = error.response?.data?.message || 'Не вдалося поповнити баланс';
            alert(message);
        });
};
</script>
