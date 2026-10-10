<script setup>
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { currencies } from 'countries-list/currencies';
import {
    ChevronDown,
    Home,
    Plus,
    Pencil,
    Search,
    Trash2,
    X,
} from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { store, update, destroy } from '@/routes/assets';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

const props = defineProps({
    accounts: {
        type: Array,
        default: () => [],
    },
});

const open = ref(false);
const currencyPickerOpen = ref(false);
const currencySearch = ref('');
const deleting = ref(null);
const form = useForm({
    id: null,
    name: '',
    type: 'cash',
    currency: 'IDR',
    investment_unit: null,
    opening_balance: 0,
});

const currencyOptions = computed(() => Object.entries(currencies)
    .map(([code, currency]) => ({
        code,
        name: currency.name,
    }))
    .sort((first, second) => first.code.localeCompare(second.code)));

const filteredCurrencies = computed(() => {
    const search = currencySearch.value.trim().toLocaleLowerCase();

    if (!search) {
        return currencyOptions.value;
    }

    return currencyOptions.value.filter((currency) => (
        currency.code.toLocaleLowerCase().includes(search)
        || currency.name.toLocaleLowerCase().includes(search)
    ));
});

const selectedCurrency = computed(() => currencyOptions.value.find(
    (currency) => currency.code === form.currency,
));

watch(() => form.type, (type) => {
    if (type === 'cash' || type === 'bank') {
        form.investment_unit = null;

        return;
    }

    form.currency = null;

    if (type !== 'investment') {
        form.investment_unit = null;
    }
});

const edit = (account) => {
    form.id = account.id;
    form.name = account.name;
    form.type = account.type;
    form.currency = account.currency || 'IDR';
    form.investment_unit = account.investment_unit;
    form.opening_balance = account.opening_balance;
    currencyPickerOpen.value = false;
    currencySearch.value = '';
    open.value = true;
};

const create = () => {
    form.reset();
    form.id = null;
    form.type = 'cash';
    form.currency = 'IDR';
    form.investment_unit = null;
    form.opening_balance = 0;
    currencyPickerOpen.value = false;
    currencySearch.value = '';
    open.value = true;
};

const closeModal = () => {
    open.value = false;
    currencyPickerOpen.value = false;
};

const selectCurrency = (code) => {
    form.currency = code;
    currencyPickerOpen.value = false;
    currencySearch.value = '';
};

const save = () => form[form.id ? 'put' : 'post'](
    form.id ? update.url(form.id) : store.url(),
    {
        onSuccess: () => {
            open.value = false;
        },
    },
);

const remove = (account) => {
    deleting.value = account;
};

const confirmDelete = (deleteTransactions) => useForm({
    delete_transactions: deleteTransactions,
}).delete(
    destroy.url(deleting.value.id),
    {
        onSuccess: () => {
            deleting.value = null;
        },
    },
);

const formatAmount = (value, account) => {
    const amount = Number(value ?? 0);

    if (account.type === 'investment') {
        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 2,
        }).format(amount);
    }

    const currency = account.currency || 'IDR';

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency,
        maximumFractionDigits: 2,
    }).format(amount);
};

const openingBalanceLabel = computed(() => form.type === 'investment'
    ? 'Opening quantity'
    : 'Opening balance');
</script>

