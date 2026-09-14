<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpRight, Banknote, Camera, ChartPie, ChevronRight, Flag, Plus } from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import FormInput from '@/Components/FormInput.vue';
import SelectInput from '@/Components/SelectInput.vue';
import DatePicker from '@/Components/DatePicker.vue';
import { index as categoriesIndex } from '@/routes/categories';
import { scan as receiptScan } from '@/routes/receipt';
import { index as assetsIndex } from '@/routes/assets';
import { store as transactionStore } from '@/actions/App/Http/Controllers/TransactionController';

const page = usePage();
const props = defineProps({
    categories: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({ income: 0, expense: 0, balance: 0 }) },
});

const userName = page.props.auth?.user?.name ?? 'User';
const showTransactionModal = ref(false);
const transactionStep = ref(1);
const transactionType = ref('expense');
const transactionForm = useForm({ type: 'expense', amount: '', date: new Date().toISOString().slice(0, 10), title: '', note: '', account_id: props.accounts[0]?.id ?? '', category_id: '' });
const isScrolled = ref(false);
const formatCurrency = (amount) => new Intl.NumberFormat('id-ID').format(Number(amount ?? 0));
const transactionCategory = (transaction) => transaction.category?.name ?? 'Uncategorized';
const transactionAmount = (transaction) => `${transaction.type === 'income' ? '+' : '-'} Rp ${formatCurrency(transaction.amount)}`;
const openTransactionModal = () => { transactionStep.value = 1; showTransactionModal.value = true; };
const submitTransaction = () => transactionForm.post(transactionStore.url(), { preserveScroll: true, onSuccess: () => { transactionForm.reset(); transactionForm.type = 'expense'; transactionForm.date = new Date().toISOString().slice(0, 10); transactionForm.account_id = props.accounts[0]?.id ?? ''; transactionStep.value = 1; showTransactionModal.value = false; } });

const categorySpending = computed(() => {
    const expenses = props.transactions.filter((transaction) => transaction.type === 'expense');
    const total = expenses.reduce((sum, transaction) => sum + Number(transaction.amount), 0);

    return props.categories
        .map((category) => {
            const amount = expenses
                .filter((transaction) => transaction.category_id === category.id)
                .reduce((sum, transaction) => sum + Number(transaction.amount), 0);

            return {
                ...category,
                amount,
                percentage: total ? Math.round((amount / total) * 100) : 0,
            };
        })
        .filter((category) => category.amount > 0)
        .sort((first, second) => second.amount - first.amount)
        .slice(0, 4);
});

