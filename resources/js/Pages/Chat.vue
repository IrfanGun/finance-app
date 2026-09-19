<script setup>
import { computed, nextTick, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Bot, MessageCircle, Send, Sparkles, UserRound } from 'lucide-vue-next';
import { chat as aiChat } from '@/routes/ai';
import { dashboard } from '@/routes';
import { confirm as confirmAiChatTransaction } from '@/actions/App/Http/Controllers/AiChatTransactionController';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

const suggestions = [
    'How much did I spend this month?',
    'Help me plan my budget',
    'Give me a saving tip',
];

const messages = ref([
    {
        id: 1,
        role: 'assistant',
        text: 'Hi! I’m your finance assistant. Ask me anything about your spending, budget, or saving goals.',
        time: 'Now',
    },
]);
const draft = ref('');
const isTyping = ref(false);
const isSavingSetup = ref(false);
const pendingTransaction = ref(null);
const setupResources = ref([]);
const accountSelectionRequired = ref(false);
const accountOptions = ref([]);
const selectedAccountId = ref(null);
const messagesContainer = ref(null);
const chatEndpoint = aiChat.url();
const confirmTransactionEndpoint = confirmAiChatTransaction.url();
const isSetupOpen = computed(() => (
    accountSelectionRequired.value || setupResources.value.length > 0
));
const canConfirmSetup = computed(() => {
    const mustSelectAccount = accountSelectionRequired.value
        && accountOptions.value.length > 0
        && selectedAccountId.value === null;

    return !isSavingSetup.value
        && !mustSelectAccount
        && !setupResources.value.some((resource) => !resource.name.trim());
});
const setupTitle = computed(() => {
    if (accountSelectionRequired.value && accountOptions.value.length > 0) {
        return 'Pilih asset untuk transaksi';
    }

    if (setupResources.value.some((resource) => resource.type === 'account')) {
        return 'Buat Asset Baru';
    }

    return 'Lengkapi kategori';
});
const setupSubmitLabel = computed(() => {
    if (isSavingSetup.value) {
        return 'Menyimpan...';
    }

    if (accountSelectionRequired.value && accountOptions.value.length > 0) {
        return 'Pilih Asset & Catat Transaksi';
    }

    return 'Buat dan Catat Transaksi';
});

const createSetupResource = (resource, transactionType) => {
    const name = resource.name ?? '';

    return {
        type: resource.type,
        name,
        accountType: /cash|tunai/i.test(name)
            ? 'cash'
            : /bca|bri|bni|bsi|mandiri|bank|dana|ovo|gopay/i.test(name)
                ? 'bank'
                : 'other',
        openingBalance: 0,
        categoryType: transactionType,
        icon: /food|makan/i.test(name) ? 'food' : 'more',
        color: '#2457DA',
    };
};

const accountTypeLabel = (type) => ({
    cash: 'Tunai',
    bank: 'Bank / e-wallet',
    investment: 'Investasi',
    other: 'Lainnya',
}[type] ?? type);

const scrollToLatestMessage = async () => {
    await nextTick();

    if (messagesContainer.value) {
        messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
    }
};

const requestHeaders = () => {
    const headers = {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    };
    const xsrfCookie = document.cookie
        .split('; ')
        .find((cookie) => cookie.startsWith('XSRF-TOKEN='));

    if (xsrfCookie) {
        headers['X-XSRF-TOKEN'] = decodeURIComponent(xsrfCookie.split('=')[1]);
    }

    return headers;
};

const setupMissingResources = (payload) => {
    if (!payload.pending_transaction) {
        return;
    }

    pendingTransaction.value = payload.pending_transaction;
    const missingResources = Array.isArray(payload.missing_resources)
        ? payload.missing_resources
        : [];

    setupResources.value = missingResources.map((resource) => createSetupResource(
        resource,
        payload.pending_transaction.transaction_type,
    ));
    accountSelectionRequired.value = payload.account_selection_required === true;
    accountOptions.value = Array.isArray(payload.account_options)
        ? payload.account_options
        : [];
    selectedAccountId.value = null;

    if (
        accountSelectionRequired.value
        && accountOptions.value.length === 0
        && !setupResources.value.some((resource) => resource.type === 'account')
    ) {
        setupResources.value.unshift(createSetupResource({
            type: 'account',
            name: '',
        }, payload.pending_transaction.transaction_type));
    }
};

