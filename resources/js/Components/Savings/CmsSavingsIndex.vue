<script setup>
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    Archive,
    ArchiveRestore,
    CheckCircle2,
    ChevronDown,
    Info,
    MoreVertical,
    Plus,
    Trash2,
    X,
} from 'lucide-vue-next';
import {
    archive as archiveSaving,
    destroy as destroySaving,
    unarchive as unarchiveSaving,
} from '@/routes/cms/savings';
import SavingCreateForm from '@/Components/Savings/SavingCreateForm.vue';
import {
    getSavingGradient,
    getSavingIcon,
} from '@/Features/Savings/lib/savingAppearance';

const props = defineProps({
    savings: { type: Array, default: () => [] },
    indexUrl: { type: String, required: true },
    createModalOpen: { type: Boolean, default: false },
    createForm: { type: Object, required: true },
    accounts: { type: Array, default: () => [] },
    createStep: { type: Number, required: true },
    showSavingUrl: { type: Function, required: true },
    manageSavingUrl: { type: Function, required: true },
});

const emit = defineEmits(['update:createStep', 'submit-create']);

const activeTab = ref('active');
const sortOrder = ref('newest');
const openMenuId = ref(null);
const isCreateModalOpen = ref(props.createModalOpen);
const savingPendingDeletion = ref(null);
const deletedSavingName = ref('');
const isDeleteSuccessModalOpen = ref(false);
const actionError = ref('');

watch(() => props.createModalOpen, (isOpen) => {
    isCreateModalOpen.value = isOpen;
});

const activeSavings = computed(() => props.savings.filter((saving) => saving.status === 'active'));
const archivedSavings = computed(() => props.savings.filter((saving) => saving.status === 'archived'));
const visibleSavings = computed(() => {
    const savings = [...(activeTab.value === 'active' ? activeSavings.value : archivedSavings.value)];

    if (sortOrder.value === 'name') {
        return savings.sort((first, second) => first.name.localeCompare(second.name, 'id'));
    }

    if (sortOrder.value === 'balance') {
        return savings.sort((first, second) => Number(second.balance ?? 0) - Number(first.balance ?? 0));
    }

    return savings.sort((first, second) => {
        const firstDate = new Date(first.created_at ?? 0).getTime();
        const secondDate = new Date(second.created_at ?? 0).getTime();

        return secondDate - firstDate || Number(second.id) - Number(first.id);
    });
});

function formatCurrency(amount, currency) {
    const currencyCode = currency || 'IDR';

    try {
        const fractionDigits = new Intl.NumberFormat('en', {
            style: 'currency',
            currency: currencyCode,
        }).resolvedOptions().maximumFractionDigits;
        const formattedAmount = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: fractionDigits,
            maximumFractionDigits: fractionDigits,
        }).format(Number(amount ?? 0));
        const currencySymbols = {
            AUD: 'A$',
            EUR: '€',
            GBP: '£',
            IDR: 'Rp',
            JPY: '¥',
            MYR: 'RM',
            SGD: 'S$',
            USD: 'US$',
        };

        return `${currencySymbols[currencyCode] ?? `${currencyCode} `}${formattedAmount}`;
    } catch {
        return `Rp${new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 0,
        }).format(Number(amount ?? 0))}`;
    }
}

function progressFor(saving) {
    const target = Number(saving.target_amount ?? 0);

    if (target <= 0) {
        return null;
    }

    return Math.min(100, (Number(saving.balance ?? 0) / target) * 100);
}

function progressLabel(saving) {
    const progress = progressFor(saving);

    if (progress === null) {
        return '';
    }

    return progress > 0 && progress < 0.1
        ? '<0,1%'
        : `${Math.round(progress)}%`;
}

function closeCreateModal() {
    if (props.createModalOpen) {
        router.visit(props.indexUrl, { preserveScroll: true });

        return;
    }

    isCreateModalOpen.value = false;
}

function archive(saving) {
    actionError.value = '';
    openMenuId.value = null;

    router.post(archiveSaving.url(saving.id), {}, {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Tabungan gagal diarsipkan.';
        },
    });
}

