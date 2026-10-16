<script setup>
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Banknote, Landmark, Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
import CmsModal from '@/Components/CmsModal.vue';
import { store, update, destroy } from '@/routes/accounts';

const props = defineProps({ accounts: { type: Array, default: () => [] } });
const open = ref(false);
const deleting = ref(null);
const search = ref('');
const form = useForm({ id: null, name: '', type: 'cash', currency: 'IDR', opening_balance: 0 });
const visibleAccounts = computed(() => props.accounts.filter((account) => account.name.toLowerCase().includes(search.value.toLowerCase())));
const money = (value, account) => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: account.currency || 'IDR',
    maximumFractionDigits: 2,
}).format(Number(value ?? 0));
const create = () => {
    form.reset();
    form.clearErrors();
    form.id = null;
    form.type = 'cash';
    form.currency = 'IDR';
    open.value = true;
};
const edit = (account) => {
    Object.assign(form, {
        id: account.id,
        name: account.name,
        type: account.type,
        currency: account.currency || 'IDR',
        opening_balance: account.opening_balance,
    });
    form.clearErrors();
    open.value = true;
};
const save = () => form[form.id ? 'put' : 'post'](
    form.id ? update.url(form.id) : store.url(),
    { onSuccess: () => { open.value = false; } },
);
const remove = (deleteTransactions) => useForm({ delete_transactions: deleteTransactions }).delete(
    destroy.url(deleting.value.id),
    { onSuccess: () => { deleting.value = null; } },
);
</script>

