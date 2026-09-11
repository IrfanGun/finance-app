import { useForm } from '@inertiajs/vue3';

export type TransactionOption = {
    id: number;
    name: string;
};

export function useTransactionForm(
    categories: TransactionOption[],
    accounts: TransactionOption[],
) {
    const otherCategory = categories.find(
        (category) => category.name.toLowerCase() === 'other',
    );

    return useForm({
        type: 'expense',
        amount: '',
        date: new Date().toISOString().slice(0, 10),
        title: 'Pengeluaran',
        note: '',
        account_id: accounts[0]?.id ?? '',
        category_id: otherCategory?.id ?? categories[0]?.id ?? '',
    });
}
