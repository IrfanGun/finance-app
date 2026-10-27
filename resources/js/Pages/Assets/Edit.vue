<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, X } from 'lucide-vue-next';
import MobileBottomNav from '@/Components/MobileBottomNav.vue';
import { show, update } from '@/routes/assets';

const props = defineProps({
    asset: {
        type: Object,
        required: true,
    },
    currencyOptions: {
        type: Array,
        default: () => ['IDR'],
    },
});

const form = useForm({
    name: props.asset.name,
    description: props.asset.description ?? '',
    currency: props.asset.currency,
    purchase_price: props.asset.purchase_price,
    purchase_date: props.asset.purchase_date,
    depreciation: props.asset.depreciation
        ? {
            depreciation_type: props.asset.depreciation.depreciation_type,
            value_direction: props.asset.depreciation.value_direction,
            is_tiered: props.asset.depreciation.is_tiered ? 1 : 0,
            depreciation_value: props.asset.depreciation.depreciation_value,
            rate_steps: (props.asset.depreciation.rate_steps ?? []).map((step) => ({
                year_number: step.year_number,
                depreciation_value: step.depreciation_value,
            })),
            depreciation_period: props.asset.depreciation.depreciation_period,
            residual_value: props.asset.depreciation.residual_value,
            depreciation_start_date: props.asset.depreciation.depreciation_start_date,
        }
        : null,
});

const save = () => form.patch(update.url(props.asset.id));

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
            depreciation_start_date: props.asset.purchase_date,
        }
        : null;
};

const setValueDirection = (direction) => {
    form.depreciation.value_direction = direction;

    if (direction === 'increase') {
        form.depreciation.depreciation_period = 'yearly';
        form.depreciation.residual_value = '0.00';
    } else if (form.depreciation.is_tiered) {
        form.depreciation.depreciation_period = 'yearly';
    }
};

const setTieredDepreciation = (isTiered) => {
    form.depreciation.is_tiered = isTiered ? 1 : 0;

    if (isTiered) {
        form.depreciation.depreciation_period = 'yearly';
        form.depreciation.rate_steps = form.depreciation.rate_steps?.length
            ? form.depreciation.rate_steps
            : [{ year_number: 1, depreciation_value: '' }];
    }
};

const addRateStep = () => {
    const lastYear = Math.max(
        0,
        ...form.depreciation.rate_steps.map((step) => Number(step.year_number) || 0),
    );

    form.depreciation.rate_steps.push({
        year_number: lastYear + 1,
        depreciation_value: '',
    });
};

const removeRateStep = (index) => {
    if (index > 0) {
        form.depreciation.rate_steps.splice(index, 1);
    }
};
</script>

