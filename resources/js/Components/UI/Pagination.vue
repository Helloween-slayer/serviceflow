<template>
    <div v-if="pagination.last_page > 1" class="flex items-center justify-between mt-6">
        <div class="flex-1 flex justify-between sm:hidden">
            <Button :disabled="!pagination.prev_page_url" @click="goToPage(pagination.prev_page_url)">
                Попередня
            </Button>
            <Button :disabled="!pagination.next_page_url" @click="goToPage(pagination.next_page_url)">
                Наступна
            </Button>
        </div>
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-gray-700">
                    Показано
                    <span class="font-medium">{{ pagination.from }}</span>
                    -
                    <span class="font-medium">{{ pagination.to }}</span>
                    з
                    <span class="font-medium">{{ pagination.total }}</span>
                </p>
            </div>
            <div>
                <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm">
                    <a
                        v-for="(link, index) in pagination.links"
                        :key="index"
                        :href="link.url || '#'"
                        :class="[
                            'relative inline-flex items-center px-4 py-2 text-sm font-semibold',
                            link.active
                                ? 'z-10 bg-blue-600 text-white focus:z-20'
                                : 'text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50',
                            !link.url ? 'cursor-not-allowed opacity-50' : '',
                            index === 0 ? 'rounded-l-md' : '',
                            index === pagination.links.length - 1 ? 'rounded-r-md' : '',
                        ]"
                        v-html="link.label"
                    />
                </nav>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import Button from '@/Components/UI/Button.vue';

const props = defineProps({
    pagination: { type: Object, required: true },
});

const goToPage = (url) => {
    if (url) {
        router.visit(url);
    }
};
</script>
