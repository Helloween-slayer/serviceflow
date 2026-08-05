<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto">
            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">⭐ Залишити відгук</h1>
                <p class="text-gray-600 mt-1">Оцініть роботу виконавця</p>
            </div>

            <Card>
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Рейтинг -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Оцінка <span class="text-red-500">*</span>
                        </label>
                        <div class="flex gap-2">
                            <button
                                v-for="i in 5"
                                :key="i"
                                type="button"
                                @click="form.rating = i"
                                class="text-4xl transition hover:scale-110"
                                :class="i <= form.rating ? 'text-yellow-400' : 'text-gray-300'"
                            >
                                ★
                            </button>
                        </div>
                        <p v-if="form.errors.rating" class="text-red-500 text-sm mt-1">
                            {{ form.errors.rating }}
                        </p>
                    </div>

                    <!-- Комментарий -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            Коментар
                        </label>
                        <textarea
                            v-model="form.comment"
                            rows="4"
                            placeholder="Розкажіть про досвід роботи з виконавцем..."
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        ></textarea>
                    </div>

                    <!-- Кнопки -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-200">
                        <Link :href="route('orders.show', order.id)" class="text-sm text-gray-600 hover:text-gray-900">
                            Скасувати
                        </Link>
                        <Button type="submit" :loading="form.processing" variant="primary">
                            Надіслати відгук
                        </Button>
                    </div>
                </form>
            </Card>
        </div>
    </AppLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import Card from '@/Components/UI/Card.vue';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    order: Object,
});

const form = useForm({
    order_id: props.order.id,
    rating: 5,
    comment: '',
});

const submit = () => {
    form.post(route('client.reviews.store'), {
        onSuccess: () => {
            form.reset();
        },
    });
};
</script>