<template>

    <Head title="CMS Accounts" />
    <div class="space-y-7">
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-[#8792A2]">Your money</p>
                <h1 class="mt-1 text-3xl font-semibold tracking-tight text-[#17243A]">Accounts</h1>
            </div>
            <button
                class="inline-flex h-11 items-center gap-2 rounded-xl bg-[#2457DA] px-4 text-sm font-semibold text-white shadow-sm shadow-blue-200 hover:bg-[#1947BE]"
                @click="create">
                <Plus :size="17" /> Add account
            </button>
        </header>
        <section class="grid gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">Total accounts</p>
                <p class="mt-3 text-2xl font-semibold text-[#17243A]">{{ accounts.length }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">Combined balance</p>
                <p class="mt-3 text-xl font-semibold text-[#17243A]">{{new Intl.NumberFormat('id-ID', {
                    style:
                        'currency', currency: 'IDR', maximumFractionDigits: 0
                }).format(accounts.reduce((total, account) =>
                    total + Number(account.current_balance ?? 0), 0)) }}</p>
            </article>
            <article class="rounded-2xl border border-[#E9EDF3] bg-white p-5">
                <p class="text-sm text-[#8792A2]">Bank accounts</p>
                <p class="mt-3 text-2xl font-semibold text-[#17243A]">{{accounts.filter((account) => account.type ===
                    'bank').length }}</p>
            </article>
        </section>
        <section
            class="overflow-hidden rounded-2xl border border-[#E9EDF3] bg-white shadow-[0_4px_18px_rgba(23,36,58,0.03)]">
            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-[#EEF1F5] px-6 py-4">
                <div>
                    <h2 class="font-semibold text-[#17243A]">All accounts</h2>
                </div><label
                    class="flex h-10 w-full items-center gap-2 rounded-lg border border-[#E7EBF2] px-3 sm:w-64">
                    <Search :size="16" class="text-[#98A2B3]" /><input v-model="search"
                        class="w-full border-0 p-0 text-sm outline-none focus:ring-0" placeholder="Search accounts" />
                </label>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[680px] text-left">
                    <thead class="bg-[#FAFBFC] text-[11px] font-semibold uppercase tracking-[0.12em] text-[#98A2B3]">
                        <tr>
                            <th class="px-6 py-3">Account</th>
                            <th class="px-5 py-3">Type</th>
                            <th class="px-5 py-3">Activity</th>
                            <th class="px-5 py-3 text-right">Current balance</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#F0F2F6]">
                        <tr v-for="account in visibleAccounts" :key="account.id" class="hover:bg-[#FCFDFE]">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3"><span
                                        class="rounded-xl bg-[#EEF3FF] p-2.5 text-[#2457DA]">
                                        <component :is="account.type === 'bank' ? Landmark : Banknote" :size="18" />
                                    </span><span><span class="block text-sm font-semibold text-[#26354C]">{{
                                            account.name }}</span><span class="mt-1 block text-xs text-[#98A2B3]">{{
                                            account.currency || 'IDR' }}</span></span></div>
                            </td>
                            <td class="px-5 py-4"><span
                                    class="rounded-full bg-[#F3F5F8] px-3 py-1 text-xs font-medium capitalize text-[#637083]">{{
                                    account.type }}</span></td>
                            <td class="px-5 py-4 text-sm text-[#637083]">{{ account.transactions_count }} transactions
                            </td>
                            <td class="px-5 py-4 text-right text-sm font-semibold text-[#26354C]">{{
                                money(account.current_balance, account) }}</td>
                            <td class="px-6 py-4">
                                <div class="flex justify-end gap-2"><button v-if="account.type !== 'investment'"
                                        class="rounded-lg p-2 text-[#7B8798] hover:bg-[#EEF3FF] hover:text-[#2457DA]"
                                        :aria-label="`Edit ${account.name}`" @click="edit(account)">
                                        <Pencil :size="16" />
                                    </button><button
                                        class="rounded-lg p-2 text-[#7B8798] hover:bg-[#FFF2F0] hover:text-[#C45B4B]"
                                        :aria-label="`Delete ${account.name}`" @click="deleting = account">
                                        <Trash2 :size="16" />
                                    </button></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-if="!visibleAccounts.length" class="px-6 py-12 text-center text-sm text-[#8792A2]">No accounts found.
            </p>
        </section>

        <CmsModal :show="open" :label="form.id ? 'Edit account' : 'Add account'" @close="open = false">
            <form
                class="my-auto max-h-[calc(100dvh-2rem)] w-full max-w-md space-y-5 overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl sm:max-h-[calc(100dvh-3rem)]"
                @submit.prevent="save">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-sm text-[#8792A2]">Account details</p>
                        <h2 class="mt-1 text-xl font-semibold text-[#17243A]">{{ form.id ? 'Edit' : 'Add' }} account</h2>
                    </div><button type="button" class="rounded-lg p-2 text-[#7B8798] hover:bg-[#F5F7FA]"
                        aria-label="Close" @click="open = false">
                        <X :size="19" />
                    </button>
                </div>
                <label class="block text-sm font-medium text-[#435065]">Account name<input v-model="form.name" required
                        maxlength="100" class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm"
                        placeholder="e.g. Everyday account" /></label>
                <div class="grid gap-4 sm:grid-cols-2"><label
                        class="block text-sm font-medium text-[#435065]">Type<select v-model="form.type"
                            class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm">
                            <option value="cash">Cash</option>
                            <option value="bank">Bank</option>
                            <option value="other">Other</option>
                        </select></label><label v-if="['cash', 'bank'].includes(form.type)"
                        class="block text-sm font-medium text-[#435065]">Currency<input v-model="form.currency" required
                            maxlength="3"
                            class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm uppercase" /></label>
                </div><label class="block text-sm font-medium text-[#435065]">Opening balance<input
                        v-model="form.opening_balance" type="number" min="0" step="0.01" required
                        class="mt-2 h-11 w-full rounded-lg border border-[#DDE3EC] px-3 text-sm" /></label>
                <p v-if="form.errors.name || form.errors.currency || form.errors.opening_balance"
                    class="text-sm text-red-600">{{ form.errors.name || form.errors.currency ||
                        form.errors.opening_balance }}</p><button
                    class="h-11 w-full rounded-xl bg-[#2457DA] text-sm font-semibold text-white disabled:opacity-60"
                    :disabled="form.processing">{{ form.processing ? 'Saving…' : 'Save account' }}</button>
            </form>
        </CmsModal>
        <div v-if="deleting" class="fixed inset-0 z-40 flex items-center justify-center bg-[#17243A]/30 p-5">
            <section class="w-full max-w-md space-y-4 rounded-2xl bg-white p-6 shadow-2xl">
                <div>
                    <p class="text-sm text-[#8792A2]">Remove account</p>
                    <h2 class="mt-1 text-xl font-semibold text-[#17243A]">Delete {{ deleting.name }}?</h2>
                    <p class="mt-2 text-sm text-[#637083]">Choose what to do with its transactions.</p>
                </div><button class="w-full rounded-xl bg-[#C8493A] py-3 text-sm font-semibold text-white"
                    @click="remove(true)">Delete account and transactions</button><button
                    class="w-full rounded-xl border border-[#DDE3EC] py-3 text-sm font-semibold text-[#435065]"
                    @click="remove(false)">Delete account only</button><button
                    class="w-full py-2 text-sm text-[#7B8798]" @click="deleting = null">Cancel</button>
            </section>
        </div>
    </div>
</template>
