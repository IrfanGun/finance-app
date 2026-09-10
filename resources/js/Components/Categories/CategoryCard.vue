<script setup>
import { Link } from '@inertiajs/vue3';
import { Pencil, Heart } from 'lucide-vue-next';
import { show } from '@/actions/App/Http/Controllers/CategoryController';
import { categoryIcons } from './categoryIcons';

defineProps({ category: { type: Object, required: true } });
const emit = defineEmits(['edit', 'toggle']);
const isFallback = (category) => category.name.toLowerCase() === 'other';
</script>

<template>
    <div class="flex items-center gap-3 rounded-xl bg-white p-4">
        <Link :href="show.url(category.id)" class="flex flex-1 items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl" :style="{ backgroundColor: category.color + '18', color: category.color }">
                <component :is="categoryIcons[category.icon] || Heart" :size="20" />
            </span>
            <span class="flex-1">
                <span class="block font-medium">{{ category.name }}</span>
                <span v-if="category.is_active" class="block text-sm" :class="category.type === 'income' ? 'text-income' : 'text-expense'">
                    {{ category.type === 'income' ? '+ ' : '- ' }}Rp 2.000.000
                </span>
            </span>
        </Link>
        <button v-if="!isFallback(category)" type="button" role="switch" :aria-checked="category.is_active" class="relative h-6 w-11 rounded-full transition" :class="category.is_active ? 'bg-app-primary' : 'bg-slate-300'" @click="emit('toggle', category)">
            <span class="absolute top-1 h-4 w-4 rounded-full bg-white shadow transition" :class="category.is_active ? 'left-6' : 'left-1'"></span>
        </button>
        <button v-if="!isFallback(category)" type="button" class="text-[#637083]" @click="emit('edit', category)"><Pencil :size="17" /></button>
    </div>
</template>
