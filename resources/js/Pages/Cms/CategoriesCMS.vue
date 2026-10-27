<script setup>
import { computed, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, Heart, Pencil, Plus } from 'lucide-vue-next';
import CategoryFormModal from '@/Components/Categories/CategoryFormModal.vue';
import { useCategoryForm } from '@/Composables/useCategoryForm';
import { categoryIcons } from '@/Components/Categories/categoryIcons';

const props = defineProps({ categories: { type: Array, default: () => [] } });
const activeFilter = ref('all');
const { open, form, create, edit, save, toggleActive, close } = useCategoryForm();
const filters = [
    { label: 'All categories', value: 'all' },
    { label: 'Expenses', value: 'expense' },
    { label: 'Income', value: 'income' },
];
const visibleCategories = computed(() => props.categories.filter((category) => activeFilter.value === 'all' || category.type === activeFilter.value));
</script>

<template>

    <Head title="CMS Categories" />
    <div class="space-y-7">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-[#8792A2]">{{ $t("Organization") }}</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#17243A]">{{ $t("Categories") }}</h1>
            </div>
            <button
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#2457DA] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-200 hover:bg-[#1947BE]"
                @click="create">
                <Plus :size="17" /> {{ $t("New category") }} </button>
        </header>
        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">{{ $t("Total categories") }}</p>
                <p class="mt-3 text-2xl font-semibold text-[#17243A]">{{ categories.length }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="flex items-center gap-2 text-sm text-[#8792A2]">
                    <ArrowUpRight :size="16" class="text-[#C45B4B]" /> {{ $t("Expense categories") }} </p>
                <p class="mt-3 text-2xl font-semibold text-[#17243A]">{{categories.filter((category) => category.type
                    === 'expense').length }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="flex items-center gap-2 text-sm text-[#8792A2]">
                    <ArrowDownLeft :size="16" class="text-[#16815B]" /> {{ $t("Income categories") }} </p>
                <p class="mt-3 text-2xl font-semibold text-[#17243A]">{{categories.filter((category) => category.type
                    === 'income').length }}</p>
            </article>
        </section>
        <section
            class="overflow-hidden rounded-2xl border border-[#E9EDF3] bg-white shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#EEF1F5] px-6 py-4">
                <div>
                    <h2 class="font-semibold text-[#17243A]">{{ $t("Your categories") }}</h2>
                    <p class="mt-1 text-xs text-[#98A2B3]">{{ $t("Enable or disable categories as your needs change.") }}</p>
                </div>
                <div class="flex gap-2"><button v-for="filter in filters" :key="filter.value"
                        class="rounded-lg px-3.5 py-2 text-sm font-semibold"
                        :class="activeFilter === filter.value ? 'bg-[#EEF3FF] text-[#2457DA]' : 'text-[#7B8798] hover:bg-[#F7F8FA]'"
                        @click="activeFilter = filter.value">{{ filter.label }}</button></div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[640px] text-left">
                    <thead class="bg-[#FAFBFC] text-[11px] font-semibold uppercase tracking-[0.12em] text-[#98A2B3]">
                        <tr>
                            <th class="px-6 py-3">{{ $t("Category") }}</th>
                            <th class="px-5 py-3">{{ $t("Type") }}</th>
                            <th class="px-5 py-3">{{ $t("Status") }}</th>
                            <th class="px-6 py-3 text-right">{{ $t("Actions") }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F2F6]">
                        <tr v-for="category in visibleCategories" :key="category.id" class="hover:bg-[#FCFDFE]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3"><span
                                        class="flex h-10 w-10 items-center justify-center rounded-xl"
                                        :style="{ backgroundColor: `${category.color}18`, color: category.color }">
                                        <component :is="categoryIcons[category.icon] || Heart" :size="19" />
                                    </span><span class="text-sm font-semibold text-[#26354C]">{{ category.name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4"><span class="inline-flex items-center gap-2 text-sm capitalize"
                                    :class="category.type === 'income' ? 'text-[#16815B]' : 'text-[#C45B4B]'">
                                    <component :is="category.type === 'income' ? ArrowDownLeft : ArrowUpRight"
                                        :size="15" />{{ category.type }}
                                </span></td>
                            <td class="px-5 py-4"><span class="rounded-full px-3 py-1 text-xs font-semibold"
                                    :class="category.is_active ? 'bg-[#EFFAF5] text-[#16815B]' : 'bg-[#F3F5F8] text-[#7B8798]'">{{
                                        category.is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-end gap-3"><button
                                        v-if="category.name.toLowerCase() !== 'other'" type="button" role="switch"
                                        :aria-checked="category.is_active"
                                        class="relative h-6 w-11 rounded-full transition"
                                        :class="category.is_active ? 'bg-[#2457DA]' : 'bg-slate-300'"
                                        :aria-label="`Toggle ${category.name}`" @click="toggleActive(category)"><span
                                            class="absolute top-1 h-4 w-4 rounded-full bg-white shadow transition"
                                            :class="category.is_active ? 'left-6' : 'left-1'"></span></button><button
                                        v-if="category.name.toLowerCase() !== 'other'" type="button"
                                        class="rounded-lg p-2 text-[#7B8798] hover:bg-[#EEF3FF] hover:text-[#2457DA]"
                                        :aria-label="`Edit ${category.name}`" @click="edit(category)">
                                        <Pencil :size="16" />
                                    </button><span v-else class="text-xs text-[#98A2B3]">{{ $t("System category") }}</span></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!visibleCategories.length" class="px-6 py-12 text-center text-sm text-[#8792A2]">{{ $t("No categories in this group yet.") }}</p>
        </section>
        <CategoryFormModal :open="open" :form="form" cms @close="close" @save="save" />
    </div>
</template>
