<script setup>
import { computed, nextTick, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import { chat as aiChat } from '@/routes/ai';
import { dashboard } from '@/routes';
import { confirm as confirmAiChatTransaction } from '@/actions/App/Http/Controllers/AiChatTransactionController';
import { cancel as cancelAiWorkflow } from '@/actions/App/Http/Controllers/AiWorkflowController';
import ChatComposer from '@/Components/Chat/ChatComposer.vue';
import ChatHeader from '@/Components/Chat/ChatHeader.vue';
import ChatIntroCard from '@/Components/Chat/ChatIntroCard.vue';
import ChatMessageList from '@/Components/Chat/ChatMessageList.vue';
import ChatTransactionSetupModal from '@/Components/Chat/ChatTransactionSetupModal.vue';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';

const props = defineProps({
    conversationId: { type: Number, default: null },
    messages: { type: Array, default: () => [] },
    workflow: { type: Object, default: null },
});

const suggestions = [
    'How much did I spend this month?',
    'Help me plan my budget',
    'Give me a saving tip',
];

const fallbackMessages = [
    {
        id: 1,
        role: 'assistant',
        text: 'Hi! I’m your finance assistant. Ask me anything about your spending, budget, or saving goals.',
        time: 'Now',
    },
];
const messages = ref(props.messages.length > 0 ? props.messages : fallbackMessages);
const draft = ref('');
const isTyping = ref(false);
const isSavingSetup = ref(false);
const pendingTransaction = ref(null);
const pendingTransactions = ref([]);
const setupResources = ref([]);
const accountSelectionRequired = ref(false);
const accountOptions = ref([]);
const selectedAccountId = ref(null);
const workflowReady = ref(false);
const workflowModalVisible = ref(false);
const confirmationOperationId = ref(null);
const failedRequest = ref(null);
const messagesContainer = ref(null);
const chatEndpoint = aiChat.url();
const confirmTransactionEndpoint = confirmAiChatTransaction.url();
const activeWorkflowId = ref(props.workflow?.id ?? null);
const isSetupOpen = computed(() => workflowModalVisible.value && (
    pendingTransactions.value.length > 0
    ||
    accountSelectionRequired.value || setupResources.value.length > 0 || workflowReady.value
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
    const transactions = Array.isArray(payload.pending_transactions)
        ? payload.pending_transactions
        : payload.pending_transaction
            ? [payload.pending_transaction]
            : [];

    if (transactions.length === 0) {
        return;
    }

    pendingTransactions.value = transactions;
    pendingTransaction.value = transactions[0];
    const missingResources = Array.isArray(payload.missing_resources)
        ? payload.missing_resources
        : [];

    setupResources.value = missingResources.map((resource) => createSetupResource(
        resource,
        transactions[0].transaction_type,
    ));
    accountSelectionRequired.value = payload.account_selection_required === true;
    accountOptions.value = Array.isArray(payload.account_options)
        ? payload.account_options
        : [];
    selectedAccountId.value = null;
    workflowReady.value = payload.workflow_ready === true;
    workflowModalVisible.value = true;
    confirmationOperationId.value = confirmationOperationId.value
        ?? `workflow:${payload.workflow_id ?? activeWorkflowId.value}`;

    if (
        accountSelectionRequired.value
        && accountOptions.value.length === 0
        && !setupResources.value.some((resource) => resource.type === 'account')
    ) {
        setupResources.value.unshift(createSetupResource({
            type: 'account',
            name: '',
        }, transactions[0].transaction_type));
    }
};

const clearWorkflowState = () => {
    pendingTransaction.value = null;
    pendingTransactions.value = [];
    setupResources.value = [];
    accountSelectionRequired.value = false;
    accountOptions.value = [];
    selectedAccountId.value = null;
    workflowReady.value = false;
    workflowModalVisible.value = false;
    activeWorkflowId.value = null;
    confirmationOperationId.value = null;
};

const hydrateWorkflow = (workflow) => {
    if (!workflow) {
        clearWorkflowState();

        return;
    }

    activeWorkflowId.value = workflow.id ?? null;
    setupMissingResources({
        workflow_id: workflow.id,
        pending_transactions: workflow.pending_transactions ?? workflow.draft?.transactions ?? [],
        pending_transaction: workflow.pending_transaction,
        missing_resources: workflow.missing_resources ?? workflow.draft?.missing_resources ?? [],
        account_selection_required: workflow.account_selection_required
            ?? workflow.draft?.account_selection_required
            ?? false,
        account_options: workflow.account_options ?? workflow.draft?.account_options ?? [],
        workflow_ready: workflow.workflow_ready === true || workflow.status === 'ready',
    });
};

watch(
    () => props.messages,
    (serverMessages) => {
        messages.value = Array.isArray(serverMessages) && serverMessages.length > 0
            ? serverMessages
            : fallbackMessages;
    },
    { deep: true },
);

watch(
    () => props.workflow,
    (workflow) => hydrateWorkflow(workflow),
    { deep: true },
);

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
                currency: ['cash', 'bank'].includes(resource.accountType)
                    ? 'IDR'
                    : null,
                investment_unit: resource.accountType === 'investment'
                    ? 'lot'
                    : null,
                opening_balance: Number(resource.openingBalance || 0),
            }
                    : {
                type: resource.type,
                name: resource.name.trim(),
                category_type: resource.categoryType,
                icon: resource.icon,
                color: resource.color,
            });
        const transactionData = pendingTransactions.value.map((transaction) => ({
            ...transaction,
        }));

        if (accountSelectionRequired.value && accountOptions.value.length > 0) {
            transactionData.forEach((transaction) => {
                transaction.account_id = selectedAccountId.value;
            });
        }

        transactionData.forEach((transaction) => {
            setupResources.value.forEach((resource) => {
                if (resource.type === 'account') {
                    transaction.account = resource.name.trim();
                } else if (!transaction.category) {
                    transaction.category = resource.name.trim();
                }
            });
        });

        const requestBody = transactionData.length > 1
            ? {
                transactions: transactionData,
                resources,
            }
            : {
                transaction: transactionData[0],
                resources,
            };
        requestBody.conversation_id = props.conversationId;
        if (activeWorkflowId.value) {
            requestBody.workflow_id = activeWorkflowId.value;
        }
        confirmationOperationId.value = confirmationOperationId.value
            ?? `workflow:${activeWorkflowId.value ?? Date.now()}`;
        requestBody.operation_id = confirmationOperationId.value;

        const response = await fetch(confirmTransactionEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: requestHeaders(),
            body: JSON.stringify(requestBody),
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.message ?? 'Akun atau kategori gagal dibuat.');
        }

        const createdTransactions = Array.isArray(payload.transactions)
            ? payload.transactions
            : [payload.transaction];
        messages.value.push({
            id: Date.now() + 1,
            role: 'assistant',
            text: createdTransactions
                .map((transaction) => (
                    `Berhasil dicatat: ${transaction.title} sebesar Rp ${new Intl.NumberFormat('id-ID').format(Number(transaction.amount))}.`
                ))
                .join(' '),
            time: 'Now',
        });
        pendingTransaction.value = null;
        activeWorkflowId.value = null;
        workflowReady.value = false;
        pendingTransactions.value = [];
        setupResources.value = [];
        accountSelectionRequired.value = false;
        accountOptions.value = [];
        selectedAccountId.value = null;
        workflowModalVisible.value = false;
        confirmationOperationId.value = null;
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