const confirmMissingResources = async () => {
    if (!pendingTransaction.value || isSavingSetup.value) {
        return;
    }

    if (
        accountSelectionRequired.value
        && accountOptions.value.length > 0
        && selectedAccountId.value === null
    ) {
        return;
    }

    isSavingSetup.value = true;

    try {
        const resources = setupResources.value.map((resource) => resource.type === 'account'
            ? {
                type: resource.type,
                name: resource.name.trim(),
                account_type: resource.accountType,
                opening_balance: Number(resource.openingBalance || 0),
            }
                    : {
                type: resource.type,
                name: resource.name.trim(),
                category_type: resource.categoryType,
                icon: resource.icon,
                color: resource.color,
            });
        const transactionData = { ...pendingTransaction.value };

        if (accountSelectionRequired.value && accountOptions.value.length > 0) {
            transactionData.account_id = selectedAccountId.value;
        }

        setupResources.value.forEach((resource) => {
            if (resource.type === 'account') {
                transactionData.account = resource.name.trim();
            } else {
                transactionData.category = resource.name.trim();
            }
        });

        const response = await fetch(confirmTransactionEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: requestHeaders(),
            body: JSON.stringify({
                transaction: transactionData,
                resources,
            }),
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message ?? 'Akun atau kategori gagal dibuat.');
        }

        const transaction = payload.transaction;
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: `Berhasil dicatat: ${transaction.title} sebesar Rp ${new Intl.NumberFormat('id-ID').format(Number(transaction.amount))}.`,
            time: 'Now',
        });
        pendingTransaction.value = null;
        setupResources.value = [];
        accountSelectionRequired.value = false;
        accountOptions.value = [];
        selectedAccountId.value = null;
        await scrollToLatestMessage();
    } catch (error) {
        messages.value.push({
            id: Date.now() + 2,
            role: 'assistant',
            text: error instanceof Error
                ? error.message
                : 'Akun atau kategori gagal dibuat. Silakan coba lagi.',
            time: 'Now',
        });
        await scrollToLatestMessage();
    } finally {
        isSavingSetup.value = false;
    }
};

const cancelSetup = () => {
    if (isSavingSetup.value) {
        return;
    }

    pendingTransaction.value = null;
    setupResources.value = [];
    accountSelectionRequired.value = false;
    accountOptions.value = [];
    selectedAccountId.value = null;
};

const startAccountCreation = () => {
    accountSelectionRequired.value = false;
    accountOptions.value = [];
    selectedAccountId.value = null;

    if (!setupResources.value.some((resource) => resource.type === 'account')) {
        setupResources.value.unshift(createSetupResource({
            type: 'account',
            name: '',
        }, pendingTransaction.value?.transaction_type));
    }
};

const sendMessage = async (suggestion = null) => {
    const text = (suggestion ?? draft.value).trim();

    if (!text || isTyping.value || isSetupOpen.value) {
        return;
    }

    messages.value.push({
        id: Date.now(),
        role: 'user',
        text,
        time: 'Now',
    });
    draft.value = '';
    isTyping.value = true;
    await scrollToLatestMessage();

    try {
        const response = await fetch(chatEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: requestHeaders(),
            body: JSON.stringify({ message: text }),
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.detail ?? payload.message ?? 'AI service gagal memproses pesan.');
        }

        if (typeof payload.response !== 'string' || !payload.response.trim()) {
            throw new Error('AI service mengembalikan response yang tidak valid.');
        }

        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: payload.response,
            time: 'Now',
        });
        setupMissingResources(payload);
        await scrollToLatestMessage();
    } catch (error) {
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: error instanceof Error
                ? error.message
                : 'Maaf, AI service tidak dapat dihubungi saat ini.',
            time: 'Now',
        });
        await scrollToLatestMessage();
    } finally {
        isTyping.value = false;
    }
};
</script>