const handleScroll = () => {
    isScrolled.value = window.scrollY > 40;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const shortcuts = [
    { icon: Plus, label: 'Add' },
    { icon: Camera, label: 'Scan', href: receiptScan.url() },
    { icon: ChartPie, label: 'Budget' },
    { icon: Flag, label: 'Plan' },
];
</script>

<template>
    <Head title="Dashboard" />

    <Transition name="mini-header">
        <div v-if="isScrolled" class="fixed inset-x-0 top-0 z-20 mx-auto flex h-16 w-full max-w-lg items-center justify-between bg-[#1F4AC2] px-6 text-white shadow-md">
            <div>
                <p class="text-xs text-blue-100">Good morning,</p>
                <h1 class="text-lg font-semibold">{{ userName }}</h1>
            </div>
            <div class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-xs font-semibold text-[#2457DA]">{{ userName.slice(0, 2).toUpperCase() }}</div>
        </div>
    </Transition>

    <div class="mx-auto flex min-h-screen w-full max-w-lg flex-col bg-app pb-24 text-app-heading">
        <header class="relative overflow-hidden bg-gradient-to-br from-[#11297A] via-[#1F4AC2] to-[#2969E6] px-6 pb-10 pt-5 text-white">
            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full border-[80px] border-white/10"></div>
            <div class="relative mt-5 flex items-center justify-between"><div><p class="text-sm text-blue-100">Good morning,</p><h1 class="text-2xl font-semibold">{{ userName }}</h1></div><div class="flex h-10 w-10 items-center justify-center rounded-full bg-white text-xs font-semibold text-[#2457DA]">{{ userName.slice(0, 2).toUpperCase() }}</div></div>
            <div class="relative mt-7"><p class="text-sm text-blue-100">Total balance</p><p class="mt-1 text-[32px] font-semibold">Rp {{ formatCurrency(summary.balance) }}</p></div>
            <div class="relative mt-7 grid grid-cols-4 gap-3 text-center text-xs"><div v-for="item in shortcuts" :key="item.label"><Link v-if="item.href" :href="item.href" class="block"><span class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#2457DA]"><component :is="item.icon" :size="19" /></span><span class="mt-1 block">{{ item.label }}</span></Link><template v-else><span class="mx-auto flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#2457DA]"><component :is="item.icon" :size="19" /></span><span class="mt-1 block">{{ item.label }}</span></template></div></div>
        </header>

        <main class="space-y-7 px-6 pt-6">
            <section><div class="flex items-center justify-between"><h2 class="text-lg font-semibold">This month</h2><span class="text-sm text-[#637083]">{{ new Date().toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) }}</span></div><div class="mt-4 grid grid-cols-2 gap-3"><div class="rounded-xl bg-[#F5F7FA] p-4"><p class="flex items-center gap-1 text-sm text-[#16815B]"><ArrowDown :size="15" />Income</p><p class="mt-2 text-lg font-semibold">Rp {{ formatCurrency(summary.income) }}</p></div><div class="rounded-xl bg-[#F5F7FA] p-4"><p class="flex items-center gap-1 text-sm text-[#B34735]"><ArrowUp :size="15" />Expense</p><p class="mt-2 text-lg font-semibold">Rp {{ formatCurrency(summary.expense) }}</p></div></div></section>
            <section><div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Spending by category</h2><button class="text-sm text-[#2457DA]" @click="router.visit(categoriesIndex.url())">Details</button></div><div v-if="categorySpending.length" class="mt-4 space-y-3"><div v-for="category in categorySpending" :key="category.id"><div class="flex items-center justify-between text-sm"><span>{{ category.name }}</span><strong>{{ category.percentage }}%</strong></div><div class="mt-1 h-2 rounded-full bg-[#EEF3FF]"><span class="block h-2 rounded-full bg-[#2457DA]" :style="{ width: `${category.percentage}%` }"></span></div></div></div><p v-else class="mt-4 text-sm text-[#637083]">No spending recorded yet.</p></section>
            <section><div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Recent transactions</h2><button class="text-sm text-[#2457DA]">See all</button></div><div v-if="transactions.length" class="mt-4 space-y-4"><div v-for="transaction in transactions" :key="transaction.id" class="flex items-center gap-4"><span class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#F5F7FA]" :class="transaction.type === 'income' ? 'text-[#16815B]' : 'text-[#B34735]'"><component :is="transaction.type === 'income' ? ArrowDown : ArrowUpRight" :size="20" /></span><div class="flex-1"><p class="text-sm font-medium">{{ transaction.note || 'Transaction' }}</p><p class="text-xs text-[#637083]">{{ transactionCategory(transaction) }} · {{ transaction.date }}</p></div><strong class="text-sm" :class="transaction.type === 'income' ? 'text-[#16815B]' : ''">{{ transactionAmount(transaction) }}</strong></div></div><p v-else class="mt-4 text-sm text-[#637083]">No transactions yet.</p></section>
        </main>

        <section class="mt-7 px-6"><div class="flex items-center justify-between"><h2 class="text-lg font-semibold">Accounts &amp; assets</h2><Link :href="assetsIndex.url()" class="flex items-center text-sm font-medium text-[#2457DA]">See all<ChevronRight :size="16" /></Link></div><div v-if="accounts.length" class="-mx-6 mt-4 flex gap-3 overflow-x-auto px-6 pb-1 [scrollbar-width:none] [&amp;::-webkit-scrollbar]:hidden"><article v-for="account in accounts" :key="account.id" class="min-w-[220px] rounded-xl bg-[#F5F7FA] p-4"><div class="flex items-center justify-between text-[#2457DA]"><span class="text-base font-medium">{{ account.name }}</span><Banknote :size="19" /></div><p class="mt-2 text-[23px] font-semibold tracking-tight">Rp {{ formatCurrency(account.current_balance) }}</p><p class="mt-1 text-xs text-[#637083] capitalize">{{ account.type }}</p></article></div><p v-else class="mt-4 text-sm text-[#637083]">No accounts yet.</p></section>
        <div v-if="showTransactionModal" class="fixed inset-0 z-30 flex items-end bg-black/30" @click.self="showTransactionModal = false">
            <form class="mx-auto w-full max-w-lg rounded-t-[28px] bg-white px-6 pb-8 pt-3 text-[#152338]" @submit.prevent="submitTransaction">
                <div class="mx-auto mb-5 h-1 w-9 rounded-full bg-[#D5DBE5]"></div>
                <div class="flex items-start justify-between"><div><p class="text-xs font-medium text-[#637083]">Step {{ transactionStep }} of 2</p><h2 class="text-[22px] font-semibold">New transaction</h2><p class="text-sm text-[#637083]">Keep track of money in and out.</p></div><button type="button" class="text-2xl text-[#637083]" @click="showTransactionModal = false">×</button></div>
                <template v-if="transactionStep === 1">
                    <div class="mt-5 grid grid-cols-2 rounded-xl bg-[#F5F7FA] p-1"><button v-for="type in ['expense', 'income']" :key="type" type="button" class="rounded-lg py-3 text-sm font-semibold capitalize" :class="transactionForm.type === type ? 'bg-white text-[#2457DA] shadow-sm' : 'text-[#637083]'" @click="transactionForm.type = type">{{ type }}</button></div>
                    <button type="button" class="mt-6 h-[53px] w-full rounded-xl bg-[#2457DA] text-white" @click="transactionStep = 2">Continue</button>
                </template>
                <template v-else-if="transactionStep === 2">
                    <label class="mt-5 block">Account<SelectInput v-model="transactionForm.account_id" :options="accounts.map((account) => ({ label: account.name, value: account.id }))" placeholder="Select account" required /></label>
                    <div class="mt-6 grid grid-cols-2 gap-3"><button type="button" class="h-[53px] rounded-xl border border-[#2457DA] text-[#2457DA]" @click="transactionStep = 1">Back</button><button type="button" class="h-[53px] rounded-xl bg-[#2457DA] text-white" @click="transactionStep = 3">Continue</button></div>
                </template>
                <template v-else>
                    <label class="mt-5 block text-sm text-[#637083]">Amount<FormInput v-model="transactionForm.amount" type="number" min="0.01" step="0.01" required placeholder="Rp 0" variant="amount" /></label>
                    <label class="mt-5 block">Title<FormInput v-model="transactionForm.title" placeholder="What was this transaction for?" required /></label>
                    <label class="mt-5 block">Category<SelectInput v-model="transactionForm.category_id" :options="categories.filter((item) => item.type === transactionForm.type && item.is_active).map((item) => ({ label: item.name, value: item.id }))" placeholder="Select category" searchable /></label>
                    <div class="mt-5 grid grid-cols-2 gap-3"><label>Date<DatePicker v-model="transactionForm.date" required /></label><label>Note<FormInput v-model="transactionForm.note" placeholder="Optional note" /></label></div>
                    <div class="mt-6 grid grid-cols-2 gap-3"><button type="button" class="h-[53px] rounded-xl border border-[#2457DA] text-[#2457DA]" @click="transactionStep = 2">Back</button><button type="submit" :disabled="transactionForm.processing" class="h-[53px] rounded-xl bg-[#2457DA] text-white disabled:cursor-not-allowed disabled:opacity-60"><span v-if="transactionForm.processing">Saving...</span><span v-else>Create transaction</span></button></div>
                    <p class="mt-3 text-center text-xs text-[#637083]">You can edit this transaction anytime.</p>
                </template>
            </form>
        </div>
        <MobileBottomNav @add="openTransactionModal" />
    </div>
</template>

<style scoped>
.mini-header-enter-active,
.mini-header-leave-active { transition: opacity 180ms ease, transform 180ms ease; }
.mini-header-enter-from,
.mini-header-leave-to { opacity: 0; transform: translateY(-12px); }
section.mt-7.px-6 { order: 1; }
main { order: 2; }
</style>