const continueInChat = () => {
    if (isSavingSetup.value) {
        return;
    }

    workflowModalVisible.value = false;
};

const cancelWorkflow = async () => {
    if (isSavingSetup.value || !activeWorkflowId.value) {
        return;
    }

    try {
        const response = await fetch(cancelAiWorkflow.url(activeWorkflowId.value), {
            method: 'POST',
            credentials: 'same-origin',
            headers: requestHeaders(),
        });

        if (!response.ok) {
            const payload = await response.json();
            throw new Error(payload.message ?? 'Workflow gagal dibatalkan.');
        }

        pendingTransaction.value = null;
        workflowModalVisible.value = false;
        workflowReady.value = false;
        pendingTransactions.value = [];
        setupResources.value = [];
        accountSelectionRequired.value = false;
        accountOptions.value = [];
        selectedAccountId.value = null;
        activeWorkflowId.value = null;
        confirmationOperationId.value = null;
    } catch (error) {
        messages.value.push({
            id: Date.now() + 3,
            role: 'assistant',
            text: error instanceof Error ? error.message : 'Workflow gagal dibatalkan.',
            time: 'Now',
        });
        await scrollToLatestMessage();
    }
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

    const retryingFailedRequest = failedRequest.value?.text === text;
    const clientRequestId = retryingFailedRequest
        ? failedRequest.value.id
        : typeof globalThis.crypto?.randomUUID === 'function'
            ? globalThis.crypto.randomUUID()
            : `${Date.now()}-${Math.random().toString(36).slice(2)}`;

    if (!retryingFailedRequest) {
        messages.value.push({
            id: clientRequestId,
            role: 'user',
            text,
            time: 'Now',
        });
    }
    failedRequest.value = null;
    draft.value = '';
    isTyping.value = true;
    await scrollToLatestMessage();

    try {
        const response = await fetch(chatEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: requestHeaders(),
            body: JSON.stringify({
                message: text,
                conversation_id: props.conversationId,
                client_request_id: clientRequestId,
            }),
        });
        const payload = await response.json();

        if (!response.ok) {
            throw new Error(payload.detail ?? payload.message ?? 'AI service gagal memproses pesan.');
        }

        if (typeof payload.response !== 'string' || !payload.response.trim()) {
            throw new Error('AI service mengembalikan response yang tidak valid.');
        }

        activeWorkflowId.value = payload.workflow_id ?? null;

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
        failedRequest.value = {
            id: clientRequestId,
            text,
        };
        await scrollToLatestMessage();
    } finally {
        isTyping.value = false;
    }
};

