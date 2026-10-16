<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDownLeft,
    ArrowUpRight,
    Banknote,
    BriefcaseBusiness,
    ChartNoAxesCombined,
    CircleDollarSign,
    Wallet,
} from 'lucide-vue-next';
import { index as transactionsIndex } from '@/routes/cms/transactions';
import { index as accountsIndex } from '@/routes/cms/accounts';
import { index as assetsIndex } from '@/routes/cms/assets';

const props = defineProps({
    categories: { type: Array, default: () => [] },
    spendingCategories: { type: Array, default: () => [] },
    accounts: { type: Array, default: () => [] },
    assets: { type: Array, default: () => [] },
    transactions: { type: Array, default: () => [] },
    summary: { type: Object, default: () => ({}) },
});

const user = computed(() => usePage().props.auth?.user);
const money = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0,
}).format(Number(value ?? 0));
const spendingTotal = computed(() => props.spendingCategories.reduce(
    (total, category) => total + Number(category.amount ?? 0),
    0,
));
const categoryShare = (amount) => spendingTotal.value
    ? `${Math.round((Number(amount) / spendingTotal.value) * 100)}%`
    : '0%';
</script>

<template>
    <Head title="CMS Dashboard" />

    <div class="space-y-7">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-[#8792A2]">Overview</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#17243A]">Dashboard</h1>
                <p class="mt-2 text-sm text-[#8792A2]">Welcome back, {{ user?.name }}. Here’s your financial snapshot.</p>
            </div>
            <Link :href="transactionsIndex.url()" class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#2457DA] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-200 transition hover:bg-[#1947BE]">
                View transactions
            </Link>
        </header>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5 shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-[#7B8798]">Net worth estimate</p>
                    <span class="rounded-xl bg-[#EEF3FF] p-2.5 text-[#2457DA]"><CircleDollarSign :size="19" /></span>
                </div>
                <p class="mt-4 text-2xl font-semibold tracking-tight text-[#17243A]">{{ money(summary.balance) }}</p>
                <p class="mt-2 text-xs text-[#98A2B3]">Includes {{ money(summary.asset_value) }} estimated asset value</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5 shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-[#7B8798]">Account balance</p>
                    <span class="rounded-xl bg-[#EFFAF5] p-2.5 text-[#16815B]"><Wallet :size="19" /></span>
                </div>
                <p class="mt-4 text-2xl font-semibold tracking-tight text-[#17243A]">{{ money(summary.account_balance) }}</p>
                <p class="mt-2 text-xs text-[#98A2B3]">Across {{ accounts.length }} accounts</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5 shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-[#7B8798]">Total income</p>
                    <span class="rounded-xl bg-[#EFFAF5] p-2.5 text-[#16815B]"><ArrowDownLeft :size="19" /></span>
                </div>
                <p class="mt-4 text-2xl font-semibold tracking-tight text-[#17243A]">{{ money(summary.income) }}</p>
                <p class="mt-2 text-xs text-[#98A2B3]">All recorded transactions</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5 shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-[#7B8798]">Total expenses</p>
                    <span class="rounded-xl bg-[#FFF2F0] p-2.5 text-[#C45B4B]"><ArrowUpRight :size="19" /></span>
                </div>
                <p class="mt-4 text-2xl font-semibold tracking-tight text-[#17243A]">{{ money(summary.expense) }}</p>
                <p class="mt-2 text-xs text-[#98A2B3]">All recorded transactions</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.65fr)_minmax(300px,0.85fr)]">
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-6 shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-base font-semibold text-[#17243A]">Money flow</h2>
                        <p class="mt-1 text-xs text-[#98A2B3]">Income and expenses over time</p>
                    </div>
                    <div class="flex gap-4 text-xs font-medium text-[#637083]">
                        <span class="flex items-center gap-2"><i class="h-2 w-2 rounded-full bg-[#2457DA]"></i>Income</span>
                        <span class="flex items-center gap-2"><i class="h-2 w-2 rounded-full bg-[#BFC8D5]"></i>Expenses</span>
                    </div>
                </div>
                <div class="mt-7 grid grid-cols-[38px_1fr] gap-3">
                    <div class="flex flex-col justify-between pb-7 text-[10px] text-[#A2ACB9]"><span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0%</span></div>
                    <div class="relative h-56 border-b border-l border-[#EEF1F5]">
                        <div class="absolute inset-0 flex flex-col justify-between"><i v-for="line in 4" :key="line" class="border-t border-dashed border-[#EEF1F5]"></i></div>
                        <svg viewBox="0 0 700 210" preserveAspectRatio="none" class="absolute inset-0 h-full w-full overflow-visible">
                            <defs><linearGradient id="flow-fill" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#2457DA" stop-opacity=".16" /><stop offset="100%" stop-color="#2457DA" stop-opacity="0" /></linearGradient></defs>
                            <path d="M0 166 C45 153 56 126 105 136 S170 159 210 114 S276 124 318 89 S370 133 414 113 S476 150 522 93 S589 115 630 72 S674 74 700 48 L700 210 L0 210 Z" fill="url(#flow-fill)" />
                            <path d="M0 166 C45 153 56 126 105 136 S170 159 210 114 S276 124 318 89 S370 133 414 113 S476 150 522 93 S589 115 630 72 S674 74 700 48" fill="none" stroke="#2457DA" stroke-width="3" vector-effect="non-scaling-stroke" />
                            <path d="M0 129 C45 107 74 146 108 125 S169 97 210 137 S270 140 318 119 S370 78 414 113 S476 96 522 136 S583 143 630 111 S680 132 700 121" fill="none" stroke="#C8D0DC" stroke-width="2" vector-effect="non-scaling-stroke" />
                        </svg>
                        <div class="absolute inset-x-0 -bottom-6 flex justify-between text-[10px] text-[#98A2B3]"><span>Week 1</span><span>Week 2</span><span>Week 3</span><span>Week 4</span></div>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-6 shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                <div class="flex items-center justify-between">
                    <div><h2 class="text-base font-semibold text-[#17243A]">Spending by category</h2><p class="mt-1 text-xs text-[#98A2B3]">Expense distribution</p></div>
                    <ChartNoAxesCombined :size="19" class="text-[#98A2B3]" />
                </div>
                <div v-if="spendingCategories.length" class="mt-5 space-y-4">
                    <div v-for="category in spendingCategories.slice(0, 5)" :key="category.id">
                        <div class="mb-2 flex items-center justify-between gap-3 text-xs"><span class="truncate font-medium text-[#435065]">{{ category.name }}</span><span class="text-[#7B8798]">{{ categoryShare(category.amount) }}</span></div>
                        <div class="h-2 overflow-hidden rounded-full bg-[#F0F2F6]"><div class="h-full rounded-full" :style="{ width: categoryShare(category.amount), backgroundColor: category.color }"></div></div>
                    </div>
                </div>
                <p v-else class="mt-8 rounded-xl bg-[#F7F8FA] p-5 text-center text-sm text-[#8792A2]">Your spending categories will appear here.</p>
            </article>
        </section>

        <section class="grid gap-5 xl:grid-cols-[minmax(0,1.4fr)_minmax(310px,0.8fr)]">
            <article class="overflow-hidden rounded-2xl border border-[#E9EDF3] bg-white shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                <div class="flex items-center justify-between border-b border-[#EEF1F5] px-6 py-5"><div><h2 class="text-base font-semibold text-[#17243A]">Recent activity</h2><p class="mt-1 text-xs text-[#98A2B3]">Your latest transactions</p></div><Link :href="transactionsIndex.url()" class="text-sm font-semibold text-[#2457DA]">View all</Link></div>
                <div v-if="transactions.length" class="divide-y divide-[#F0F2F6]">
                    <div v-for="transaction in transactions" :key="transaction.id" class="flex items-center gap-4 px-6 py-4">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl" :class="transaction.type === 'income' ? 'bg-[#EFFAF5] text-[#16815B]' : 'bg-[#FFF2F0] text-[#C45B4B]'"><component :is="transaction.type === 'income' ? ArrowDownLeft : ArrowUpRight" :size="19" /></span>
                        <div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold text-[#26354C]">{{ transaction.title || transaction.note || 'Transaction' }}</p><p class="mt-1 truncate text-xs text-[#98A2B3]">{{ transaction.category?.name || 'Uncategorized' }} · {{ transaction.date }}</p></div>
                        <strong class="shrink-0 text-sm" :class="transaction.type === 'income' ? 'text-[#16815B]' : 'text-[#26354C]'">{{ transaction.type === 'income' ? '+' : '−' }} {{ money(transaction.amount) }}</strong>
                    </div>
                </div>
                <p v-else class="px-6 py-10 text-center text-sm text-[#8792A2]">No transactions yet.</p>
            </article>

            <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-1">
                <article class="rounded-2xl border border-[#E9EDF3] bg-white p-6 shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                    <div class="flex items-center justify-between"><div><h2 class="text-base font-semibold text-[#17243A]">Accounts</h2><p class="mt-1 text-xs text-[#98A2B3]">{{ accounts.length }} connected</p></div><Link :href="accountsIndex.url()" class="text-sm font-semibold text-[#2457DA]">Manage</Link></div>
                    <div v-if="accounts.length" class="mt-4 space-y-3">
                        <div v-for="account in accounts.slice(0, 3)" :key="account.id" class="flex items-center justify-between gap-3 rounded-xl bg-[#F7F8FA] px-4 py-3"><span class="flex min-w-0 items-center gap-3"><i class="rounded-lg bg-white p-2 text-[#2457DA]"><Banknote :size="16" /></i><span class="truncate text-sm font-medium text-[#435065]">{{ account.name }}</span></span><strong class="shrink-0 text-sm text-[#26354C]">{{ money(account.current_balance) }}</strong></div>
                    </div>
                    <p v-else class="mt-5 text-sm text-[#8792A2]">No accounts added.</p>
                </article>
                <article class="rounded-2xl border border-[#E9EDF3] bg-white p-6 shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
                    <div class="flex items-center justify-between"><div><h2 class="text-base font-semibold text-[#17243A]">Assets</h2><p class="mt-1 text-xs text-[#98A2B3]">Estimated current value</p></div><Link :href="assetsIndex.url()" class="text-sm font-semibold text-[#2457DA]">Manage</Link></div>
                    <div v-if="assets.length" class="mt-4 space-y-3"><div v-for="asset in assets" :key="asset.id" class="flex items-center justify-between gap-3 rounded-xl bg-[#F7F8FA] px-4 py-3"><span class="flex min-w-0 items-center gap-3"><i class="rounded-lg bg-white p-2 text-[#2457DA]"><BriefcaseBusiness :size="16" /></i><span class="truncate text-sm font-medium text-[#435065]">{{ asset.name }}</span></span><strong class="shrink-0 text-sm text-[#26354C]">{{ money(asset.current_value) }}</strong></div></div>
                    <p v-else class="mt-5 text-sm text-[#8792A2]">No assets recorded.</p>
                </article>
            </div>
        </section>
    </div>
</template>
