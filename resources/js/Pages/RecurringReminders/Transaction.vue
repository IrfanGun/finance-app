<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2 } from 'lucide-vue-next';
import { complete as completeReminder, index as remindersIndex } from '@/routes/recurring-reminders';

const props = defineProps({
    reminder: { type: Object, required: true },
    log: { type: Object, required: true },
    accounts: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    budgetPeriods: { type: Array, default: () => [] },
    savings: { type: Array, default: () => [] },
});

const sourceType = ref('free');
const form = useForm({
    amount: props.reminder.amount ?? '',
    date: props.log.scheduled_at.slice(0, 10),
    account_id: props.reminder.financial_account_id ?? '',
    category_id: props.reminder.category_id ?? '',
    title: props.reminder.title,
    note: props.reminder.notes ?? '',
    budget_period_id: null,
    saving_id: null,
});

const submit = () => {
    form.budget_period_id = sourceType.value === 'budget' ? form.budget_period_id : null;
    form.saving_id = sourceType.value === 'saving' ? form.saving_id : null;
    form.post(completeReminder.url({ reminder: props.reminder.id, log: props.log.id }));
};
</script>

<template>
    <Head title="Catat transaksi dari pengingat" />
    <div class="mx-auto min-h-screen w-full max-w-lg bg-[#F7F8FA] px-4 py-6 text-[#152338] sm:px-8">
        <Link :href="remindersIndex.url()" class="inline-flex items-center gap-2 text-sm font-medium text-[#2457DA]"><ArrowLeft :size="18" /> Kembali ke pengingat</Link>
        <main class="mt-5 rounded-2xl border border-[#E7EBF2] bg-white p-5 sm:p-7">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#EEF3FF] text-[#2457DA]"><CheckCircle2 :size="21" /></span>
                <div><h1 class="text-xl font-semibold">Periksa transaksi</h1><p class="mt-1 text-sm text-[#637083]">{{ reminder.title }} · jatuh tempo {{ log.scheduled_at }}</p></div>
            </div>

            <div v-if="log.status === 'completed'" class="mt-6 rounded-xl bg-[#E6F5EE] p-4 text-sm text-[#176344]">Pengingat ini sudah dicatat sebagai transaksi. Membuka halaman ini tidak membuat transaksi baru.</div>
            <div v-else-if="log.status === 'skipped'" class="mt-6 rounded-xl bg-[#F1F4F9] p-4 text-sm text-[#637083]">Pengingat ini sudah dilewati dan tidak dapat dicatat lagi.</div>
            <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
                <label class="block text-sm font-medium">Judul transaksi<input v-model="form.title" required maxlength="255" class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm" /></label>
                <label class="block text-sm font-medium">Nominal<input v-model="form.amount" type="number" min="0.01" step="0.01" required class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm" /><span v-if="form.errors.amount" class="mt-1 block text-xs text-[#B34735]">{{ form.errors.amount }}</span></label>
                <label class="block text-sm font-medium">Tanggal transaksi<input v-model="form.date" type="date" required class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm" /></label>
                <label class="block text-sm font-medium">Akun
                    <select v-model="form.account_id" required class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm"><option value="" disabled>Pilih akun</option><option v-for="account in accounts" :key="account.id" :value="account.id">{{ account.name }} ({{ account.currency || 'IDR' }})</option></select>
                    <span v-if="form.errors.account_id" class="mt-1 block text-xs text-[#B34735]">{{ form.errors.account_id }}</span>
                </label>
                <label v-if="reminder.type === 'expense'" class="block text-sm font-medium">Sumber dana
                    <select v-model="sourceType" class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm">
                        <option value="free">Uang bebas</option>
                        <option value="budget">Cadangan budget</option>
                        <option value="saving">Savings</option>
                    </select>
                </label>
                <label v-if="reminder.type === 'expense' && sourceType === 'budget'" class="block text-sm font-medium">
                    Periode budget
                    <select v-model="form.budget_period_id" class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm" required>
                        <option :value="null">Pilih periode</option>
                        <option
                            v-for="period in budgetPeriods.filter((item) => Number(item.account_id) === Number(form.account_id) && form.date >= item.starts_on && form.date <= item.ends_on)"
                            :key="period.id"
                            :value="period.id"
                        >{{ period.name }} · {{ period.starts_on }} – {{ period.ends_on }}</option>
                    </select>
                </label>
                <label v-if="reminder.type === 'expense' && sourceType === 'saving'" class="block text-sm font-medium">
                    Savings
                    <select v-model="form.saving_id" class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm" required>
                        <option :value="null">Pilih savings</option>
                        <option
                            v-for="saving in savings.filter((item) => item.currency === accounts.find((account) => Number(account.id) === Number(form.account_id))?.currency)"
                            :key="saving.id"
                            :value="saving.id"
                        >{{ saving.name }}</option>
                    </select>
                </label>
                <label class="block text-sm font-medium">Kategori
                    <select v-model="form.category_id" class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm"><option value="">Tanpa kategori</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select>
                    <span v-if="form.errors.category_id" class="mt-1 block text-xs text-[#B34735]">{{ form.errors.category_id }}</span>
                </label>
                <label class="block text-sm font-medium">Catatan<textarea v-model="form.note" rows="3" maxlength="1000" class="mt-1 block w-full rounded-xl border-[#D8DEE8] text-sm" /></label>
                <div v-if="form.hasErrors" class="space-y-1 text-sm text-[#B34735]">
                    <p v-for="(error, field) in form.errors" :key="field">{{ error }}</p>
                </div>
                <p class="rounded-xl bg-[#EEF3FF] p-3 text-xs text-[#354B72]">Transaksi hanya dibuat setelah Anda menekan Simpan. Jenis transaksi mengikuti pengingat: {{ reminder.type === 'income' ? 'pemasukan' : 'pengeluaran' }}.</p>
                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing || !accounts.length"
                    class="w-full !rounded-xl !py-3 !text-sm"
                >
                    {{ form.processing ? 'Menyimpan…' : 'Simpan transaksi' }}
                </ButtonPrimary>
            </form>
            <Link v-if="['completed', 'skipped'].includes(log.status)" :href="remindersIndex.url()" class="mt-5 inline-flex rounded-xl bg-[#2457DA] px-4 py-2.5 text-sm font-semibold text-white">Kembali</Link>
        </main>
    </div>
</template>
