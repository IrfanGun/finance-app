<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ChevronRight, Plus, X } from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import { dashboard } from '@/routes';
import { show, store } from '@/routes/assets';

const props = defineProps({
    assets: {
        type: Object,
        default: () => ({ data: [], links: [] }),
    },
    currencyOptions: {
        type: Array,
        default: () => ['IDR'],
    },
});

const creating = ref(false);
const currentStep = ref(1);
const createForm = ref(null);
const businessToday = () => new Intl.DateTimeFormat('sv-SE', {
    timeZone: 'Asia/Jakarta',
}).format(new Date());
const formatDate = (value) => {
    if (!value) {
        return '—';
    }

    const date = new Date(`${String(value).slice(0, 10)}T00:00:00Z`);

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(date);
};
const form = useForm({
    name: '',
    description: '',
    currency: props.currencyOptions[0] ?? 'IDR',
    purchase_price: '',
    purchase_date: businessToday(),
    depreciation: null,
});

const startCreate = () => {
    form.reset();
    form.clearErrors();
    form.currency = props.currencyOptions[0] ?? 'IDR';
    form.purchase_date = businessToday();
    currentStep.value = 1;
    creating.value = true;
};

const closeCreate = () => {
    creating.value = false;
    currentStep.value = 1;
};

const backCreateStep = () => {
    if (currentStep.value === 2) {
        currentStep.value = 1;

        return;
    }

    closeCreate();
};

const toggleDepreciation = (enabled) => {
    form.clearErrors();
    form.depreciation = enabled
        ? {
            depreciation_type: 'fixed',
            value_direction: 'decrease',
            depreciation_value: '',
            is_tiered: 0,
            depreciation_period: 'monthly',
            rate_steps: [],
            residual_value: '0.00',
            depreciation_start_date: form.purchase_date,
        }
        : null;
};

const setDepreciationType = (depreciation, type) => {
    depreciation.depreciation_type = type;
};

const setValueDirection = (depreciation, direction) => {
    depreciation.value_direction = direction;

    if (direction === 'increase') {
        depreciation.depreciation_period = 'yearly';
        depreciation.residual_value = '0.00';
    } else if (depreciation.is_tiered) {
        depreciation.depreciation_period = 'yearly';
    }
};

const setTieredDepreciation = (depreciation, isTiered) => {
    depreciation.is_tiered = isTiered ? 1 : 0;

    if (isTiered) {
        depreciation.depreciation_period = 'yearly';
        depreciation.rate_steps = depreciation.rate_steps?.length
            ? depreciation.rate_steps
            : [{ year_number: 1, depreciation_value: '' }];
    }
};

const addRateStep = (depreciation) => {
    const lastYear = Math.max(
        0,
        ...depreciation.rate_steps.map((step) => Number(step.year_number) || 0),
    );

    depreciation.rate_steps.push({
        year_number: lastYear + 1,
        depreciation_value: '',
    });
};

const removeRateStep = (depreciation, index) => {
    if (index > 0) {
        depreciation.rate_steps.splice(index, 1);
    }
};

const continueToDepreciation = () => {
    if (createForm.value?.reportValidity() === false) {
        return;
    }

    currentStep.value = 2;
};

const save = () => {
    form.post(store.url(), {
        onSuccess: () => {
            closeCreate();
            form.reset();
        },
        onError: (errors) => {
            const hasAssetDetailsErrors = [
                'name',
                'currency',
                'purchase_price',
                'purchase_date',
            ].some((key) => errors[key]);

            const hasDepreciationErrors = Object.keys(errors).some(
                (key) => key.startsWith('depreciation'),
            );

            currentStep.value = hasAssetDetailsErrors
                ? 1
                : hasDepreciationErrors
                    ? 2
                    : currentStep.value;
        },
    });
};

const formatMoney = (amount, currency = 'IDR') => new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency,
    maximumFractionDigits: 2,
}).format(Number(amount ?? 0));
</script>

