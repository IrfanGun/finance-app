import { computed, ref, watch } from 'vue';
import { confirm as confirmAiChatTransaction } from '@/actions/App/Http/Controllers/AiChatTransactionController';
import { cancel as cancelAiWorkflow } from '@/actions/App/Http/Controllers/AiWorkflowController';
import { chatRequestHeaders } from './chatRequestHeaders';

export function useChatWorkflow(props, appendAssistantMessage) {
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
    const activeWorkflowId = ref(props.workflow?.id ?? null);
    const confirmTransactionEndpoint = confirmAiChatTransaction.url();

    const isSetupOpen = computed(() => workflowModalVisible.value && (
        pendingTransactions.value.length > 0
        || accountSelectionRequired.value
        || setupResources.value.length > 0
        || workflowReady.value
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

    const handleChatResponse = (payload) => {
        activeWorkflowId.value = payload.workflow_id ?? null;
        setupMissingResources(payload);
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
                headers: chatRequestHeaders(),
                body: JSON.stringify(requestBody),
            });
            const payload = await response.json();

            if (!response.ok) {
                throw new Error(payload.message ?? 'Akun atau kategori gagal dibuat.');
            }

            const createdTransactions = Array.isArray(payload.transactions)
                ? payload.transactions
                : [payload.transaction];
            const successMessage = createdTransactions
                .map((transaction) => (
                    `Berhasil dicatat: ${transaction.title} sebesar Rp ${new Intl.NumberFormat('id-ID').format(Number(transaction.amount))}.`
                ))
                .join(' ');

            await appendAssistantMessage(successMessage);
            clearWorkflowState();
        } catch (error) {
            await appendAssistantMessage(
                error instanceof Error
                    ? error.message
                    : 'Akun atau kategori gagal dibuat. Silakan coba lagi.',
                2,
            );
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
                headers: chatRequestHeaders(),
            });

            if (!response.ok) {
                const payload = await response.json();
                throw new Error(payload.message ?? 'Workflow gagal dibatalkan.');
            }

            clearWorkflowState();
        } catch (error) {
            await appendAssistantMessage(
                error instanceof Error ? error.message : 'Workflow gagal dibatalkan.',
                3,
            );
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

    watch(
        () => props.workflow,
        (workflow) => hydrateWorkflow(workflow),
        { deep: true },
    );

    hydrateWorkflow(props.workflow);

    return {
        isSavingSetup,
        setupResources,
        accountSelectionRequired,
        accountOptions,
        selectedAccountId,
        isSetupOpen,
        canConfirmSetup,
        setupTitle,
        setupSubmitLabel,
        accountTypeLabel,
        handleChatResponse,
        confirmMissingResources,
        continueInChat,
        cancelWorkflow,
        startAccountCreation,
    };
}