function unarchive(saving) {
    actionError.value = '';
    openMenuId.value = null;

    router.post(unarchiveSaving.url(saving.id), {}, {
        preserveScroll: true,
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Tabungan gagal dipulihkan.';
        },
    });
}

function remove(saving) {
    openMenuId.value = null;
    savingPendingDeletion.value = saving;
}

function closeDeleteModal() {
    savingPendingDeletion.value = null;
}

function confirmRemove() {
    if (!savingPendingDeletion.value) {
        return;
    }

    const saving = savingPendingDeletion.value;
    savingPendingDeletion.value = null;
    actionError.value = '';

    router.delete(destroySaving.url(saving.id), {
        preserveScroll: true,
        onSuccess: () => {
            deletedSavingName.value = saving.name;
            isDeleteSuccessModalOpen.value = true;
        },
        onError: (errors) => {
            actionError.value = Object.values(errors)[0] ?? 'Tabungan gagal dihapus.';
        },
    });
}

const savingIcon = (saving) => getSavingIcon(saving.icon);
</script>

<template>
    <section class="flex flex-col gap-6 text-[#14213A]">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <h1 class="text-3xl font-bold tracking-tight sm:text-[40px]">Tabungan</h1>
            <ButtonPrimary type="button" class="inline-flex items-center justify-center gap-2 !rounded-xl !px-5" @click="isCreateModalOpen = true">
                <Plus :size="20" />
                Buat tabungan
            </ButtonPrimary>
        </div>

        <div class="flex items-center gap-3 rounded-xl border border-[#DCE5F2] bg-white/70 px-4 py-3 text-sm text-[#5E6F8B]">
            <Info :size="20" class="shrink-0 text-[#1764F5]" />
            <span>Alokasi tabungan tidak mengubah saldo akun.</span>
        </div>

        <p v-if="actionError" role="alert" class="rounded-xl border border-[#F1C7C2] bg-[#FFF4F2] px-4 py-3 text-sm text-[#A33D31]">
            {{ actionError }}
        </p>

        <div class="flex flex-col gap-3 border-b border-[#DCE5F2] sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2" role="tablist" aria-label="Status tabungan">
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'active'"
                    class="inline-flex items-center gap-2 border-b-2 px-3 py-3 text-sm font-semibold transition"
                    :class="activeTab === 'active'
                        ? 'border-[#1764F5] text-[#1764F5]'
                        : 'border-transparent text-[#5E6F8B] hover:text-[#14213A]'"
                    @click="activeTab = 'active'; openMenuId = null"
                >
                    Aktif
                    <span class="rounded-full bg-[#EAF1FF] px-2 py-0.5 text-xs text-[#1764F5]">{{ activeSavings.length }}</span>
                </button>
                <button
                    type="button"
                    role="tab"
                    :aria-selected="activeTab === 'archived'"
                    class="border-b-2 px-3 py-3 text-sm font-semibold transition"
                    :class="activeTab === 'archived'
                        ? 'border-[#1764F5] text-[#1764F5]'
                        : 'border-transparent text-[#5E6F8B] hover:text-[#14213A]'"
                    @click="activeTab = 'archived'; openMenuId = null"
                >
                    Diarsipkan
                </button>
            </div>

            <label class="relative mb-2 self-start sm:mb-0 sm:self-auto">
                <span class="sr-only">Urutkan tabungan</span>
                <select
                    v-model="sortOrder"
                    class="min-h-11 min-w-36 appearance-none rounded-xl border border-[#D4DDEA] bg-white py-2 pl-4 pr-10 text-sm text-[#14213A] outline-none focus:border-[#1764F5]"
                >
                    <option value="newest">Terbaru</option>
                    <option value="name">Nama A-Z</option>
                    <option value="balance">Saldo tertinggi</option>
                </select>
                <ChevronDown :size="16" class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-[#5E6F8B]" />
            </label>
        </div>

        <div v-if="visibleSavings.length" class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="saving in visibleSavings"
                :key="saving.id"
                class="flex min-h-[344px] flex-col rounded-xl border border-[#DCE5F2] bg-white p-5 shadow-[0_1px_2px_rgba(20,33,58,0.03)]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl text-white"
                            :style="{ backgroundImage: getSavingGradient(saving.color) }"
                        >
                            <component :is="savingIcon(saving)" :size="30" :stroke-width="1.8" />
                        </div>
                        <div class="min-w-0">
                            <span class="inline-flex rounded-lg border border-[#D8E7FF] bg-[#F1F6FF] px-2.5 py-1 text-xs font-semibold text-[#1764F5]">
                                {{ saving.currency }}
                            </span>
                            <h2 class="mt-2 truncate text-lg font-semibold text-[#14213A]">{{ saving.name }}</h2>
                        </div>
                    </div>

                    <div class="relative shrink-0">
                        <ButtonPrimary
                            type="button"
                            variant="secondary"
                            class="!min-h-9 !rounded-lg !border-0 !px-2 !py-1 !text-[#14213A]"
                            :aria-label="`Menu ${saving.name}`"
                            :aria-expanded="openMenuId === saving.id"
                            @click="openMenuId = openMenuId === saving.id ? null : saving.id"
                        >
                            <MoreVertical :size="20" />
                        </ButtonPrimary>
                        <div
                            v-if="openMenuId === saving.id"
                            class="absolute right-0 top-10 z-10 flex min-w-44 flex-col rounded-xl border border-[#DCE5F2] bg-white p-1.5 shadow-lg"
                        >
                            <Link
                                :href="showSavingUrl(saving.id)"
                                class="rounded-lg px-3 py-2 text-sm text-[#314158] hover:bg-[#F3F6FB]"
                                @click="openMenuId = null"
                            >Lihat detail</Link>
                            <Link
                                :href="manageSavingUrl(saving.id)"
                                class="rounded-lg px-3 py-2 text-sm text-[#314158] hover:bg-[#F3F6FB]"
                                @click="openMenuId = null"
                            >Pengaturan tabungan</Link>
                            <ButtonPrimary
                                v-if="saving.status === 'active'"
                                type="button"
                                variant="secondary"
                                class="!min-h-0 !inline-flex !items-center !justify-start !gap-2 !rounded-lg !border-0 !px-3 !py-2 !text-sm !font-normal !text-[#314158]"
                                @click="archive(saving)"
                            >
                                <Archive :size="16" />
                                Arsipkan
                            </ButtonPrimary>
                            <ButtonPrimary
                                v-else
                                type="button"
                                variant="secondary"
                                class="!min-h-0 !inline-flex !items-center !justify-start !gap-2 !rounded-lg !border-0 !px-3 !py-2 !text-sm !font-normal !text-[#314158]"
                                @click="unarchive(saving)"
                            >
                                <ArchiveRestore :size="16" />
                                Pulihkan
                            </ButtonPrimary>
                            <div class="my-1 border-t border-[#E7ECF3]" />
                            <ButtonPrimary
                                type="button"
                                variant="secondary"
                                class="!min-h-0 !inline-flex !items-center !justify-start !gap-2 !rounded-lg !border-0 !px-3 !py-2 !text-sm !font-normal !text-[#B34735]"
                                @click="remove(saving)"
                            >
                                <Trash2 :size="16" />
                                {{ $t('Delete permanently') }}
                            </ButtonPrimary>
                        </div>
                    </div>
                </div>

                <div class="mt-8">
                    <p class="text-[32px] font-bold leading-tight tracking-tight text-[#14213A]">
                        {{ formatCurrency(saving.balance, saving.currency) }}
                    </p>
                    <p v-if="Number(saving.target_amount ?? 0) > 0" class="mt-2 text-sm text-[#687A96]">
                        Target {{ formatCurrency(saving.target_amount, saving.currency) }}
                    </p>
                    <p v-else class="mt-2 text-sm text-[#687A96]">Tanpa target</p>

                    <div v-if="progressFor(saving) !== null" class="mt-4 flex items-center gap-3">
                        <div class="h-3 flex-1 overflow-hidden rounded-full bg-[#E8EDF4]">
                            <div
                                class="h-full rounded-full bg-[#1764F5]"
                                :style="{ width: `${progressFor(saving)}%` }"
                            />
                        </div>
                        <span class="min-w-11 text-right text-sm text-[#687A96]">{{ progressLabel(saving) }}</span>
                    </div>
                </div>

                <div class="mt-auto border-t border-[#E2E8F0] pt-4">
                    <Link
                        :href="manageSavingUrl(saving.id, { query: { action: 'allocate' } })"
                        class="flex min-h-9 items-center gap-3 font-semibold text-[#1764F5] hover:text-[#104FC7]"
                    >
                        <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-[#1764F5]">
                            <Plus :size="20" />
                        </span>
                        <span>Tambah dana</span>
                        <ArrowRight :size="20" class="ml-auto text-[#5E6F8B]" />
                    </Link>
                </div>
            </article>
        </div>

        <div v-else class="rounded-xl border border-dashed border-[#CCD8E8] bg-white/70 px-6 py-12 text-center">
            <CheckCircle2 :size="28" class="mx-auto text-[#1764F5]" />
            <h2 class="mt-3 font-semibold text-[#14213A]">
                {{ activeTab === 'active' ? 'Belum ada tabungan aktif' : 'Belum ada tabungan yang diarsipkan' }}
            </h2>
            <p v-if="activeTab === 'active'" class="mt-1 text-sm text-[#687A96]">Buat tabungan untuk mulai mengatur alokasi dana.</p>
            <ButtonPrimary
                v-if="activeTab === 'active'"
                type="button"
                class="mt-5 inline-flex items-center gap-2"
                @click="isCreateModalOpen = true"
            >
                <Plus :size="18" />
                Buat tabungan
            </ButtonPrimary>
        </div>

        <ConfirmationModal
            :show="Boolean(savingPendingDeletion)"
            :title="$t('Permanently delete {name} savings?', { name: savingPendingDeletion?.name ?? '' })"
            :description="$t('Remaining funds will be released to their source accounts. Saving history will be permanently deleted; account transactions will remain.')"
            :cancel-text="$t('No, keep it')"
            :confirm-text="$t('Yes, delete permanently')"
            intent="danger"
            @close="closeDeleteModal"
            @confirm="confirmRemove"
        />

        <SuccessModal
            :show="isDeleteSuccessModalOpen"
            :title="$t('Savings deleted')"
            :message="$t('The savings {name} was deleted. Remaining funds are available in their source accounts.', { name: deletedSavingName })"
            :button-label="$t('Done')"
            @close="isDeleteSuccessModalOpen = false"
        />

        <div
            v-if="isCreateModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-[#14213A]/60 p-4 backdrop-blur-[2px] sm:p-6"
            @click.self="closeCreateModal"
        >
            <section
                class="my-auto w-full max-w-xl rounded-2xl border border-[#E1E8F2] bg-white p-5 shadow-[0_24px_80px_rgba(15,35,70,0.25)] sm:p-7"
                role="dialog"
                aria-modal="true"
                aria-labelledby="create-saving-title"
            >
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 id="create-saving-title" class="text-xl font-bold text-[#14213A]">Buat tabungan</h2>
                        <p class="mt-1 text-sm text-[#687A96]">Siapkan tujuan tabungan dan target opsional.</p>
                    </div>
                    <ButtonPrimary
                        type="button"
                        variant="secondary"
                        class="!flex !h-9 !min-h-9 !w-9 !shrink-0 !items-center !justify-center !rounded-lg !border-0 !p-0 !text-[#5E6F8B] !shadow-none hover:!bg-[#F1F5FA] hover:!text-[#14213A]"
                        aria-label="Tutup modal buat tabungan"
                        @click="closeCreateModal"
                    >
                        <X :size="21" />
                    </ButtonPrimary>
                </div>
                <SavingCreateForm
                    :form="createForm"
                    :accounts="accounts"
                    :step="createStep"
                    appearance-enabled
                    @update:step="emit('update:createStep', $event)"
                    @submit="emit('submit-create')"
                />
            </section>
        </div>
    </section>
</template>
