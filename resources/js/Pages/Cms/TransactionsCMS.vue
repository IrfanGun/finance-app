<script setup>
import { computed, ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, Filter, Plus, Search, X } from 'lucide-vue-next';
import { index as transactionsIndex } from '@/routes/cms/transactions';
import { index as accountsIndex } from '@/routes/cms/accounts';
import { store as transactionStore } from '@/actions/App/Http/Controllers/TransactionController';

const props = defineProps({
    transactions: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
    assets: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
});

const showFilters = ref(false);
const showCreateForm = ref(false);
const search = ref('');
const typeFilter = ref('all');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const accountFilter = ref('');
const categoryFilter = ref('');
const businessToday = () => new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Jakarta',
}).format(new Date());
const createForm = useForm({
    type: 'expense',
    amount: '',
    date: businessToday(),
    title: '',
    note: '',
    account_id: props.assets[0]?.id ?? '',
    category_id: '',
});
const availableCategories = computed(() => props.categories.filter(
    (category) => category.type === createForm.type,
));
const money = (value) => new Intl.NumberFormat('id-ID', {
    style: 'currency', currency: 'IDR', maximumFractionDigits: 0,
}).format(Number(value ?? 0));
const filteredTransactions = computed(() => props.transactions.filter((transaction) => {
    const matchesType = typeFilter.value === 'all' || transaction.type === typeFilter.value;
    const searchText = `${transaction.title ?? ''} ${transaction.note ?? ''} ${transaction.category?.name ?? ''} ${transaction.account?.name ?? ''}`.toLowerCase();
    const matchesSearch = !search.value || searchText.includes(search.value.toLowerCase());
    const matchesAccount = !accountFilter.value || String(transaction.account_id) === accountFilter.value;
    const matchesCategory = !categoryFilter.value || String(transaction.category_id) === categoryFilter.value;

    return matchesType && matchesSearch && matchesAccount && matchesCategory;
}));
const applyFilters = () => router.get(transactionsIndex.url(), {
    from: from.value || undefined,
    to: to.value || undefined,
}, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => { showFilters.value = false; },
});
const clearFilters = () => {
    from.value = '';
    to.value = '';
    accountFilter.value = '';
    categoryFilter.value = '';
    applyFilters();
};
const openCreateForm = () => {
    createForm.reset();
    createForm.clearErrors();
    createForm.type = 'expense';
    createForm.date = businessToday();
    createForm.account_id = props.assets[0]?.id ?? '';
    showCreateForm.value = true;
};
const closeCreateForm = () => {
    showCreateForm.value = false;
    createForm.clearErrors();
};
const saveTransaction = () => createForm.post(transactionStore.url(), {
    preserveScroll: true,
    onSuccess: () => {
        showCreateForm.value = false;
        createForm.reset();
    },
});
</script>

