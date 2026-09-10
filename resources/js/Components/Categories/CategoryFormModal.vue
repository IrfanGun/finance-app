<script setup>
import { X } from 'lucide-vue-next';
import { categoryIcons, categoryColors } from './categoryIcons';

defineProps({ open: Boolean, form: { type: Object, required: true } });
defineEmits(['close', 'save']);
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-30 flex items-end bg-black/30">
        <form class="mx-auto w-full max-w-lg rounded-t-[28px] bg-white px-6 pb-8 pt-3" @submit.prevent="$emit('save')">
            <div class="mx-auto mb-5 h-1 w-9 rounded-full bg-[#D5DBE5]"></div>
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-[22px] font-semibold">{{ form.id ? 'Edit category' : 'New category' }}</h2>
                    <p class="text-sm text-[#637083]">Organize your money, your way.</p>
                </div><button type="button" @click="$emit('close')">
                    <X class="text-[#637083]" />
                </button>
            </div>
            <div class="mt-5 grid grid-cols-2 rounded-xl bg-[#F5F7FA] p-1"><button v-for="type in ['expense', 'income']"
                    :key="type" type="button" class="rounded-lg py-3 text-sm font-semibold capitalize"
                    :class="form.type === type ? 'bg-white text-[#2457DA] shadow-sm' : 'text-[#637083]'"
                    @click="form.type = type">{{ type }}</button></div>
            <label class="mt-5 block">Category name<input v-model="form.name" required placeholder="e.g. Food & drinks"
                    class="mt-2 h-[42px] w-full rounded-lg border border-slate-300 px-4 outline-none focus:border-[#2457DA]" /></label>
            <p class="mt-5 text-sm">Icon</p>
            <div class="mt-2 flex flex-wrap gap-3"><button v-for="(Icon, key) in categoryIcons" :key="key" type="button"
                    class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#F5F7FA]"
                    :class="form.icon === key ? 'border border-[#2457DA] text-[#2457DA]' : 'text-[#637083]'"
                    @click="form.icon = key">
                    <component :is="Icon" :size="20" />
                </button></div>
            <div class="mt-5 flex items-center justify-between"><span class="text-sm font-medium">Category
                    status</span>
                    <button type="button" role="switch" :aria-checked="form.is_active"
                    class="relative h-7 w-12 rounded-full transition"
                    :class="form.is_active ? 'bg-app-primary' : 'bg-slate-300'"
                    @click="form.is_active = !form.is_active">
                        <span
                            class="absolute top-1 h-5 w-5 rounded-full bg-white shadow transition"
                            :class="form.is_active ? 'left-6' : 'left-1'">
                        </span>
                    </button></div>
            <p class="mt-5 text-sm">Color</p>
            <div class="mt-2 flex justify-between"><button v-for="color in categoryColors" :key="color" type="button"
                    class="h-8 w-8 rounded-full" :style="{ backgroundColor: color }" @click="form.color = color"><span
                        v-if="form.color === color" class="text-white">✓</span></button></div>
            <button class="mt-6 h-[53px] w-full rounded-xl bg-[#2457DA] text-white disabled:opacity-60"
                :disabled="form.processing">{{ form.id ? 'Save changes' : 'Create category' }}</button>
        </form>
    </div>
</template>
