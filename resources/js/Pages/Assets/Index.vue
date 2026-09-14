<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Home, Plus, Pencil, Trash2, X } from 'lucide-vue-next';
import { dashboard } from '@/routes';
import { index, store, update, destroy } from '@/routes/assets';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

const props = defineProps({ accounts: { type: Array, default: () => [] } });
const open = ref(false);
const deleting = ref(null);
const form = useForm({ id: null, name: '', type: 'cash', opening_balance: 0 });
const edit = (account) => { Object.assign(form, account); open.value = true; };
const create = () => { form.reset(); form.id = null; form.type = 'cash'; open.value = true; };
const save = () => form[form.id ? 'put' : 'post'](form.id ? update.url(form.id) : store.url(), { onSuccess: () => { open.value = false; } });
const remove = (account) => { deleting.value = account; };
const confirmDelete = (deleteTransactions) => useForm({ delete_transactions: deleteTransactions }).delete(destroy.url(deleting.value.id), { onSuccess: () => { deleting.value = null; } });
const currency = (value) => new Intl.NumberFormat('id-ID').format(Number(value ?? 0));
</script>

<template>

    <Head title="Assets" />
    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header class="flex items-center justify-between px-6 py-5">
            <Link :href="dashboard.url()">
                <Home :size="22" class="text-[#2457DA]" />
            </Link>
            <h1 class="text-lg font-semibold">Assets</h1><button class="rounded-full bg-app-primary p-2 text-white"
                @click="create">
                <Plus :size="18" />
            </button>
        </header>
        <main class="space-y-3 px-6 pt-5">
            <p v-if="!accounts.length" class="py-10 text-center text-sm text-[#637083]">No assets yet.</p>
            <article v-for="account in accounts" :key="account.id" class="rounded-xl bg-[#F5F7FA] p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="font-semibold">{{ account.name }}</h2>
                        <p class="text-xs capitalize text-[#637083]">{{ account.type }} · {{ account.transactions_count
                            }} transactions</p>
                    </div>
                    <div class="flex gap-2"><button @click="edit(account)">
                            <Pencil :size="17" class="text-[#2457DA]" />
                        </button><button @click="remove(account)">
                            <Trash2 :size="17" class="text-red-500" />
                        </button></div>
                </div>
                <p class="mt-3 text-xl font-semibold">Rp {{ currency(account.current_balance) }}</p>
                <p class="mt-1 text-xs text-[#637083]">Current balance</p>
            </article>
        </main>
        <div v-if="open" class="fixed inset-0 z-30 flex items-end bg-black/30" @click.self="open = false">
            <form class="mx-auto w-full max-w-lg rounded-t-[28px] bg-white px-6 pb-8 pt-3" @submit.prevent="save">
                <div class="flex items-center justify-between">
                    <h2 class="text-[22px] font-semibold">{{ form.id ? 'Edit asset' : 'New asset' }}</h2><button
                        type="button" @click="open = false">
                        <X />
                    </button>
                </div><label class="mt-5 block text-sm">Asset name<input v-model="form.name" required
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        placeholder="e.g. BCA Savings" /></label><label class="mt-4 block text-sm">Type<select
                        v-model="form.type" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="investment">Investment</option>
                        <option value="other">Other</option>
                    </select></label><label class="mt-4 block text-sm">Opening balance<input
                        v-model="form.opening_balance" type="number" min="0" step="0.01" required
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4" /></label><button
                    class="mt-6 h-[53px] w-full rounded-xl bg-[#2457DA] text-white" :disabled="form.processing">{{
                        form.id ? 'Save changes' : 'Create asset' }}</button>
            </form>
        </div>
        <div v-if="deleting" class="fixed inset-0 z-40 flex items-end bg-black/30">
            <div class="mx-auto w-full max-w-lg rounded-t-[28px] bg-white px-6 pb-8 pt-6">
                <h2 class="text-xl font-semibold">Delete {{ deleting.name }}?</h2>
                <p class="mt-2 text-sm text-[#637083]">Choose what to do with its transactions.</p>
                <div class="mt-5 grid gap-3"><button class="rounded-xl bg-red-600 py-3 font-semibold text-white"
                        @click="confirmDelete(true)">Delete asset and transactions</button><button
                        class="rounded-xl border border-[#2457DA] py-3 font-semibold text-[#2457DA]"
                        @click="confirmDelete(false)">Delete asset only</button><button
                        class="py-2 text-sm text-[#637083]" @click="deleting = null">Cancel</button></div>
            </div>
        </div>
        <MobileBottomNav />
    </div>
</template>
