<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowLeft, ArrowUpRight, SlidersHorizontal, X } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import DatePicker from '@/Components/DatePicker.vue';
import SelectInput from '@/Components/SelectInput.vue';

const props = defineProps({
    transactions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    assets: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});
const showFilter = ref(false);
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const assetIds = ref([].concat(props.filters.asset_id ?? []).filter(Boolean));
const categoryIds = ref([].concat(props.filters.category_id ?? []).filter(Boolean));
const transactionType = ref('all');
const currency = (value) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));
const filteredTransactions = computed(() => transactionType.value === 'all'
    ? props.transactions
    : props.transactions.filter((transaction) => transaction.type === transactionType.value));
const toggleSelection = (items, id) => {
    const index = items.indexOf(id);

    if (index === -1) {
        items.push(id);

        return;
    }

    items.splice(index, 1);
};
const applyFilter = () => router.get('/transactions', { from: from.value || undefined, to: to.value || undefined, asset_id: assetIds.value.length ? assetIds.value : undefined, category_id: categoryIds.value.length ? categoryIds.value : undefined }, { preserveState: true, preserveScroll: true, onSuccess: () => { showFilter.value = false; } });
const clearFilter = () => { from.value = ''; to.value = ''; assetIds.value = []; categoryIds.value = []; applyFilter(); };
</script>

<template>
    <Head title="Transactions" />
    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header class="flex items-center justify-between px-6 py-5"><Link :href="dashboard.url()"><ArrowLeft :size="22" class="text-[#2457DA]" /></Link><h1 class="text-lg font-semibold">All transactions</h1><button class="flex items-center gap-1 text-sm font-medium text-[#2457DA]" @click="showFilter = true"><SlidersHorizontal :size="18" /> Filter</button></header>
        <main class="space-y-4 px-6 pt-5">            <div class="flex gap-3">
                <button v-for="tab in [{ label: 'All', value: 'all' }, { label: 'Expense', value: 'expense' }, { label: 'Income', value: 'income' }]" :key="tab.value" type="button" class="rounded-full px-5 py-2.5 text-sm font-semibold capitalize transition" :class="transactionType === tab.value ? 'bg-app-primary text-white shadow-sm' : 'bg-app-primary/5 text-app-primary'" @click="transactionType = tab.value">
                    {{ tab.label }}
                </button>
            </div><div v-if="from || to || assetIds.length || categoryIds.length" class="flex items-center justify-between rounded-xl bg-[#EEF3FF] px-4 py-3 text-xs text-[#2457DA]"><span>Filters applied</span><button class="font-semibold" @click="clearFilter">Clear</button></div><p v-if="!filteredTransactions.length" class="py-10 text-center text-sm text-[#637083]">No transactions found for this period.</p><article v-for="transaction in filteredTransactions" :key="transaction.id" class="flex items-center gap-4 border-b border-[#EEF1F5] pb-4"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F5F7FA]" :class="transaction.type === 'income' ? 'text-[#16815B]' : 'text-[#B34735]'"><component :is="transaction.type === 'income' ? ArrowDown : ArrowUpRight" :size="20" /></span><div class="min-w-0 flex-1"><p class="truncate text-sm font-medium">{{ transaction.title || transaction.note || 'Transaction' }}</p><p class="truncate text-xs text-[#637083]">{{ transaction.category?.name || 'Uncategorized' }} &middot; {{ transaction.account?.name || 'No asset' }} &middot; {{ transaction.date }}</p></div><strong class="shrink-0 text-sm" :class="transaction.type === 'income' ? 'text-[#16815B]' : ''">{{ transaction.type === 'income' ? '+' : '-' }} Rp {{ currency(transaction.amount) }}</strong></article></main>
        <div v-if="showFilter" class="fixed inset-0 z-30 flex items-end bg-black/30" @click.self="showFilter = false"><form class="mx-auto w-full max-w-lg rounded-t-[28px] bg-white px-6 pb-8 pt-3 text-[#152338]" @submit.prevent="applyFilter"><div class="mx-auto mb-5 h-1 w-9 rounded-full bg-[#D5DBE5]"></div><div class="flex items-start justify-between"><div><p class="text-xs font-medium text-[#637083]">Filter transactions</p><h2 class="text-[22px] font-semibold">Filter Activity</h2><p class="text-sm text-[#637083]">Find transactions using the filters below.</p></div><button type="button" class="text-2xl text-[#637083]" @click="showFilter = false">&times;</button></div><div class="mt-5 grid grid-cols-2 gap-3"><label class="text-xs font-medium">From<DatePicker v-model="from" placeholder="Select date" /></label><label class="text-xs font-medium">To<DatePicker v-model="to" placeholder="Select date" /></label></div><div class="mt-4"><p class="text-xs font-medium">Asset</p><div class="mt-2 flex gap-2 overflow-x-auto pb-1"> <button v-for="asset in props.assets" :key="asset.id" type="button" class="whitespace-nowrap rounded-lg border px-4 py-2 text-sm" :class="assetIds.includes(asset.id) ? 'bg-[#2457DA] text-white' : 'border-slate-300 text-[#637083]'" @click="toggleSelection(assetIds, asset.id)">{{ asset.name }}</button></div></div><div class="mt-4"><p class="text-xs font-medium">Category</p><div class="mt-2 flex gap-2 overflow-x-auto pb-1"> <button v-for="category in props.categories" :key="category.id" type="button" class="whitespace-nowrap rounded-lg border px-4 py-2 text-sm" :class="categoryIds.includes(category.id) ? 'bg-[#2457DA] text-white' : 'border-slate-300 text-[#637083]'" @click="toggleSelection(categoryIds, category.id)">{{ category.name }}</button></div></div><div class="mt-6 grid grid-cols-2 gap-3"><button type="button" class="h-[53px] rounded-xl border border-[#2457DA] text-[#2457DA]" @click="clearFilter">Clear</button><button class="h-[53px] rounded-xl bg-[#2457DA] text-white">Show results</button></div></form></div>
        <MobileBottomNav />
    </div>
</template>