<template>

    <Head title="CMS Transactions" />
    <div class="space-y-7">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-[#8792A2]">Activity</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#17243A]">Transactions</h1>
            </div>
            <div class="flex flex-wrap gap-3">
                <button type="button"
                    class="inline-flex h-11 items-center gap-2 rounded-xl border border-[#E4E9F0] bg-white px-4 text-sm font-semibold text-[#435065] shadow-sm hover:border-[#C9D4E4]"
                    @click="showFilters = true">
                    <Filter :size="17" /> Filters
                </button>
                <ButtonPrimary type="button" class="inline-flex h-11 items-center gap-2 !px-4 !py-2 !text-sm"
                    @click="openCreateForm">
                    <Plus :size="17" /> Create transaction
                </ButtonPrimary>
            </div>
        </header>

        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">Transactions shown</p>
                <p class="mt-3 text-2xl font-semibold text-[#17243A]">{{ filteredTransactions.length }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">Income shown</p>
                <p class="mt-3 text-xl font-semibold text-[#16815B]">{{money(filteredTransactions.filter((item) =>
                    item.type === 'income').reduce((sum, item) => sum + Number(item.amount), 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">Expenses shown</p>
                <p class="mt-3 text-xl font-semibold text-[#C45B4B]">{{money(filteredTransactions.filter((item) =>
                    item.type === 'expense').reduce((sum, item) => sum + Number(item.amount), 0)) }}</p>
            </article>
        </section>

        <section
            class="overflow-hidden rounded-2xl border border-[#E9EDF3] bg-white shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#EEF1F5] px-6 py-4">
                <div class="flex flex-wrap gap-2">
                    <button
                        v-for="tab in [{ label: 'All activity', value: 'all' }, { label: 'Income', value: 'income' }, { label: 'Expenses', value: 'expense' }]"
                        :key="tab.value" type="button" class="rounded-lg px-3.5 py-2 text-sm font-semibold transition"
                        :class="typeFilter === tab.value ? 'bg-[#EEF3FF] text-[#2457DA]' : 'text-[#7B8798] hover:bg-[#F7F8FA]'"
                        @click="typeFilter = tab.value">{{ tab.label }}</button>
                </div>
                <label class="flex h-10 w-full items-center gap-2 rounded-lg border border-[#E7EBF2] px-3 sm:w-64">
                    <Search :size="16" class="text-[#98A2B3]" /><input v-model="search"
                        class="w-full border-0 p-0 text-sm outline-none focus:ring-0"
                        placeholder="Search transactions" />
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead class="bg-[#FAFBFC] text-[11px] font-semibold uppercase tracking-[0.12em] text-[#98A2B3]">
                        <tr>
                            <th class="px-6 py-3">Transaction</th>
                            <th class="px-5 py-3">Category</th>
                            <th class="px-5 py-3">Account</th>
                            <th class="px-5 py-3">Date</th>
                            <th class="px-6 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F2F6]">
                        <tr v-for="transaction in filteredTransactions" :key="transaction.id"
                            class="transition hover:bg-[#FCFDFE]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3"><span
                                        class="flex h-10 w-10 items-center justify-center rounded-xl"
                                        :class="transaction.type === 'income' ? 'bg-[#EFFAF5] text-[#16815B]' : 'bg-[#FFF2F0] text-[#C45B4B]'">
                                        <component :is="transaction.type === 'income' ? ArrowDownLeft : ArrowUpRight"
                                            :size="18" />
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-[#26354C]">{{ transaction.title ||
                                            transaction.note || 'Transaction' }}</p>
                                        <p class="mt-1 text-xs capitalize text-[#98A2B3]">{{ transaction.type }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-sm text-[#637083]">{{ transaction.category?.name ||
                                'Uncategorized' }}</td>
                            <td class="px-5 py-4 text-sm text-[#637083]">{{ transaction.account?.name || '—' }}</td>
                            <td class="px-5 py-4 text-sm text-[#7B8798]">{{ transaction.date }}</td>
                            <td class="px-6 py-4 text-right text-sm font-semibold"
                                :class="transaction.type === 'income' ? 'text-[#16815B]' : 'text-[#26354C]'">{{
                                    transaction.type === 'income' ? '+' : '−' }} {{ money(transaction.amount) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!filteredTransactions.length" class="px-6 py-12 text-center text-sm text-[#8792A2]">No transactions
                match these filters.</p>
        </section>

        <Teleport to="body">
            <div v-if="showCreateForm"
                class="cms-modal fixed inset-0 z-[100] flex min-h-dvh items-center justify-center overflow-y-auto bg-[#101828]/55 p-4 backdrop-blur-sm sm:p-6"
                @click.self="closeCreateForm">
                <form
                    class="my-auto max-h-[calc(100dvh-2rem)] w-full max-w-2xl space-y-5 overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl sm:max-h-[calc(100dvh-3rem)]"
                    role="dialog" aria-modal="true" aria-labelledby="create-transaction-title"
                    @submit.prevent="saveTransaction">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-[#8792A2]">Add to your activity</p>
                            <h2 id="create-transaction-title" class="mt-1 text-xl font-semibold text-[#17243A]">Create
                                transaction</h2>
                        </div>
                        <button type="button" class="rounded-lg p-2 text-[#7B8798] hover:bg-[#F5F7FA]"
                            aria-label="Close form" @click="closeCreateForm">
                            <X :size="19" />
                        </button>
                    </div>

                    <div class="grid grid-cols-2 rounded-xl bg-[#F5F7FA] p-1">
                        <button v-for="type in ['expense', 'income']" :key="type" type="button"
                            class="rounded-lg py-2.5 text-sm font-semibold capitalize transition"
                            :class="createForm.type === type ? 'bg-white text-[#2457DA] shadow-sm' : 'text-[#7B8798]'"
                            :aria-pressed="createForm.type === type"
                            @click="createForm.type = type; createForm.category_id = ''">
                            {{ type }}
                        </button>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-medium text-[#435065] sm:col-span-2">
                            Title
                            <input v-model="createForm.title" required maxlength="255"
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm"
                                placeholder="e.g. Lunch with the team" />
                            <span v-if="createForm.errors.title" class="mt-1 block text-xs text-red-600">{{
                                createForm.errors.title }}</span>
                        </label>
                        <label class="text-sm font-medium text-[#435065]">
                            Amount
                            <input v-model="createForm.amount" type="number" min="0.01" step="0.01" required
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm"
                                placeholder="0.00" />
                            <span v-if="createForm.errors.amount" class="mt-1 block text-xs text-red-600">{{
                                createForm.errors.amount }}</span>
                        </label>
                        <label class="text-sm font-medium text-[#435065]">
                            Date
                            <input v-model="createForm.date" type="date" required
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm" />
                            <span v-if="createForm.errors.date" class="mt-1 block text-xs text-red-600">{{
                                createForm.errors.date }}</span>
                        </label>
                        <label class="text-sm font-medium text-[#435065]">
                            Account
                            <select v-model="createForm.account_id" required
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] bg-white px-3 text-sm">
                                <option value="" disabled>Select an account</option>
                                <option v-for="account in assets" :key="account.id" :value="account.id">{{ account.name
                                    }}</option>
                            </select>
                            <span v-if="createForm.errors.account_id" class="mt-1 block text-xs text-red-600">{{
                                createForm.errors.account_id }}</span>
                        </label>
                        <label class="text-sm font-medium text-[#435065]">
                            Category <span class="font-normal text-[#98A2B3]">(optional)</span>
                            <select v-model="createForm.category_id"
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] bg-white px-3 text-sm">
                                <option value="">Uncategorized</option>
                                <option v-for="category in availableCategories" :key="category.id" :value="category.id">
                                    {{ category.name }}</option>
                            </select>
                            <span v-if="createForm.errors.category_id" class="mt-1 block text-xs text-red-600">{{
                                createForm.errors.category_id }}</span>
                        </label>
                        <label class="text-sm font-medium text-[#435065] sm:col-span-2">
                            Note <span class="font-normal text-[#98A2B3]">(optional)</span>
                            <textarea v-model="createForm.note" rows="3" maxlength="1000"
                                class="mt-2 w-full rounded-lg border border-[#DDE3EC] px-3 py-2 text-sm"
                                placeholder="Add any useful details" />
                            <span v-if="createForm.errors.note" class="mt-1 block text-xs text-red-600">{{
                                createForm.errors.note }}</span>
                        </label>
                    </div>

                    <p v-if="!assets.length" class="rounded-xl bg-amber-50 p-3 text-sm text-amber-800">
                        Add an account before creating a transaction.
                        <Link :href="accountsIndex.url()" class="ml-1 font-semibold underline">Go to accounts</Link>
                    </p>

                    <div class="flex justify-end gap-3 border-t border-[#EEF1F5] pt-4">
                        <button type="button"
                            class="rounded-xl border border-[#DDE3EC] px-4 py-2.5 text-sm font-semibold text-[#637083]"
                            @click="closeCreateForm">Cancel</button>
                        <ButtonPrimary type="submit" class="!px-5 !py-2.5 !text-sm"
                            :disabled="createForm.processing || !assets.length">
                            {{ createForm.processing ? 'Saving…' : 'Save transaction' }}
                        </ButtonPrimary>
                    </div>
                </form>
            </div>

            <div v-if="showFilters"
                class="cms-modal fixed inset-0 z-[100] flex min-h-dvh items-center justify-center overflow-y-auto bg-[#101828]/55 p-4 backdrop-blur-sm sm:p-6"
                @click.self="showFilters = false">
                <form class="my-auto w-full max-w-xl space-y-5 rounded-2xl bg-white p-6 shadow-2xl" role="dialog"
                    aria-modal="true" aria-labelledby="transaction-filters-title" @submit.prevent="applyFilters">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-sm text-[#8792A2]">Narrow your activity</p>
                            <h2 id="transaction-filters-title" class="mt-1 text-xl font-semibold text-[#17243A]">
                                Transaction filters</h2>
                        </div><button type="button" class="rounded-lg p-2 text-[#7B8798] hover:bg-[#F5F7FA]"
                            aria-label="Close filters" @click="showFilters = false">
                            <X :size="19" />
                        </button>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-medium text-[#435065]">From<input
                                v-model="from" type="date"
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm" /></label><label
                            class="text-sm font-medium text-[#435065]">To<input v-model="to" type="date"
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm" /></label><label
                            class="text-sm font-medium text-[#435065]">Account<select v-model="accountFilter"
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm">
                                <option value="">All accounts</option>
                                <option v-for="account in assets" :key="account.id" :value="String(account.id)">{{
                                    account.name }}</option>
                            </select></label><label class="text-sm font-medium text-[#435065]">Category<select
                                v-model="categoryFilter"
                                class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm">
                                <option value="">All categories</option>
                                <option v-for="category in categories" :key="category.id" :value="String(category.id)">
                                    {{ category.name }}</option>
                            </select></label></div>
                    <div class="flex justify-between border-t border-[#EEF1F5] pt-4"><button type="button"
                            class="text-sm font-semibold text-[#637083]" @click="clearFilters">Clear filters</button>
                        <ButtonPrimary type="submit" class="!px-5 !py-2.5 !text-sm">Apply filters</ButtonPrimary>
                    </div>
                </form>
            </div>
        </Teleport>
    </div>
</template>