<template>
    <Head :title="`Edit ${asset.name}`" />

    <div class="mx-auto min-h-screen w-full max-w-lg bg-white pb-28 text-app-heading">
        <header class="flex items-center gap-4 px-6 py-5">
            <Link :href="show.url(asset.id)" :aria-label="`Kembali ke detail ${asset.name}`">
                <ArrowLeft :size="22" class="text-[#2457DA]" />
            </Link>
            <div class="min-w-0">
                <p class="text-xs font-medium text-[#637083]">{{ $t("Assets") }}</p>
                <h1 class="truncate text-lg font-semibold">{{ $t("Edit") }} {{ asset.name }}</h1>
            </div>
        </header>

        <main class="space-y-5 px-6 pt-3">
            <form class="space-y-4 rounded-2xl border border-slate-200 p-4" @submit.prevent="save">
                <div>
                    <h2 class="font-semibold">{{ $t("Asset details") }}</h2>
                    <p class="mt-1 text-xs leading-5 text-[#637083]"> {{ $t("Detail pembelian dan perubahan nilai terkunci setelah ada riwayat.") }} </p>
                </div>

                <label class="block text-sm font-medium"> {{ $t("Asset name") }} <input
                        v-model="form.name"
                        required
                        maxlength="255"
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                    />
                    <p v-if="form.errors.name" class="mt-1 text-xs text-red-600">
                        {{ form.errors.name }}
                    </p>
                </label>

                <label class="block text-sm font-medium"> {{ $t("Description") }} <textarea
                        v-model="form.description"
                        rows="2"
                        class="mt-2 w-full rounded-lg border border-slate-300 px-4 py-3"
                    />
                    <p v-if="form.errors.description" class="mt-1 text-xs text-red-600">
                        {{ form.errors.description }}
                    </p>
                </label>

                <div class="grid grid-cols-2 gap-3">
                    <label class="block text-sm font-medium"> {{ $t("Currency") }} <select
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
                    <label class="block text-sm font-medium"> {{ $t("Purchase price") }} <AmountInput
                            v-model="form.purchase_price"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        />
                        <p v-if="form.errors.purchase_price" class="mt-1 text-xs text-red-600">
                            {{ form.errors.purchase_price }}
                        </p>
                    </label>
                </div>

                <label class="block text-sm font-medium"> {{ $t("Purchase date") }} <input
                        v-model="form.purchase_date"
                        type="date"
                        required
                        class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                    />
                    <p v-if="form.errors.purchase_date" class="mt-1 text-xs text-red-600">
                        {{ form.errors.purchase_date }}
                    </p>
                </label>

                <section class="space-y-3 rounded-xl border border-[#DCE3EE] bg-[#F8FAFF] p-4">
                    <div>
                        <h2 class="font-semibold">{{ $t("Depreciation and appreciation") }}</h2>
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-lg bg-white p-3 text-sm font-medium">
                        <input
                            type="checkbox"
                            :checked="form.depreciation !== null"
                            class="mt-0.5 rounded border-slate-300 text-[#2457DA] focus:ring-[#2457DA]"
                            @change="toggleDepreciation($event.target.checked)"
                        />
                        <span>
                            <span class="block">Aktifkan perubahan nilai otomatis</span>
                            <span class="mt-1 block text-xs font-normal leading-5 text-[#637083]">
                                {{ form.depreciation
                                    ? 'Pengaturan di bawah ini akan dipakai untuk menghitung perubahan nilai aset.'
                                    : 'Belum ada jadwal perubahan nilai. Centang untuk mengatur depresiasi atau apresiasi.' }}
                            </span>
                        </span>
                    </label>
                </section>

                <div v-if="form.depreciation" class="space-y-4">
                    <label class="block text-sm font-medium"> {{ $t("Arah perubahan nilai") }} <select
                            v-model="form.depreciation.value_direction"
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                            @change="setValueDirection($event.target.value)"
                        >
                            <option value="decrease">{{ $t("Turun (depresiasi)") }}</option>
                            <option value="increase">{{ $t("Naik (apresiasi)") }}</option>
                        </select>
                        <p v-if="form.errors['depreciation.value_direction']" class="mt-1 text-xs text-red-600">
                            {{ form.errors['depreciation.value_direction'] }}
                        </p>
                    </label>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-sm font-medium"> {{ $t("Metode perubahan nilai") }} <select
                                v-model="form.depreciation.depreciation_type"
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                            >
                                <option value="fixed">{{ $t("Nominal tetap") }}</option>
                                <option value="percentage">{{ $t("Persentase harga beli") }}</option>
                            </select>
                        </label>
                        <label v-if="!form.depreciation.is_tiered" class="block text-sm font-medium">
                            {{ form.depreciation.depreciation_type === 'fixed' ? `Nominal (${form.currency})` : 'Persentase harga beli (%)' }}
                            <AmountInput
                                v-model="form.depreciation.depreciation_value"
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

                    <label class="flex items-center gap-3 rounded-xl bg-[#F5F7FA] p-4 text-sm font-medium">
                        <input
                            :checked="Boolean(form.depreciation.is_tiered)"
                            type="checkbox"
                            class="rounded border-slate-300 text-[#2457DA] focus:ring-[#2457DA]"
                            @change="setTieredDepreciation($event.target.checked)"
                        /> {{ $t("Bertingkat per tahun") }} </label>

                    <div v-if="form.depreciation.is_tiered" class="space-y-3 rounded-xl bg-[#F5F7FA] p-3">
                        <div
                            v-for="(step, index) in form.depreciation.rate_steps"
                            :key="index"
                            class="grid grid-cols-[1fr_1fr_auto] items-end gap-2"
                        >
                            <label class="block text-sm font-medium"> {{ $t("Tahun ke-") }} <input
                                    v-model.number="step.year_number"
                                    type="number"
                                    min="1"
                                    max="1000"
                                    :readonly="index === 0"
                                    required
                                    class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                                />
                                <p v-if="form.errors[`depreciation.rate_steps.${index}.year_number`]" class="mt-1 text-xs text-red-600">
                                    {{ form.errors[`depreciation.rate_steps.${index}.year_number`] }}
                                </p>
                            </label>
                            <label class="block text-sm font-medium">
                                {{ form.depreciation.depreciation_type === 'fixed' ? `Nominal (${form.currency})` : 'Persentase harga beli (%)' }}
                                <AmountInput
                                    v-model="step.depreciation_value"
                                    :max="form.depreciation.depreciation_type === 'percentage' ? 100 : undefined"
                                    :step="form.depreciation.depreciation_type === 'fixed' ? 0.01 : 0.0001"
                                    required
                                    class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                                />
                                <p v-if="form.errors[`depreciation.rate_steps.${index}.depreciation_value`]" class="mt-1 text-xs text-red-600">
                                    {{ form.errors[`depreciation.rate_steps.${index}.depreciation_value`] }}
                                </p>
                            </label>
                            <button
                                v-if="index > 0"
                                type="button"
                                class="mb-2 rounded-lg p-2 text-red-600"
                                :aria-label="`Hapus jadwal tahun ke-${step.year_number}`"
                                @click="removeRateStep(index)"
                            >
                                <X :size="18" />
                            </button>
                        </div>
                        <p v-if="form.errors['depreciation.rate_steps']" class="text-xs text-red-600">
                            {{ form.errors['depreciation.rate_steps'] }}
                        </p>
                        <p class="text-xs leading-5 text-[#637083]">
                            Persentase dihitung dari harga beli, sedangkan nominal memakai jumlah tetap. Nilai terakhir terus berlaku sampai jadwal berikutnya.
                        </p>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-[#2457DA]"
                            @click="addRateStep"
                        >
                            <Plus :size="16" /> {{ $t("Tambah tahun perubahan tarif") }} </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="block text-sm font-medium"> {{ $t("Period") }} <select
                                v-if="!form.depreciation.is_tiered && form.depreciation.value_direction === 'decrease'"
                                v-model="form.depreciation.depreciation_period"
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                            >
                                <option value="monthly">{{ $t("Monthly") }}</option>
                                <option value="yearly">{{ $t("Yearly") }}</option>
                            </select>
                            <div
                                v-else
                                class="mt-2 flex h-11 items-center rounded-lg border border-slate-300 bg-slate-50 px-3 text-sm text-[#637083]"
                            > {{ $t("Yearly") }} </div>
                        </label>
                        <label v-if="form.depreciation.value_direction === 'decrease'" class="block text-sm font-medium"> {{ $t("Nilai minimum") }} <AmountInput
                                v-model="form.depreciation.residual_value"
                                required
                                class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-3"
                            />
                            <p v-if="form.errors['depreciation.residual_value']" class="mt-1 text-xs text-red-600">
                                {{ form.errors['depreciation.residual_value'] }}
                            </p>
                        </label>
                    </div>

                    <label class="block text-sm font-medium"> {{ $t("Tanggal acuan") }} <input
                            v-model="form.depreciation.depreciation_start_date"
                            type="date"
                            :min="form.purchase_date"
                            required
                            class="mt-2 h-11 w-full rounded-lg border border-slate-300 px-4"
                        />
                        <p v-if="form.errors['depreciation.depreciation_start_date']" class="mt-1 text-xs text-red-600">
                            {{ form.errors['depreciation.depreciation_start_date'] }}
                        </p>
                    </label>
                </div>

                <p v-if="form.errors.depreciation" class="text-xs text-red-600">
                    {{ form.errors.depreciation }}
                </p>

                <ButtonPrimary
                    type="submit"
                    :disabled="form.processing"
                    class="w-full"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan perubahan' }}
                </ButtonPrimary>
            </form>
        </main>

        <MobileBottomNav />
    </div>
</template>
