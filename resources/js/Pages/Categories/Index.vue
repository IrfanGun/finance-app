<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { Home, Plus } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import CategoryCard from '@/Components/Categories/CategoryCard.vue';
import CategoryFormModal from '@/Components/Categories/CategoryFormModal.vue';
import { useCategoryForm } from '@/Composables/useCategoryForm';

const props = defineProps({ categories: { type: Array, default: () => [] } });
const filters = ['all', 'expense', 'income'];
const activeFilter = ref('all');
const { open, form, create, edit, save, toggleActive, close } = useCategoryForm();
const categoriesByType = (type) => props.categories.filter((category) => category.type === type && (activeFilter.value === 'all' || activeFilter.value === type));
</script>

<template>
    <Head title="Categories" />
    <div class="mx-auto min-h-screen w-full max-w-lg pb-28 text-app-heading">
        <header class="flex items-center justify-between bg-white px-6 py-5">
            <Link :href="dashboard.url()"><Home :size="22" class="text-[#2457DA]" /></Link>
            <h1 class="text-lg font-semibold">Categories</h1>
            <button class="rounded-full bg-app-primary p-2 text-white" @click="create"><Plus :size="18" /></button>
        </header>
        <main class="space-y-5 px-6 pt-6">
            <div class="flex gap-3"><button v-for="filter in filters" :key="filter" type="button" class="rounded-full px-5 py-2.5 text-sm font-semibold capitalize transition" :class="activeFilter === filter ? 'bg-app-primary text-white shadow-sm' : 'bg-app-primary/5 text-app-primary'" @click="activeFilter = filter">{{ filter }}</button></div>
            <section v-for="type in ['expense', 'income']" :key="type" class="space-y-3"><CategoryCard v-for="category in categoriesByType(type)" :key="category.id" :category="category" @edit="edit" @toggle="toggleActive" /></section>
        </main>
        <CategoryFormModal :open="open" :form="form" @close="close" @save="save" />
        <MobileBottomNav />
    </div>
</template>
