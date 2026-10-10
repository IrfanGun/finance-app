<script setup>
import { computed, ref, watch } from 'vue';
import { Check, Search, X } from 'lucide-vue-next';
import {
    categoryIconEntries,
    categoryColors,
} from './categoryIcons';
import {
    getIconCategory,
    iconCategories,
} from './iconCategoryLibrary';

const props = defineProps({
    open: Boolean,
    form: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(['close', 'save']);
const visibleIconCount = ref(14);
const showIconPicker = ref(false);
const iconSearch = ref('');
const selectedIconCategory = ref('All');
const iconPickerLimit = ref(20);
const iconEntries = computed(() => categoryIconEntries);
const visibleIcons = computed(() => {
    const icons = iconEntries.value.slice(0, visibleIconCount.value);
    const selectedIcon = iconEntries.value.find(([key]) => key === props.form.icon);

    if (selectedIcon && !icons.some(([key]) => key === selectedIcon[0])) {
        icons.push(selectedIcon);
    }

    return icons;
});
const hasMoreIcons = computed(() => visibleIconCount.value < iconEntries.value.length);

const iconPickerEntries = computed(() => {
    const search = iconSearch.value.trim().toLowerCase();

    return iconEntries.value.filter(([name]) => {
        const matchesSearch = !search || name.toLowerCase().includes(search);
        const matchesCategory = selectedIconCategory.value === 'All'
            || getIconCategory(name) === selectedIconCategory.value;

        return matchesSearch && matchesCategory;
    });
});

const visibleIconPickerEntries = computed(() => iconPickerEntries.value.slice(0, iconPickerLimit.value));
const hasMorePickerIcons = computed(() => iconPickerLimit.value < iconPickerEntries.value.length);

watch(() => props.open, (isOpen) => {
    if (isOpen) {
        visibleIconCount.value = 14;
    }
});

const loadMoreIcons = () => {
    iconSearch.value = '';
    selectedIconCategory.value = 'All';
    iconPickerLimit.value = 20;
    showIconPicker.value = true;
};

const selectIcon = (iconName) => {
    props.form.icon = iconName;
    showIconPicker.value = false;
};

const loadMorePickerIcons = () => {
    iconPickerLimit.value += 20;
};

const selectIconCategory = (category) => {
    selectedIconCategory.value = category;
    iconPickerLimit.value = 20;
};
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-30 flex items-end bg-black/30"
        @click.self="emit('close')"
    >
        <form
            class="mx-auto max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-t-[28px] bg-white px-6 pb-8 pt-3"
            @submit.prevent="emit('save')"
        >
            <div class="mx-auto mb-5 h-1 w-9 rounded-full bg-[#D5DBE5]"></div>

            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-[22px] font-semibold">
                        {{ form.id ? 'Edit category' : 'New category' }}
                    </h2>
                    <p class="text-sm text-[#637083]">Organize your money, your way.</p>
                </div>
                <button
                    type="button"
                    aria-label="Close category form"
                    @click="emit('close')"
                >
                    <X class="text-[#637083]" />
                </button>
            </div>

            <div class="mt-5 grid grid-cols-2 rounded-xl bg-[#F5F7FA] p-1">
                <button
                    v-for="type in ['expense', 'income']"
                    :key="type"
                    type="button"
                    class="rounded-lg py-3 text-sm font-semibold capitalize"
                    :class="form.type === type ? 'bg-white text-[#2457DA] shadow-sm' : 'text-[#637083]'"
                    :aria-pressed="form.type === type"
                    @click="form.type = type"
                >
                    {{ type }}
                </button>
            </div>

            <label class="mt-5 block" for="category-name">Category name</label>
            <input
                id="category-name"
                v-model="form.name"
                required
                placeholder="e.g. Food & drinks"
                class="mt-2 h-[42px] w-full rounded-lg border border-slate-300 px-4 outline-none focus:border-[#2457DA]"
            />
            <p v-if="form.errors.name" class="mt-1 text-sm text-expense">
                {{ form.errors.name }}
            </p>

            <p class="mt-5 text-sm">Icon</p>
            <div class="mt-2 grid grid-cols-7 gap-2 sm:grid-cols-8">
                <button
                    v-for="[key, Icon] in visibleIcons"
                    :key="key"
                    type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-xl border border-transparent bg-[#F5F7FA] text-[#637083]"
                    :style="form.icon === key
                        ? {
                            borderColor: form.color,
                            color: form.color,
                            backgroundColor: form.color + '18',
                        }
                        : {}"
                    :aria-label="`Select ${key} icon`"
                    :aria-pressed="form.icon === key"
                    :title="key"
                    @click="form.icon = key"
                >
                    <component :is="Icon" :size="20" aria-hidden="true" />
                </button>
            </div>
            <button
                v-if="hasMoreIcons"
                type="button"
                class="mt-3 rounded-lg px-3 py-2 text-sm font-semibold text-[#2457DA] transition hover:bg-blue-50"
                @click="loadMoreIcons"
            >
                Browse all icons
            </button>
            <p v-if="form.errors.icon" class="mt-1 text-sm text-expense">
                {{ form.errors.icon }}
            </p>

            <div class="mt-5 flex items-center justify-between">
                <span class="text-sm font-medium">Category status</span>
                <button
                    type="button"
                    role="switch"
                    :aria-checked="form.is_active"
                    class="relative h-7 w-12 rounded-full transition"
                    :class="form.is_active ? 'bg-app-primary' : 'bg-slate-300'"
                    @click="form.is_active = !form.is_active"
                >
                    <span
                        class="absolute top-1 h-5 w-5 rounded-full bg-white shadow transition"
                        :class="form.is_active ? 'left-6' : 'left-1'"
                    ></span>
                </button>
            </div>

            <p class="mt-5 text-sm">Color</p>
            <div class="mt-2 flex flex-wrap gap-4">
                <button
                    v-for="color in categoryColors"
                    :key="color"
                    type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-full ring-offset-2 focus:outline-none focus:ring-2 focus:ring-[#2457DA]"
                    :style="{ backgroundColor: color }"
                    :aria-label="`Select ${color} color`"
                    :aria-pressed="form.color === color"
                    @click="form.color = color"
                >
                    <Check
                        v-if="form.color === color"
                        :size="16"
                        class="text-white"
                        aria-hidden="true"
                    />
                </button>

                <label
                    class="relative flex h-8 w-8 cursor-pointer items-center justify-center overflow-hidden rounded-full border border-slate-300 bg-white ring-offset-2 transition focus-within:outline-none focus-within:ring-2 focus-within:ring-[#2457DA]"
                    :style="!categoryColors.includes(form.color) ? { backgroundColor: form.color } : {}"
                    title="Choose a custom color"
                >
                    <Check
                        v-if="!categoryColors.includes(form.color)"
                        :size="16"
                        class="pointer-events-none text-white"
                        aria-hidden="true"
                    />
                    <span
                        v-else
                        class="pointer-events-none text-lg leading-none text-[#637083]"
                        aria-hidden="true"
                    >
                        +
                    </span>
                    <input
                        v-model="form.color"
                        type="color"
                        class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        aria-label="Choose a custom category color"
                    />
                </label>
            </div>
            <p v-if="form.errors.color" class="mt-1 text-sm text-expense">
                {{ form.errors.color }}
            </p>

            <button
                class="mt-6 h-[53px] w-full rounded-xl bg-[#2457DA] text-white disabled:opacity-60"
                :disabled="form.processing"
            >
                {{ form.id ? 'Save changes' : 'Create category' }}
            </button>
        </form>

        <div
            v-if="showIconPicker"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 p-0 sm:items-center sm:p-6"
            @click.self="showIconPicker = false"
            @keydown.esc="showIconPicker = false"
        >
            <section class="flex max-h-[90vh] w-full max-w-4xl flex-col rounded-t-[28px] bg-white shadow-2xl sm:rounded-3xl">
                <header class="flex items-start justify-between border-b border-slate-100 px-5 py-4 sm:px-7">
                    <div>
                        <h3 class="text-xl font-semibold">Choose an icon</h3>
                        <p class="mt-1 text-sm text-[#637083]">
                            Browse {{ iconEntries.length.toLocaleString() }} Lucide icons by category.
                        </p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full p-2 text-[#637083] hover:bg-slate-100"
                        aria-label="Close icon picker"
                        @click="showIconPicker = false"
                    >
                        <X :size="19" />
                    </button>
                </header>

                <div class="space-y-4 border-b border-slate-100 px-5 py-4 sm:px-7">
                    <label class="flex h-11 items-center gap-2 rounded-xl border border-slate-200 px-3 focus-within:border-[#2457DA] focus-within:ring-2 focus-within:ring-blue-100">
                        <Search :size="18" class="shrink-0 text-slate-400" />
                        <input
                            v-model="iconSearch"
                            type="search"
                            class="h-full w-full border-0 p-0 text-sm outline-none focus:ring-0"
                            placeholder="Search icons"
                            aria-label="Search icons"
                            @input="iconPickerLimit = 20"
                        />
                    </label>

                    <div class="flex gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                        <button
                            v-for="category in iconCategories"
                            :key="category"
                            type="button"
                            class="shrink-0 rounded-full px-3 py-1.5 text-sm font-medium transition"
                            :class="selectedIconCategory === category
                                ? 'bg-[#2457DA] text-white'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                            :aria-pressed="selectedIconCategory === category"
                            @click="selectIconCategory(category)"
                        >
                            {{ category }}
                        </button>
                    </div>
                </div>

                <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4 sm:px-7">
                    <p class="mb-3 text-xs font-medium text-slate-500">
                        Showing {{ visibleIconPickerEntries.length }} of
                        {{ iconPickerEntries.length.toLocaleString() }} icons
                        <span v-if="selectedIconCategory !== 'All'">
                            in {{ selectedIconCategory }}
                        </span>
                    </p>

                    <div
                        v-if="visibleIconPickerEntries.length"
                        class="grid grid-cols-4 gap-2 sm:grid-cols-6 md:grid-cols-8 lg:grid-cols-10"
                    >
                        <button
                            v-for="[key, Icon] in visibleIconPickerEntries"
                            :key="key"
                            type="button"
                            class="flex min-h-[76px] flex-col items-center justify-center gap-1.5 rounded-xl border p-2 text-center transition hover:border-[#2457DA] hover:bg-blue-50"
                            :class="form.icon === key ? 'border-transparent' : 'border-slate-100 text-slate-600'"
                            :style="form.icon === key
                                ? {
                                    borderColor: form.color,
                                    color: form.color,
                                    backgroundColor: form.color + '18',
                                }
                                : {}"
                            :title="key"
                            :aria-label="`Select ${key} icon`"
                            :aria-pressed="form.icon === key"
                            @click="selectIcon(key)"
                        >
                            <component :is="Icon" :size="21" aria-hidden="true" />
                            <span class="w-full truncate text-[10px]">{{ key }}</span>
                        </button>
                    </div>

                    <p v-else class="py-12 text-center text-sm text-slate-500">
                        No icons found. Try another search or category.
                    </p>

                    <button
                        v-if="hasMorePickerIcons"
                        type="button"
                        class="mx-auto mt-5 block rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-[#2457DA] transition hover:bg-blue-50"
                        @click="loadMorePickerIcons"
                    >
                        Load more icons
                    </button>
                </div>
            </section>
        </div>
    </div>
</template>