<template>
    <Head title="Assets" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header class="flex items-center justify-between px-6 py-5">
            <Link :href="dashboard.url()">
                <Home :size="22" class="text-[#2457DA]" />
            </Link>

            <h1 class="text-lg font-semibold">Assets</h1>

            <button
                class="rounded-full bg-app-primary p-2 text-white"
                @click="create"
            >
                <Plus :size="18" />
            </button>
        </header>

        <main class="space-y-3 px-6 pt-5">
            <p
                v-if="!accounts.length"
                class="py-10 text-center text-sm text-[#637083]"
            >
                No assets yet.
            </p>

            <article
                v-for="account in accounts"
                :key="account.id"
                class="rounded-xl bg-[#F5F7FA] p-4"
            >
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-semibold">{{ account.name }}</h2>
                        <p class="text-xs capitalize text-[#637083]">
                            {{ account.type }}
                            <span v-if="account.type === 'investment' && account.investment_unit">
                                · {{ account.investment_unit }}
                            </span>
                            <span v-else-if="account.type === 'cash' || account.type === 'bank'">
                                · {{ account.currency || 'IDR' }}
                            </span>
                            · {{ account.transactions_count }} transactions
                        </p>
                    </div>

                    <div class="flex gap-2">
                        <button @click="edit(account)">
                            <Pencil :size="17" class="text-[#2457DA]" />
                        </button>
                        <button @click="remove(account)">
                            <Trash2 :size="17" class="text-red-500" />
                        </button>
                    </div>
                </div>

                <p class="mt-3 text-xl font-semibold">
                    <template v-if="account.type === 'investment'">
                        {{ formatAmount(account.current_balance, account) }}
                        {{ account.investment_unit === 'gram' ? 'g' : account.investment_unit }}
                    </template>
                    <template v-else>
                        {{ formatAmount(account.current_balance, account) }}
                    </template>
                </p>
                <p class="mt-1 text-xs text-[#637083]">Current balance</p>
            </article>
        </main>

        <div
            v-if="open"
            class="fixed inset-0 z-30 flex items-end bg-black/30"
            @click.self="closeModal"
            @keydown.esc="closeModal"
        >
            <form
                class="mx-auto max-h-[90vh] w-full max-w-lg space-y-4 overflow-y-auto rounded-t-[28px] bg-white px-6 pb-8 pt-3"
                @submit.prevent="save"
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-[22px] font-semibold">
                        {{ form.id ? 'Edit asset' : 'New asset' }}
                    </h2>
                    <button type="button" @click="closeModal">
                        <X />
                    </button>
                </div>

                <label class="block text-sm">
                    Asset name
                    <input
                        v-model="form.name"
                        required
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        placeholder="e.g. BCA Savings"
                    />
                </label>

                <label class="block text-sm">
                    Type
                    <select
                        v-model="form.type"
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                    >
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="investment">Investment</option>
                        <option value="other">Other</option>
                    </select>
                </label>

                <div
                    v-if="form.type === 'cash' || form.type === 'bank'"
                    class="relative block text-sm font-medium"
                >
                    <span>Currency</span>
                    <button
                        type="button"
                        class="mt-2 flex h-12 w-full items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-4 text-left font-normal outline-none transition hover:border-slate-300 focus:border-[#2457DA] focus:bg-white focus:ring-4 focus:ring-blue-100"
                        :aria-expanded="currencyPickerOpen"
                        aria-haspopup="listbox"
                        @click="currencyPickerOpen = !currencyPickerOpen"
                    >
                        <span v-if="selectedCurrency" class="truncate">
                            <span class="font-semibold">{{ selectedCurrency.code }}</span>
                            <span class="ml-2 text-slate-500">{{ selectedCurrency.name }}</span>
                        </span>
                        <span v-else class="text-slate-400">Choose a currency</span>
                        <ChevronDown :size="18" class="shrink-0 text-slate-500" />
                    </button>

                    <div
                        v-if="currencyPickerOpen"
                        class="absolute left-0 right-0 top-full z-20 mt-2 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                    >
                        <div class="border-b border-slate-100 p-3">
                            <div class="flex h-10 items-center gap-2 rounded-lg bg-slate-100 px-3">
                                <Search :size="17" class="shrink-0 text-slate-400" />
                                <input
                                    v-model="currencySearch"
                                    autofocus
                                    type="search"
                                    class="h-full w-full border-0 bg-transparent p-0 text-sm outline-none placeholder:text-slate-400 focus:ring-0"
                                    placeholder="Search code or currency"
                                    aria-label="Search currencies"
                                />
                            </div>
                        </div>

                        <div
                            class="max-h-60 overflow-y-auto p-1"
                            role="listbox"
                            aria-label="Currencies"
                        >
                            <button
                                v-for="currency in filteredCurrencies"
                                :key="currency.code"
                                type="button"
                                role="option"
                                :aria-selected="form.currency === currency.code"
                                class="flex w-full items-center justify-between rounded-lg px-3 py-2.5 text-left transition hover:bg-blue-50"
                                :class="form.currency === currency.code ? 'bg-blue-50 text-[#2457DA]' : 'text-slate-700'"
                                @click="selectCurrency(currency.code)"
                            >
                                <span class="min-w-0">
                                    <span class="mr-2 font-semibold">{{ currency.code }}</span>
                                    <span class="text-sm text-slate-500">{{ currency.name }}</span>
                                </span>
                                <span
                                    v-if="form.currency === currency.code"
                                    class="ml-2 shrink-0 text-sm font-semibold"
                                >
                                    Selected
                                </span>
                            </button>
                            <p
                                v-if="!filteredCurrencies.length"
                                class="px-3 py-6 text-center text-sm text-slate-500"
                            >
                                No currencies found.
                            </p>
                        </div>
                    </div>
                    <p v-if="form.errors.currency" class="mt-1 text-xs text-red-600">
                        {{ form.errors.currency }}
                    </p>
                </div>

                <label
                    v-if="form.type === 'investment'"
                    class="block text-sm"
                >
                    Investment unit
                    <select
                        v-model="form.investment_unit"
                        required
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                    >
                        <option :value="null" disabled>Select unit</option>
                        <option value="gram">Gram</option>
                        <option value="lot">Lot</option>
                    </select>
                    <p v-if="form.errors.investment_unit" class="mt-1 text-xs text-red-600">
                        {{ form.errors.investment_unit }}
                    </p>
                </label>

                <label class="block text-sm">
                    {{ openingBalanceLabel }}
                    <input
                        v-model="form.opening_balance"
                        type="number"
                        min="0"
                        step="0.01"
                        required
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                    />
                </label>

                <button
                    class="h-[53px] w-full rounded-xl bg-[#2457DA] text-white"
                    :disabled="form.processing"
                >
                    {{ form.id ? 'Save changes' : 'Create asset' }}
                </button>
            </form>
        </div>

        <div
            v-if="deleting"
            class="fixed inset-0 z-40 flex items-end bg-black/30"
        >
            <div class="mx-auto w-full max-w-lg rounded-t-[28px] bg-white px-6 pb-8 pt-6">
                <h2 class="text-xl font-semibold">Delete {{ deleting.name }}?</h2>
                <p class="mt-2 text-sm text-[#637083]">
                    Choose what to do with its transactions.
                </p>
                <div class="mt-5 grid gap-3">
                    <button
                        class="rounded-xl bg-red-600 py-3 font-semibold text-white"
                        @click="confirmDelete(true)"
                    >
                        Delete asset and transactions
                    </button>
                    <button
                        class="rounded-xl border border-[#2457DA] py-3 font-semibold text-[#2457DA]"
                        @click="confirmDelete(false)"
                    >
                        Delete asset only
                    </button>
                    <button
                        class="py-2 text-sm text-[#637083]"
                        @click="deleting = null"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        </div>

        <MobileBottomNav />
    </div>
</template>
