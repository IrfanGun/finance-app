<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowLeft, ArrowUp, ArrowUpRight, Heart, Pencil, Receipt } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { index as categoriesIndex } from '@/routes/categories';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

defineProps({ category: Object, transactions: Array });
const categoryEdit = categoriesIndex;
</script>

<template>

    <Head :title="category.name" />
    <div class="mx-auto min-h-screen w-full max-w-lg bg-[#F5F7FA] pb-8 text-[#152338]">
        <header class="bg-white px-6 pb-6 pt-5">
            <div class="flex items-center justify-between">
                <Link :href="dashboard.url()">
                    <ArrowLeft :size="22" />
                </Link>
                <h1 class="text-lg font-semibold">Category details</h1>
                <Link :href="categoryEdit.url(category.id)" class="text-[#2457DA]">
                    <Pencil :size="19" />
                </Link>
            </div>
        </header>
        <main class="space-y-5 px-6 pt-6">
            <section class="rounded-2xl bg-white p-6 text-center shadow-sm"><span
                    class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl"
                    :style="{ backgroundColor: category.color + '18', color: category.color }">
                    <Heart :size="30" />
                </span>
                <h2 class="mt-4 text-2xl font-semibold">{{ category.name }}</h2>
                <p class="mt-1 text-sm capitalize text-[#637083]">{{ category.type }} category</p>
            </section>
            <section class="grid grid-cols-2 gap-3">
                <div class="rounded-xl bg-white p-4">
                    <p class="flex items-center gap-1 text-sm text-[#16815B]">
                        <ArrowDown :size="15" />Income
                    </p><strong class="mt-2 block text-lg">Rp 0</strong>
                </div>
                <div class="rounded-xl bg-white p-4">
                    <p class="flex items-center gap-1 text-sm text-[#B34735]">
                        <ArrowUp :size="15" />Expense
                    </p><strong class="mt-2 block text-lg">Rp 0</strong>
                </div>
            </section>
            <section class="rounded-xl bg-white p-5">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold">Transactions</h2><span class="text-sm text-[#637083]">
                        {{ transactions.length }} total</span>
                </div>
                <div v-if="!transactions.length"
                    class="flex flex-col items-center gap-3 py-10 text-center text-[#637083]">
                    <Receipt :size="30" />
                    <p class="text-sm">No transactions in this category yet.</p>
                </div>
                <div v-else class="mt-4 space-y-4">
                    <div v-for="transaction in transactions" :key="transaction.id" class="flex items-center gap-3"><span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#F5F7FA]">
                            <ArrowUpRight :size="18" />
                        </span><span class="flex-1 text-sm">{{ transaction.note || 'Transaction' }}</span>
                        <strong class="text-sm">Rp {{ transaction.amount }}</strong>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <MobileBottomNav />
</template>