<template>
    <Head title="Chat" />

    <div class="mx-auto flex h-[100dvh] min-h-0 w-full max-w-lg flex-col overflow-hidden bg-[#F8FAFC] pb-28 text-app-heading">
        <header class="flex items-center justify-between border-b border-[#E9EDF3] bg-white px-6 py-5">
            <Link :href="dashboard.url()" class="flex h-10 w-10 items-center justify-center rounded-full bg-[#EEF3FF] text-[#2457DA]" aria-label="Back to dashboard">
                <ArrowLeft :size="20" />
            </Link>
            <div class="text-center">
                <h1 class="text-lg font-semibold">Chat</h1>
                <p class="mt-0.5 flex items-center justify-center gap-1 text-xs text-[#16815B]"><span class="h-1.5 w-1.5 rounded-full bg-[#16815B]"></span>Online now</p>
            </div>
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2457DA] text-white"><MessageCircle :size="20" /></span>
        </header>

        <main ref="messagesContainer" class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain px-6 pb-[190px] pt-6">
            <section class="rounded-2xl bg-gradient-to-br from-[#11297A] to-[#2969E6] p-5 text-white shadow-lg shadow-blue-900/10">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/15"><Sparkles :size="20" /></span>
                    <div>
                        <p class="font-semibold">Your financial companion</p>
                        <p class="mt-1 text-sm leading-5 text-blue-100">Get simple guidance for your everyday money decisions.</p>
                    </div>
                </div>
            </section>

            <div class="space-y-4">
                <div v-for="message in messages" :key="message.id" class="flex items-end gap-2" :class="message.role === 'user' ? 'justify-end' : 'justify-start'">
                    <span v-if="message.role === 'assistant'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8EEFF] text-[#2457DA]"><Bot :size="17" /></span>
                    <div class="max-w-[82%]">
                        <div class="rounded-2xl px-4 py-3 text-sm leading-5" :class="message.role === 'user' ? 'rounded-br-md bg-[#2457DA] text-white' : 'rounded-bl-md bg-white text-[#314158] shadow-sm ring-1 ring-[#EEF1F5]'">
                            {{ message.text }}
                        </div>
                        <p class="mt-1 px-1 text-[10px] text-[#94A0B2]" :class="message.role === 'user' ? 'text-right' : ''">{{ message.time }}</p>
                    </div>
                    <span v-if="message.role === 'user'" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#DCE6FF] text-[#2457DA]"><UserRound :size="16" /></span>
                </div>

                <div v-if="isTyping" class="flex items-end gap-2">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#E8EEFF] text-[#2457DA]"><Bot :size="17" /></span>
                    <div class="flex gap-1 rounded-2xl rounded-bl-md bg-white px-4 py-4 shadow-sm ring-1 ring-[#EEF1F5]">
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF] [animation-delay:120ms]"></span>
                        <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-[#8BA5EF] [animation-delay:240ms]"></span>
                    </div>
                </div>
            </div>
        </main>

        <section class="fixed inset-x-0 bottom-[84px] z-20 mx-auto w-full max-w-lg space-y-3 border-t border-[#E9EDF3] bg-white px-6 py-4 shadow-[0_-4px_18px_rgba(21,35,56,0.05)]">
            <div class="flex min-w-0 max-w-full gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&amp;::-webkit-scrollbar]:hidden">
                <button v-for="suggestion in suggestions" :key="suggestion" type="button" class="shrink-0 whitespace-nowrap rounded-full border border-[#DCE6FF] bg-[#F7F9FF] px-3 py-2 text-xs font-medium text-[#2457DA] transition hover:bg-[#EEF3FF] disabled:opacity-50" :disabled="isTyping || isSetupOpen" @click="sendMessage(suggestion)">
                    {{ suggestion }}
                </button>
            </div>
            <form class="flex min-w-0 items-center gap-2 rounded-2xl bg-[#F5F7FA] p-2" @submit.prevent="sendMessage()">
                <input v-model="draft" type="text" placeholder="Ask about your finances..." class="min-w-0 flex-1 border-0 bg-transparent px-2 text-sm text-[#152338] placeholder:text-[#94A0B2] focus:ring-0" :disabled="isTyping || isSetupOpen" />
                <button type="submit" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#2457DA] text-white transition hover:bg-[#1D43B6] disabled:cursor-not-allowed disabled:opacity-50" :disabled="!draft.trim() || isTyping || isSetupOpen" aria-label="Send message">
                    <Send :size="18" />
                </button>
            </form>
            <p class="text-center text-[10px] text-[#94A0B2]">Assistant suggestions are for general guidance only.</p>
        </section>

        <div v-if="isSetupOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-black/40 px-0 sm:items-center sm:px-4" @click.self="cancelSetup">
            <section role="dialog" aria-modal="true" aria-labelledby="setup-title" class="max-h-[90dvh] w-full max-w-lg space-y-5 overflow-y-auto rounded-t-[28px] bg-white px-6 pb-8 pt-5 text-[#152338] shadow-xl sm:rounded-[28px]">
                <div class="space-y-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-[#2457DA]">Konfirmasi transaksi</p>
                    <h2 id="setup-title" class="text-xl font-semibold">{{ setupTitle }}</h2>
                    <p v-if="accountSelectionRequired && accountOptions.length" class="text-sm leading-5 text-[#637083]">
                        Transaksi belum dicatat. Pilih akun sumber dana yang akan dihubungkan ke transaksi ini.
                    </p>
                    <p v-else-if="accountSelectionRequired" class="text-sm leading-5 text-[#637083]">
                        Belum ada asset yang tersedia. Buat asset baru untuk mencatat transaksi ini.
                    </p>
                    <p v-else class="text-sm leading-5 text-[#637083]">
                        Transaksi belum dicatat. Lengkapi data yang diperlukan; transaksi akan dibuat setelah Anda konfirmasi.
                    </p>
                </div>

                <fieldset v-if="accountSelectionRequired && accountOptions.length" class="space-y-3">
                    <legend class="text-sm font-medium text-[#314158]">Pilih asset / akun</legend>
                    <label
                        v-for="account in accountOptions"
                        :key="account.id"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border p-4 transition"
                        :class="selectedAccountId === account.id ? 'border-[#2457DA] bg-[#EEF3FF]' : 'border-[#E9EDF3] bg-white hover:bg-[#F8FAFC]'"
                    >
                        <input
                            v-model="selectedAccountId"
                            type="radio"
                            name="transaction-account"
                            :value="account.id"
                            class="h-4 w-4 border-slate-300 text-[#2457DA] focus:ring-[#2457DA]"
                        />
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ account.name }}</span>
                            <span class="mt-0.5 block text-xs capitalize text-[#637083]">{{ accountTypeLabel(account.type) }}</span>
                        </span>
                    </label>
                    <button
                        type="button"
                        class="w-full rounded-xl border border-dashed border-[#AABBEA] px-4 py-3 text-sm font-medium text-[#2457DA] hover:bg-[#F7F9FF]"
                        @click="startAccountCreation"
                    >
                        + Buat Asset Baru
                    </button>
                </fieldset>

                <div v-for="(resource, index) in setupResources" :key="`${resource.type}-${index}`" class="space-y-3 rounded-2xl border border-[#E9EDF3] bg-[#F8FAFC] p-4">
                    <h3 class="font-semibold">
                        {{ resource.type === 'account' ? 'Buat Asset Baru' : 'Buat Category Baru / Create New Category' }}
                    </h3>
                    <label class="block text-sm">
                        {{ resource.type === 'account' ? 'Nama asset/akun' : 'Nama kategori' }}
                        <input v-model="resource.name" required maxlength="100" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3 outline-none focus:border-[#2457DA]" />
                    </label>

                    <template v-if="resource.type === 'account'">
                        <label class="block text-sm">
                            Tipe akun
                            <select v-model="resource.accountType" class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank / e-wallet</option>
                                <option value="investment">Investment</option>
                                <option value="other">Other</option>
                            </select>
                        </label>
                        <label class="block text-sm">
                            Saldo awal
                            <input v-model="resource.openingBalance" type="number" min="0" step="0.01" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-3" />
                        </label>
                    </template>

                    <p v-else class="text-sm text-[#637083]">
                        Tipe kategori: <span class="font-medium capitalize">{{ resource.categoryType }}</span>
                    </p>
                </div>

                <div class="grid gap-3">
                    <button type="button" class="h-12 rounded-xl bg-[#2457DA] font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60" :disabled="!canConfirmSetup" @click="confirmMissingResources">
                        {{ setupSubmitLabel }}
                    </button>
                    <button type="button" class="h-11 rounded-xl border border-[#DCE3ED] font-medium text-[#637083] disabled:opacity-60" :disabled="isSavingSetup" @click="cancelSetup">
                        Batal
                    </button>
                </div>
            </section>
        </div>

        <MobileBottomNav />
    </div>
</template>
