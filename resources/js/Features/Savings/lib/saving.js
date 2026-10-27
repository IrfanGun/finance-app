export const formatSavingMoney = (amount) => new Intl.NumberFormat('id-ID').format(
    Number(amount ?? 0),
);

export const getActiveHistoryFilterCount = (filters) => [
    filters.source,
    filters.period,
    filters.account,
].filter(Boolean).length;

export const getSavingCurrencies = (accounts) => {
    const currencies = [
        ...new Set(accounts
            .map((account) => account.currency
                || (account.type === 'investment' ? null : 'IDR'))
            .filter(Boolean)),
    ];

    return currencies.length > 0 ? currencies : ['IDR'];
};

export const createSavingFormDefaults = (currency = 'IDR') => ({
    name: '',
    currency,
    target_amount: '',
    target_date: '',
    notes: '',
});

export const createAllocationFormDefaults = ({
    accountId = '',
    periodId = '',
    reminderLogId = null,
}) => ({
    account_id: accountId,
    amount: '',
    budget_period_id: periodId,
    reminder_log_id: reminderLogId,
    idempotency_key: crypto.randomUUID(),
});

export const createReleaseFormDefaults = (accountId = '') => ({
    account_id: accountId,
    amount: '',
    idempotency_key: crypto.randomUUID(),
});

export const createReminderFormDefaults = (accountId = null) => ({
    purpose: 'saving_allocation',
    type: null,
    priority: 'medium',
    title: 'Alokasi savings',
    amount: '',
    financial_account_id: accountId,
    category_id: null,
    saving_id: null,
    frequency: 'monthly',
    interval: 1,
    starts_at: '',
    ends_at: null,
    timezone: 'Asia/Jakarta',
    notes: '',
});

export const getInitialSavingId = (managedSavingId, search = window.location.search) => {
    const query = new URLSearchParams(search);

    return managedSavingId || Number(query.get('saving')) || null;
};

export const getInitialPeriodId = (search = window.location.search) => {
    const query = new URLSearchParams(search);

    return Number(query.get('period')) || null;
};

export const getReminderLogId = (search = window.location.search) => {
    const query = new URLSearchParams(search);

    return Number(query.get('reminder_log')) || null;
};