hydrateWorkflow(props.workflow);
</script>

<template>
    <Head title="Chat" />

    <div class="mx-auto flex h-[100dvh] min-h-0 w-full max-w-lg flex-col overflow-hidden bg-[#F8FAFC] pb-28 text-app-heading">
        <ChatHeader :dashboard-url="dashboard.url()" />

        <main ref="messagesContainer" class="min-h-0 flex-1 space-y-6 overflow-y-auto overscroll-contain px-6 pb-[190px] pt-6">
            <ChatMessageList :messages="messages" :is-typing="isTyping" />
        </main>

        <ChatComposer
            v-model:draft="draft"
            :suggestions="suggestions"
            :is-typing="isTyping"
            :is-setup-open="isSetupOpen"
            @send="sendMessage"
        />

        <ChatTransactionSetupModal
            :is-open="isSetupOpen"
            :setup-title="setupTitle"
            :setup-submit-label="setupSubmitLabel"
            :account-selection-required="accountSelectionRequired"
            :account-options="accountOptions"
            :selected-account-id="selectedAccountId"
            :setup-resources="setupResources"
            :can-confirm-setup="canConfirmSetup"
            :is-saving-setup="isSavingSetup"
            :account-type-label="accountTypeLabel"
            @continue="continueInChat"
            @cancel="cancelWorkflow"
            @confirm="confirmMissingResources"
            @start-account-creation="startAccountCreation"
            @update:selected-account-id="selectedAccountId = $event"
        />

        <MobileBottomNav />
    </div>
</template>