<template>
    <Head :title="creating ? 'Aset baru' : 'Assets'" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header v-if="!creating" class="flex items-center justify-between px-6 py-5">
            <Link :href="dashboard.url()" aria-label="Back to dashboard">
                <ArrowLeft :size="22" class="text-[#2457DA]" />
            </Link>
            <h1 class="text-lg font-semibold">Assets</h1>
            <button
                type="button"
                class="rounded-full bg-app-primary p-2 text-white"
                aria-label="Add asset"
                @click="startCreate"
            >
                <Plus :size="18" />
            </button>
        </header>

        <main v-if="!creating" class="space-y-3 px-6 pt-5">
            <p v-if="!assets.data.length" class="rounded-2xl bg-[#F5F7FA] px-5 py-10 text-center text-sm text-[#637083]">
                Belum ada aset. Catat harga beli dan atur perubahan nilainya saat membuat aset.
            </p>

            <Link
                v-for="asset in assets.data"
                :key="asset.id"
                :href="show.url(asset.id)"
                class="block rounded-xl bg-[#F5F7FA] p-4 transition hover:bg-[#EEF3FF]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="truncate font-semibold">{{ asset.name }}</h2>
                        <p class="mt-1 text-xs capitalize text-[#637083]">
                            {{ asset.status }} · Dibeli {{ formatDate(asset.purchase_date) }}
                        </p>
                    </div>
                    <ChevronRight :size="18" class="mt-1 shrink-0 text-[#2457DA]" />
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3">
                    <div>
                        <p class="text-xs text-[#637083]">Estimasi nilai sekarang</p>
                        <p class="mt-1 font-semibold">{{ formatMoney(asset.current_value, asset.currency) }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-[#637083]">Harga beli</p>
                        <p class="mt-1 font-medium">{{ formatMoney(asset.purchase_price, asset.currency) }}</p>
                    </div>
                </div>
                <p v-if="asset.depreciation" class="mt-3 text-xs capitalize text-[#637083]">
                    Nilai {{ asset.depreciation.value_direction === 'increase' ? 'naik' : 'turun' }}
                    · {{ asset.depreciation.depreciation_type === 'fixed' ? 'nominal tetap' : 'persentase harga beli' }}
                    · {{ asset.depreciation.status }}
                </p>
            </Link>

            <nav v-if="assets.links?.length > 3" class="flex flex-wrap justify-center gap-2 pt-3" aria-label="Asset pages">
                <Link
                    v-for="link in assets.links"
                    :key="`${link.label}-${link.url}`"
                    :href="link.url || '#'"
                    :class="[
                        'rounded-lg px-3 py-2 text-sm',
                        link.active ? 'bg-[#2457DA] text-white' : 'bg-[#F5F7FA] text-[#314158]',
                        !link.url ? 'pointer-events-none opacity-40' : '',
                    ]"
                    v-html="link.label"
                />
            </nav>
        </main>

        <section v-else class="px-6 pb-8 pt-3">
            <header class="flex items-center gap-4">
                <button
                    type="button"
                    class="rounded-full p-2 text-[#2457DA] hover:bg-[#F5F7FA]"
                    :aria-label="currentStep === 2 ? 'Kembali ke detail aset' : 'Kembali ke daftar aset'"
                    @click="backCreateStep"
                >
                    <ArrowLeft :size="22" />
                </button>
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-medium text-[#637083]">Aset nonlikuid</p>
                    <h1 class="text-xl font-semibold">
                        {{ currentStep === 1 ? 'Aset baru' : 'Atur perubahan nilai' }}
                    </h1>
                </div>
                <span class="shrink-0 text-sm font-medium text-[#637083]">
                    Langkah {{ currentStep }} dari {{ form.depreciation ? 2 : 1 }}
                </span>
            </header>

            <div class="mt-6 flex gap-2" aria-hidden="true">
                <div class="h-1.5 flex-1 rounded-full" :class="currentStep >= 1 ? 'bg-[#2457DA]' : 'bg-slate-200'"></div>
                <div
                    v-if="form.depreciation"
                    class="h-1.5 flex-1 rounded-full"
                    :class="currentStep >= 2 ? 'bg-[#2457DA]' : 'bg-slate-200'"
                ></div>
            </div>

            <form
                ref="createForm"
                class="mt-6 space-y-5"
                novalidate
                @submit.prevent="save"
            >
                <div
                    v-if="Object.keys(form.errors).length"
                    class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                    role="alert"
                >
                    <p
                        v-for="(error, key) in form.errors"
                        :key="key"
                    >
                        {{ error }}
                    </p>
                </div>
                <div v-if="currentStep === 1" class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold">Detail aset</h2>
                        <p class="mt-1 text-sm leading-5 text-[#637083]">
                            Catat informasi dasar dan nilai saat aset dibeli.
                        </p>
                    </div>

                    <label class="block text-sm font-medium">
                        Nama aset
                        <input
                            v-model="form.name"
                            required
                            maxlength="255"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                            placeholder="Contoh: Mobil A"
                        />
                        <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                            {{ form.errors.name }}
                        </p>
                    </label>

                    <label class="block text-sm font-medium">
                        Deskripsi
                        <textarea
                            v-model="form.description"
                            rows="2"
                            class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3"
                            placeholder="Opsional"
                        />
                        <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">
                            {{ form.errors.description }}
                        </p>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-sm font-medium">
                            Mata uang
                            <select
                                v-model="form.currency"
                                required
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 bg-white px-4"
                            >
                                <option v-for="currency in currencyOptions" :key="currency" :value="currency">
                                    {{ currency }}
                                </option>
                            </select>
                            <p v-if="form.errors.currency" class="mt-1 text-xs text-red-600">
                                {{ form.errors.currency }}
                            </p>
                        </label>
                        <label class="block text-sm font-medium">
                            Harga beli
                            <input
                                v-model="form.purchase_price"
                                type="number"
                                min="0.01"
                                step="0.01"
                                required
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                            />
                            <p v-if="form.errors.purchase_price" class="mt-1 text-xs text-red-600">
                                {{ form.errors.purchase_price }}
                            </p>
                        </label>
                    </div>

                    <label class="block text-sm font-medium">
                        Tanggal beli
                        <input
                            v-model="form.purchase_date"
                            type="date"
                            :max="businessToday()"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        />
                        <p v-if="form.errors.purchase_date" class="mt-1 text-xs text-red-600">
                            {{ form.errors.purchase_date }}
                        </p>
                    </label>

                    <label class="flex items-center gap-3 rounded-xl bg-[#F5F7FA] p-4 text-sm font-medium">
                        <input type="checkbox" :checked="form.depreciation !== null" class="rounded border-slate-300 text-[#2457DA] focus:ring-[#2457DA]" @change="toggleDepreciation($event.target.checked)" />
                        <span>
                            <span class="block">Aktifkan perubahan nilai otomatis</span>
                            <span class="mt-1 block text-xs font-normal leading-5 text-[#637083]">
                                Atur depresiasi atau apresiasi aset pada langkah berikutnya.
                            </span>
                        </span>
                    </label>
                </div>

                <div v-if="currentStep === 2 && form.depreciation" class="space-y-4">
                    <div>
                        <h2 class="text-lg font-semibold">Formulir depresiasi</h2>
                      
                    </div>

                    <div class="space-y-4 rounded-xl border border-slate-200 p-4">
                    <label class="block text-sm font-medium">
                        Arah perubahan nilai
                        <select
                            v-model="form.depreciation.value_direction"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                            @change="setValueDirection(form.depreciation, $event.target.value)"
                        >
                            <option value="decrease">Turun (depresiasi)</option>
                            <option value="increase">Naik (apresiasi)</option>
                        </select>
                        <p v-if="form.errors['depreciation.value_direction']" class="mt-1 text-xs text-red-600">
                            {{ form.errors['depreciation.value_direction'] }}
                        </p>
                    </label>
                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-sm font-medium">
                            Metode perubahan nilai
                            <select
                                v-model="form.depreciation.depreciation_type"
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                                @change="setDepreciationType(form.depreciation, $event.target.value)"
                            >
                                <option value="fixed">Nominal tetap</option>
                                <option value="percentage">Persentase harga beli</option>
                            </select>
                        </label>
                        <label v-if="!form.depreciation.is_tiered" class="block text-sm font-medium">
                            {{ form.depreciation.depreciation_type === 'fixed' ? `Nominal (${form.currency})` : 'Persentase harga beli (%)' }}
                            <input
                                v-model="form.depreciation.depreciation_value"
                                type="number"
                                min="0.0001"
                                :max="form.depreciation.depreciation_type === 'percentage' ? 100 : undefined"
                                :step="form.depreciation.depreciation_type === 'fixed' ? 0.01 : 0.0001"
                                required
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                            />
                            <p v-if="form.errors['depreciation.depreciation_value']" class="mt-1 text-xs text-red-600">
                                {{ form.errors['depreciation.depreciation_value'] }}
                            </p>
                        </label>
                    </div>

                    <label class="flex items-center gap-3 rounded-lg bg-slate-50 p-3 text-sm font-medium">
                        <input
                            :checked="Boolean(form.depreciation.is_tiered)"
                            type="checkbox"
                            class="rounded border-slate-300 text-[#2457DA] focus:ring-[#2457DA]"
                            @change="setTieredDepreciation(form.depreciation, $event.target.checked)"
                        />
                        Bertingkat per tahun
                    </label>

                    <div v-if="form.depreciation.is_tiered" class="space-y-3">
                        <div class="space-y-3">
                            <div
                                v-for="(step, index) in form.depreciation.rate_steps"
                                :key="index"
                                class="grid grid-cols-[1fr_1fr_auto] items-end gap-2"
                            >
                                <label class="block text-sm font-medium">
                                    Mulai tahun ke-
                                    <input
                                        v-model.number="step.year_number"
                                        type="number"
                                        min="1"
                                        max="1000"
                                        :readonly="index === 0"
                                        required
                                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3 read-only:bg-slate-100"
                                    />
                                    <p
                                        v-if="form.errors[`depreciation.rate_steps.${index}.year_number`]"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ form.errors[`depreciation.rate_steps.${index}.year_number`] }}
                                    </p>
                                </label>
                                <label class="block text-sm font-medium">
                                    {{ form.depreciation.depreciation_type === 'fixed' ? `Nominal (${form.currency})` : 'Persentase harga beli (%)' }}
                                    <input
                                        v-model="step.depreciation_value"
                                        type="number"
                                        min="0.0001"
                                        :max="form.depreciation.depreciation_type === 'percentage' ? 100 : undefined"
                                        :step="form.depreciation.depreciation_type === 'fixed' ? 0.01 : 0.0001"
                                        required
                                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                                    />
                                    <p
                                        v-if="form.errors[`depreciation.rate_steps.${index}.depreciation_value`]"
                                        class="mt-1 text-xs text-red-600"
                                    >
                                        {{ form.errors[`depreciation.rate_steps.${index}.depreciation_value`] }}
                                    </p>
                                </label>
                                <button
                                    v-if="index > 0"
                                    type="button"
                                    class="mb-1 rounded-lg p-2 text-slate-500 hover:bg-slate-100"
                                    :aria-label="`Hapus tarif tahun ke-${step.year_number}`"
                                    @click="removeRateStep(form.depreciation, index)"
                                >
                                    <X :size="18" />
                                </button>
                            </div>
                        </div>
                        <button
                            type="button"
                            class="text-sm font-medium text-[#2457DA]"
                            @click="addRateStep(form.depreciation)"
                        >
                            + Tambah tingkat tarif
                        </button>
                        <p v-if="form.errors['depreciation.rate_steps']" class="text-xs text-red-600">
                            {{ form.errors['depreciation.rate_steps'] }}
                        </p>
                        <p class="text-xs leading-5 text-[#637083]">
                            Jadwal persentase dihitung dari harga beli. Jadwal nominal memakai jumlah tetap per tahun. Nilai terakhir terus berlaku sampai ada tingkat berikutnya.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div v-if="form.depreciation.is_tiered || form.depreciation.value_direction === 'increase'" class="text-sm font-medium">
                            Periode
                            <p class="mt-2 flex h-11 items-center rounded-lg border border-slate-300 bg-slate-50 px-3">
                                Tahunan
                            </p>
                        </div>
                        <label v-else class="block text-sm font-medium">
                            Periode
                            <select v-model="form.depreciation.depreciation_period" class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3">
                                <option value="monthly">Bulanan</option>
                                <option value="yearly">Tahunan</option>
                            </select>
                        </label>
                        <label v-if="form.depreciation.value_direction === 'decrease'" class="block text-sm font-medium">
                            Nilai minimum
                            <input v-model="form.depreciation.residual_value" type="number" min="0" step="0.01" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3" />
                            <p v-if="form.errors['depreciation.residual_value']" class="mt-1 text-xs text-red-600">{{ form.errors['depreciation.residual_value'] }}</p>
                        </label>
                    </div>
                    <label class="block text-sm font-medium">
                        Mulai hitung dari
                        <input v-model="form.depreciation.depreciation_start_date" type="date" :min="form.purchase_date" :max="businessToday()" required class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4" />
                        <p v-if="form.errors['depreciation.depreciation_start_date']" class="mt-1 text-xs text-red-600">{{ form.errors['depreciation.depreciation_start_date'] }}</p>
                    </label>
                    <p class="text-xs leading-5 text-[#637083]">
                        Periode pertama jatuh tempo satu periode penuh setelah tanggal acuan. Perubahan nilai berjalan otomatis setiap tahun dan tidak mengubah saldo akun kas atau bank.
                    </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2">
                    <ButtonPrimary
                        v-if="currentStep === 2"
                        type="button"
                        variant="secondary"
                        class="w-full"
                        @click="currentStep = 1"
                    >
                        Kembali
                    </ButtonPrimary>
                    <ButtonPrimary
                        v-if="currentStep === 1 && form.depreciation"
                        type="button"
                        class="col-span-2 w-full"
                        @click="continueToDepreciation"
                    >
                        Lanjut ke depresiasi
                    </ButtonPrimary>
                    <ButtonPrimary
                        v-else
                        type="submit"
                        :disabled="form.processing"
                        :class="currentStep === 1 ? 'col-span-2 w-full' : 'w-full'"
                    >
                        {{ form.processing ? 'Menyimpan...' : 'Simpan aset' }}
                    </ButtonPrimary>
                </div>
            </form>
        </section>

        <MobileBottomNav />
    </div>
</template>
